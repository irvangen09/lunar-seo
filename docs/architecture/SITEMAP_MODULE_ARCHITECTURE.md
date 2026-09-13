# SITEMAP_MODULE_ARCHITECTURE.md

**Project:** Lunar SEO
**Module:** Sitemap
**Version:** 1.1 (LOCKED - 3 🔶 points confirmed via live end-to-end testing on stg1.gamestuff.id)
**Status:** LOCKED

> This document defines the technical architecture of the Sitemap module. It follows the pattern already proven in `GENERAL_MODULE_ARCHITECTURE.md` — sections that are simply pattern reuse are described briefly, while the 3 points that are genuinely new (and carry real trade-offs) are marked 🔶 and have been confirmed through real testing (see §9).

---

# 1. Module Structure

```text
modules/sitemap/
├── Module.php                     → Module bootstrap (same pattern as General)
├── Settings/
│   ├── SectionInterface.php       → Section contract (module is separate from General, no cross-dependency)
│   ├── Settings.php               → Orchestrator + custom REST route (same pattern as General)
│   ├── SitemapContent.php         → Field schema: which content types to include + dynamic Custom Post Type/Taxonomy
│   ├── ExcludedItems.php          → Field schema: excluded categories, excluded post IDs
│   ├── Priorities.php             → Field schema: priority value per type + Automatic/Manual mode
│   └── Changefreq.php             → Field schema: changefreq value per type
├── Services/
│   ├── ContentTypeRegistry.php    → Detect Custom Post Types & Custom Taxonomies registered on the site
│   ├── PriorityCalculator.php     → Calculate Automatic-mode Posts priority (see §5 🔶)
│   ├── SitemapCache.php           → Cache generated results + automatic invalidation (see §4 🔶)
│   └── XmlBuilder.php             → Build the XML string (sitemap index / url entries) from raw data
├── Providers/                     → One generic provider per content CATEGORY (not per specific type)
│   ├── ProviderInterface.php      → Contract: get_entries(): array
│   ├── HomepageProvider.php       → Homepage (1 entry)
│   ├── PostTypeProvider.php       → GENERIC - used for any Posts/Pages/CPT (parametrized by post type slug)
│   ├── TaxonomyProvider.php       → GENERIC - used for any Categories/Tags/custom taxonomy (parametrized by taxonomy slug)
│   └── AuthorProvider.php         → Author archive pages
├── Frontend.php                   → Orchestrator: rewrite rules + request interception + XML output
├── Admin.php                      → Menu + React root container (same pattern as General)
└── Assets.php                     → Enqueue assets (same pattern as General)
```

