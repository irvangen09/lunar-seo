# SITEMAP_MODULE_ARCHITECTURE.md

**Project:** Lunar SEO
**Module:** Sitemap
**Version:** 1.1 (LOCKED - 3 🔶 points confirmed via live end-to-end testing)
**Status:** LOCKED

> This document defines the technical architecture of the Sitemap module. It follows the pattern already proven in `GENERAL_MODULE_ARCHITECTURE.md` — parts that simply reuse that pattern are described briefly, while the 3 points that are genuinely new (and carry a real trade-off) are marked 🔶 and have been confirmed through real-world testing (see §9).

---

# 1. Module Structure

```text
modules/sitemap/
├── Module.php                    → Module bootstrap (same pattern as General)
├── Settings/
│   ├── SectionInterface.php      → Section contract (a module separate from General, no shared dependency)
│   ├── Settings.php               → Orchestrator + custom REST route (same pattern as General)
│   ├── SitemapContent.php         → Field schema: which content types are included + dynamic Custom Post Type/Taxonomy
│   ├── ExcludedItems.php          → Field schema: excluded categories, excluded post IDs
│   ├── Priorities.php             → Field schema: priority values per type + Automatic/Manual mode
│   └── Changefreq.php             → Field schema: changefreq values per type
├── Services/
│   ├── ContentTypeRegistry.php    → Detects Custom Post Types & Custom Taxonomies registered on the site
│   ├── PriorityCalculator.php     → Calculates Automatic-mode priority for Posts (see §5 🔶)
│   ├── SitemapCache.php           → Caches generated output + automatic invalidation (see §4 🔶)
│   └── XmlBuilder.php             → Builds the XML string (sitemap index / URL entries) from raw data
├── Providers/                     → One generic provider per content CATEGORY (not per specific type)
│   ├── ProviderInterface.php      → Contract: get_entries(): array
│   ├── HomepageProvider.php       → Homepage (1 entry)
│   ├── PostTypeProvider.php       → GENERIC - used for Posts/Pages/any CPT (parametrized by post type slug)
│   ├── TaxonomyProvider.php       → GENERIC - used for Categories/Tags/any custom Taxonomy (parametrized by taxonomy slug)
│   └── AuthorProvider.php         → Author archive pages
├── Frontend.php                   → Orchestrator: rewrite rules + request interception + XML output
├── Admin.php                      → Menu + React root container (same pattern as General)
└── Assets.php                     → Asset enqueueing (same pattern as General)
```