**Why a generic Provider was chosen (not one class per type):**
`PostTypeProvider` and `TaxonomyProvider` accept a post type/taxonomy slug as a parameter, so **Post, Page, or any Custom Post Type (including WooCommerce's `product`, if the site uses it) is automatically handled by the SAME class** — no new class is needed every time a new Custom Post Type/Taxonomy appears. This is consistent with our decision not to hardcode "product" specifically (see the earlier architecture discussion), and aligns with `ARCHITECTURE.md` §18 — Extensibility.

**No `Editor.php`** — unlike the General module, Sitemap has no per-post Gutenberg integration. Content exclusion is handled via **Excluded Items** in Global Settings (an ID list, as documented), not a per-post toggle. This purely follows the documented scope (Scope Discipline — `ENGINEERING_PRINCIPLES.md` #18), not an unfinished feature.

---

# 2. Option Data Model

Follows the exact same pattern as General: **1 option per module** (`lunar_seo_sitemap_settings`), via `OptionManager`, custom REST route `lunar-seo/v1/sitemap-settings`.

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
        'posts'                     => float,  // used ONLY when auto_calculate = false
        'minimum_post_priority'     => float,  // lower bound, ALWAYS applies
        'auto_calculate_post_priority' => bool,
        'static_pages'              => float,
        'categories'                => float,
        'archives'                  => float,
        'tag_pages'                 => float,
        'author_pages'              => float,
        'custom_post_type_default'  => float,  // 1 value for ALL custom post types
        'custom_taxonomy_default'   => float,  // 1 value for ALL custom taxonomies
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

Per a previous decision — the document is the source of truth, with one gap (Authors) filled in consistently with the existing pluralization pattern:

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

Pagination (once Links Per Page is exceeded): `/post-sitemap.xml`, `/post-sitemap2.xml`, `/post-sitemap3.xml`, and so on — as documented.

---

# 4. 🔶 DECISION — Sitemap Generation & Cache Strategy

## Options

| Option | Description | Trade-off |
|---|---|---|
| **A. Pure on-the-fly** | Query the database every time `/post-sitemap.xml` is accessed | Simple, always accurate, BUT heavy for sites with lots of content — search engine crawlers access sitemaps fairly often |
| **B. Transient cache + automatic invalidation** (**recommended**) | The generated result is stored (`set_transient`), reused until relevant content changes | Fast for repeated requests, needs invalidation logic, but not overly complex |
| **C. Generate a static file on disk** | Write a physical `.xml` file to the uploads folder | Fastest, but needs filesystem write permission, adds complexity (race conditions, cleanup) |

## Recommendation: **Option B**

**Rationale:**
- The document itself implies this: *"The sitemap will update automatically whenever content is published, updated, or deleted, **without requiring a manual generation process**."* — this phrasing only makes sense if there's a cache+invalidation mechanism; if it were purely on-the-fly, there would be no "generation process" to speak of at all (it's always automatic by definition).
- `ARCHITECTURE.md` §15 — Performance Strategy: *"Minimize queries."* Querying every post/term every time the sitemap is accessed is wasteful for large sites.
- Option C was rejected because the filesystem complexity isn't worth the benefit for this case (`ENGINEERING_PRINCIPLES.md` #5 — Every Feature Has a Cost).

**Cache invalidation is triggered by:**
- `save_post`, `delete_post`, `trashed_post` → invalidate the sitemap transient for the relevant post type
- `edited_term`, `delete_term`, `created_term` → invalidate the sitemap transient for the relevant taxonomy
- Sitemap Settings saved (via the REST route) → invalidate ALL sitemap transients (safe, since the Include/Exclude toggles can change the whole structure)

**The transient does NOT use an expiration time** (not time-based) — purely event-driven invalidation, so the sitemap is always accurate right when an event occurs, instead of waiting for a timeout.

---

# 5. 🔶 DECISION — Automatic Priority Calculation Formula (Posts)

The document mentions an **Automatic** mode for Posts, but doesn't specify the formula. The following formula is proposed:

## Recommendation: Rank-based Linear Interpolation

```
priority = minimum_post_priority + ( ( posts_priority - minimum_post_priority ) / total_posts ) × ( total_posts - rank + 1 )
```

Where:
- `rank` = the post's order by publish date (1 = the **newest** post)
- `posts_priority` = the value from the "Posts" field (used as a **ceiling**, not ignored — still relevant even when Automatic mode is active)
- `minimum_post_priority` = the lower bound (always applies, as documented)

**Effect:** The newest post gets a priority close to the "Posts" value (e.g. 0.8), the oldest post gets close to "Minimum Post Priority" (e.g. 0.2), decreasing linearly based on **order**, not the date gap — so there's no need for more complex content-age calculation (avoiding over-engineering).

**Rationale for the rank-based approach (rather than age/date-based):**
- Simple and deterministic — no need to decide on an arbitrary "time scale" (months? years?).
- Consistent with `ENGINEERING_PRINCIPLES.md` §17 — Maintainability First.

**An important note you should know:** Google has publicly stated they **ignore** the `<priority>`/`<changefreq>` values for ranking purposes as of a few years ago — this field is practically vestigial from a modern SEO perspective. It's still implemented per the document (since the document explicitly requests it, and some other tools/search engines may still read it), but you should know this won't directly affect Google ranking.

---

# 6. 🔶 DECISION (minor) — Rewrite Rules & XML Output

## Approach

Follows the **Remove Category Base** pattern already built in the General module:

1. Filter `generate_rewrite_rules` → register a rule for every active sitemap (dynamic, based on Settings), mapped to the query var `lunar_seo_sitemap` (example values: `post`, `post2`, `category`, `index`).
2. Hook `template_redirect` → check that query var; if present, call `XmlBuilder` (read from the `SitemapCache` first, generate+store if not cached), set the `Content-Type: application/xml; charset=UTF-8` header, echo the XML, `exit`.
3. Rewrite rules are flushed automatically when Settings are saved.

**Revision note (post live testing):** The initial implementation used `add_rewrite_rule()` on the `init` hook (not the `generate_rewrite_rules` filter). This caused a real bug: because `init` only reads Settings once per request — and during the REST API request that saves Settings, the save hadn't **finished yet** — the rules flushed by `flush_rewrite_rules()` were still the old version, causing `/post-sitemap.xml` to 404 until a second Save. This was fixed by moving rule registration to the `generate_rewrite_rules` filter (the exact same pattern as `UrlRewriter::add_taxonomy_rewrite_rules()` in General), which runs exactly when ruleset regeneration happens, so it always reads the latest Settings. Verified: a single Save is now enough.

---

# 7. Admin Settings Page

Identical pattern to General: React app + custom REST route (`lunar-seo/v1/sitemap-settings`), 4 sections:
- **Sitemap Content** (fixed checkboxes + dynamic Custom Post Type/Taxonomy checkboxes from `ContentTypeRegistry`)
- **Excluded Items** (category checklist + post ID textarea, parsed into an array during sanitization)
- **Priorities** (dropdown per type + Automatic toggle for Posts)
- **Changefreq** (dropdown per type, newly designed since there was no mockup — mirrors the Priorities structure exactly)

---

# 8. Decision Summary

| # | Decision | Recommendation | Status |
|---|---|---|---|
| 🔶 1 | Sitemap cache strategy | Transient + event-driven invalidation (Option B) | ✅ Confirmed live |
| 🔶 2 | Automatic Priority formula | Rank-based linear interpolation | ✅ Confirmed live |
| 🔶 3 | Rewrite rules & output | `generate_rewrite_rules` filter, same pattern as Category Base | ✅ Confirmed live (after 1 bug fix revision, see §6) |

---

# 9. End-to-End Test Results (stg1.gamestuff.id)

Every scenario below has been tested directly on the server and **passed**:

- Basic structure (`/sitemap.xml` + every per-content-type sitemap) — homepage, post, page, category, tags, authors, archives, custom post type/taxonomy.
- Dynamic Sitemap Content (Include toggle per type takes effect on `/sitemap.xml` immediately after 1 Save).
- Excluded Items (excluded categories/posts consistently absent).
- Priorities & Changefreq (including hourly/always/etc. value changes on Post, Homepage, Category).
- Include Archives — confirmed as assumed (monthly archives, `/YYYY/MM/`).
- Event-driven cache invalidation — publishing/deleting a post, a new/removed author, a new archive month, all reflected automatically without any manual regeneration.
- Links Per Page pagination (`post-sitemap2.xml` etc. appear automatically once content exceeds the limit).

Two bugs were found & fixed during this process (referenced here in case the Schema module needs a similar pattern):
- **Incomplete cache invalidation** — the `authors`/`archives` cache wasn't being invalidated when a post changed, even though both are derived from post data. Fix: `SitemapCache::invalidate_for_post()` now also clears the `authors` cache (always) and the `archives` cache (specifically for the `post` post type).
- **Rewrite rule timing** — registering the rule on the `init` hook read an old version of Settings while a Save was in progress (the REST request hadn't finished). Fix: moved to the `generate_rewrite_rules` filter, which runs exactly when ruleset regeneration occurs.

---

**Status:** LOCKED.