**Why generic Providers were chosen (rather than one class per type):**
`PostTypeProvider` and `TaxonomyProvider` take a post type/taxonomy slug as a parameter, so **any Post, Page, or Custom Post Type (including WooCommerce's `product`, if the site uses it) is automatically handled by the SAME class** — no new class needs to be created every time a new Custom Post Type/Taxonomy appears. This is consistent with our earlier decision not to hardcode "product" as a special case (§ discussed previously), and aligns with `ARCHITECTURE.md §18 — Extensibility`.

**No `Editor.php`** — unlike the General module, Sitemap has no per-post Gutenberg integration. Content exclusion is handled via **Excluded Items** in Global Settings (an ID list, per the spec document), not a per-post toggle. This purely follows the documented scope (Scope Discipline — `ENGINEERING_PRINCIPLES.md #18`), not a delay in implementation.

---

# 2. Option Data Model

Follows the exact same pattern as General: **1 option per module** (`lunar_seo_sitemap_settings`), via `OptionManager`, with a custom REST route at `lunar-seo/v1/sitemap-settings`.

```php
[
    'sitemap_content' => [
        'include_homepage'      => bool,
        'include_posts'         => bool,
        'include_static_pages'  => bool,
        'include_categories'    => bool,
        'include_archives'      => bool,
        'include_author_pages'  => bool,
        'include_tag_pages'     => bool,
        'include_last_modified' => bool,
        'links_per_page'        => int,   // default 1000
        'custom_post_types'     => [ '<slug>' => bool, ... ],  // dynamic
        'custom_taxonomies'     => [ '<slug>' => bool, ... ],  // dynamic
    ],
    'excluded_items' => [
        'excluded_categories' => int[],  // term ID
        'excluded_posts'      => int[],  // post ID, parsed from an input like "110,121"
    ],
    'priorities' => [
        'homepage'                  => float,
        'posts'                     => float,  // used ONLY if auto_calculate = false
        'minimum_post_priority'     => float,  // lower bound, ALWAYS applies
        'auto_calculate_post_priority' => bool,
        'static_pages'              => float,
        'categories'                => float,
        'archives'                  => float,
        'tag_pages'                 => float,
        'author_pages'              => float,
        'custom_post_type_default'  => float,  // 1 value applied to ALL custom post types
        'custom_taxonomy_default'   => float,  // 1 value applied to ALL custom taxonomies
    ],
    'changefreq' => [
        'homepage'                 => string, // always|hourly|daily|weekly|monthly|yearly|never
        'posts'                    => string,
        'static_pages'             => string,
        'categories'               => string,
        'archives'                 => string,
        'tag_pages'                => string,
        'author_pages'             => string,
        'custom_post_type_default' => string,
        'custom_taxonomy_default'  => string,
    ],
]
```

---

# 3. Sitemap File Naming

Per an earlier decision — the spec document is the reference, with the one gap it left (Authors) filled in consistently with the existing pluralization pattern:

| Type | URL |
|---|---|
| Sitemap Index | `/sitemap.xml` |
| Homepage | `/homepage-sitemap.xml` |
| Posts | `/post-sitemap.xml` |
| Pages | `/page-sitemap.xml` |
| Categories | `/category-sitemap.xml` |
| Tags | `/tags-sitemap.xml` |
| Authors | `/authors-sitemap.xml` |
| Custom Post Type `x` | `/x-sitemap.xml` |
| Custom Taxonomy `y` | `/y-sitemap.xml` |

Pagination (once Links Per Page is exceeded): `/post-sitemap.xml`, `/post-sitemap2.xml`, `/post-sitemap3.xml`, etc. — per the spec document.

---

# 4. 🔶 DECISION — Sitemap Generation & Caching Strategy

## Options

| Option | Description | Trade-off |
|---|---|---|
| **A. Pure on-the-fly** | Query the database every time `/post-sitemap.xml` is accessed | Simple, always accurate, BUT heavy for content-rich sites — search engine crawlers hit sitemaps fairly often |
| **B. Transient cache + automatic invalidation** (**recommended**) | Generated output is stored (`set_transient`) and reused until relevant content changes | Fast for repeat requests, needs invalidation logic, but not complex |
| **C. Generate static files on disk** | Write physical `.xml` files to the uploads folder | Fastest, but needs filesystem write permission, extra complexity (race conditions, cleanup) |

## Recommendation: **Option B**

**Rationale:**
- The spec document itself implies this: *"The sitemap will update automatically whenever content is published, updated, or deleted, **with no manual regeneration required**."* — this phrasing only makes sense if there's a cache+invalidation mechanism; if it were purely on-the-fly, there'd be no "regeneration process" worth discussing at all (it's always automatic by definition).
- `ARCHITECTURE.md §15 — Performance Strategy`: *"Minimal queries"* — querying every post/term on every sitemap access is wasteful for large sites.
- Option C was rejected because the filesystem complexity isn't worth the benefit for this case (`ENGINEERING_PRINCIPLES.md #5 — Every Feature Has a Cost`).

**Cache invalidation is triggered by:**
- `save_post`, `delete_post`, `trashed_post` → invalidates the sitemap transient for the related post type
- `edited_term`, `delete_term`, `created_term` → invalidates the sitemap transient for the related taxonomy
- Sitemap settings saved (via the REST route) → invalidates ALL sitemap transients (safe, since toggling Include/Exclude can change the entire structure)

**Transients do NOT use an expiration time** (not time-based) — pure event-driven invalidation, so the sitemap is always accurate exactly when an event happens, rather than waiting for a timeout.

---

# 5. 🔶 DECISION — Automatic Priority Calculation Formula (Posts)

The spec document mentions an **Automatic** mode for Posts, but doesn't specify the formula. I'm proposing the following:

## Recommendation: Rank-based Linear Interpolation

```
priority = minimum_post_priority + ( ( posts_priority - minimum_post_priority ) / total_posts ) × ( total_posts - rank + 1 )
```

Where:
- `rank` = the post's order by publish date (1 = the **newest** post)
- `posts_priority` = the value from the "Posts" field (used as a **ceiling**, not ignored — still relevant even in Automatic mode)
- `minimum_post_priority` = the lower bound (always applies, per the spec document)

**Effect:** the newest post gets a priority close to the "Posts" value (e.g. 0.8), the oldest post approaches "Minimum Post Priority" (e.g. 0.2), decreasing linearly based on **rank order**, not date difference — so there's no need for more complex content-age calculations (avoiding over-engineering).

**Why a rank-based (rather than age/date-based) approach:**
- Simple and deterministic — no need to define an arbitrary "reasonable time scale" (months? years?).
- Consistent with `ENGINEERING_PRINCIPLES.md §17 — Maintainability First`.

**An important note you should know:** Google has publicly stated that they **ignore** the `<priority>`/`<changefreq>` values for ranking purposes as of the last several years — these fields are essentially vestigial from a modern SEO standpoint. I'm still implementing them per the spec document (since it explicitly asks for them, and some other tools/search engines may still read them), but wanted you to know so you don't expect this to directly affect Google ranking.

---

# 6. 🔶 DECISION (minor) — Rewrite Rules & XML Output

## Approach

Following the **Remove Category Base** feature already built in the General module:

1. `generate_rewrite_rules` filter → registers a rule for every active sitemap (dynamic based on Settings), mapped to the `lunar_seo_sitemap` query var (example values: `post`, `post2`, `category`, `index`).
2. `template_redirect` hook → checks that query var; if present, calls `XmlBuilder` (reads from `SitemapCache` first, generates+stores if not cached), sets the `Content-Type: application/xml; charset=UTF-8` header, echoes the XML, then `exit`.
3. Rewrite rules are flushed automatically when Settings are saved.

**Revision note (post live-testing):** The initial implementation briefly used `add_rewrite_rule()` on the `init` hook (instead of the `generate_rewrite_rules` filter). This caused a real bug: since `init` only reads Settings once per request — and while a Settings-save REST request was **still in progress** — the rules flushed by `flush_rewrite_rules()` were still the old version, causing `/post-sitemap.xml` to 404 until a second Save. This was fixed by moving rule registration to the `generate_rewrite_rules` filter (exactly the pattern used by General's `UrlRewriter::add_taxonomy_rewrite_rules()`), which runs exactly when ruleset regeneration happens, so it always reads the latest Settings. Verified: a single Save is now enough.

---

# 7. Admin Settings Page

Identical pattern to General: React app + custom REST route (`lunar-seo/v1/sitemap-settings`), 4 sections:
- **Sitemap Content** (fixed checkboxes + dynamic checkboxes for Custom Post Type/Taxonomy from `ContentTypeRegistry`)
- **Excluded Items** (category checklist + a post-ID textarea, parsed into an array during sanitization)
- **Priorities** (dropdown per type + an Automatic toggle for Posts)
- **Changefreq** (dropdown per type — a new design since there was no mockup, closely mirroring the Priorities structure)

---

# 8. Decision Summary

| # | Decision | Recommendation | Status |
|---|---|---|---|
| 🔶 1 | Sitemap caching strategy | Transient + event-driven invalidation (Option B) | ✅ Confirmed live |
| 🔶 2 | Automatic Priority formula | Rank-based linear interpolation | ✅ Confirmed live |
| 🔶 3 | Rewrite rules & output | `generate_rewrite_rules` filter, same pattern as Category Base | ✅ Confirmed live (after 1 bug revision, see §6) |

---

# 9. End-to-End Test Results

The following scenarios were tested directly on the server and **passed**:

- Basic structure (`/sitemap.xml` + every per-content-type sitemap) — homepage, post, page, category, tags, authors, archives, custom post type/taxonomy.
- Dynamic Sitemap Content (toggling Include per type takes effect on `/sitemap.xml` after 1 Save).
- Excluded Items (excluded categories/posts consistently don't appear).
- Priorities & Changefreq (including changing values like hourly/always/etc. on Post, Homepage, Category).
- Include Archives — confirmed to work as expected (monthly archives, `/YYYY/MM/`).
- Event-driven cache invalidation — publishing/deleting a post, a new/removed author, a new archive month — all reflected automatically without manual regeneration.
- Links Per Page pagination (`post-sitemap2.xml` etc. appear automatically once content exceeds the limit).

Two bugs were found & fixed during this process (a useful reference if the Schema module needs a similar pattern):
- **Incomplete cache invalidation** — the `authors`/`archives` cache wasn't being invalidated when a post changed, even though both are derived from post data. Fix: `SitemapCache::invalidate_for_post()` now also clears the `authors` cache (always) and the `archives` cache (specifically for the `post` post type).
- **Rewrite rule timing** — registering the rule on the `init` hook read a stale version of Settings while a Save was still in progress (the REST request hadn't finished yet). Fix: moved to the `generate_rewrite_rules` filter, which runs exactly when the ruleset is regenerated.

---

**Status:** LOCKED.
