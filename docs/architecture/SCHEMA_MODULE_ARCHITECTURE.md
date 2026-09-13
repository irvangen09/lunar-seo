# SCHEMA_MODULE_ARCHITECTURE.md

**Project:** Lunar SEO
**Module:** Schema
**Version:** 1.2 (LOCKED — every 🔶 point has been confirmed; see §0.1 for the 1.2 revision)
**Status:** LOCKED

> This document defines the technical architecture of the Schema module. It follows the pattern already proven in `GENERAL_MODULE_ARCHITECTURE.md` and `SITEMAP_MODULE_ARCHITECTURE.md`. Scope decisions are sourced from `LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md` and the architecture discussion in that conversation.

---

# 0.1 Revision 1.2 — Article/WebPage Eligibility Generalized

§4 and §5.5 originally hardcoded `ArticleNode` to the `post` post type and `WebPageNode` to the `page` post type (`is_singular('post')` / `is_singular('page')`). This has been generalized behind the `lunar_seo_supported_post_types` filter — see `LUNAR_SEO_WIKI_INTEGRATION_CONTRACT.md` for the full extension point contract. `ArticleNode` now applies to any post type configured with `schema_node => 'article'`, and `WebPageNode` to any post type configured with `schema_node => 'webpage'`. The default configuration still maps `post → article` and `page → webpage`, so the Option B outcome in §4 is unchanged for sites where nothing registers into the filter — only the mechanism is now extensible instead of hardcoded. §4 and §5.5 below are left as originally decided (historical record of Option A vs. B); the generalization is additive on top of that decision, not a reversal of it.

---

# 0. Confirmed Scope

Based on `LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §9` and the preceding discussion, the Schema module's Phase 1 covers **5 schema types**, connected via a single `@graph` JSON-LD per page:

| Type | Coverage | Output Context |
|---|---|---|
| `WebSite` | Sitewide, defined once | Every page |
| `Organization` | Sitewide, defined once | Every page |
| `BreadcrumbList` | Per page | Singular (post/page) |
| `Article` | Per post | `post` post type only (see 🔶4) |
| `ImageObject` | Primary/Featured Image only | Any post/page with a featured image |

**Explicitly out of scope for Phase 1** (following the brief & `PROJECT_BRIEF.md` — Features NOT Included):
- Image License (`license`, `acquireLicensePage`, `creator`, `creditText`, `copyrightNotice`) — removed entirely from the plan, not merely deferred.
- ImageObject for in-body article screenshots — Primary Image only.
- `HowTo`, `FAQPage`, `VideoObject` — deferred until there's a clear content need supporting them.

---

# 1. Module Structure

```text
modules/schema/
├── Module.php                     → Module bootstrap (same pattern as General/Sitemap)
├── Services/
│   └── SchemaGraphBuilder.php     → Orchestrator: collect the relevant nodes per context, assemble into a single @graph
├── Nodes/                         → One class per schema type, each class produces an array node ready for the @graph
│   ├── NodeInterface.php          → Contract: get_node(): ?array (null = skip, not applicable in this context)
│   ├── WebSiteNode.php
│   ├── OrganizationNode.php
│   ├── BreadcrumbListNode.php
│   ├── ArticleNode.php
│   └── ImageObjectNode.php        → Used internally by ArticleNode (see §5.5), NOT a standalone top-level node
├── Frontend.php                   → Orchestrator: hooks wp_head, calls SchemaGraphBuilder, outputs <script type="application/ld+json">
└── Assets.php                     → Empty/not needed — there is NO Settings page (see §2)
```

**No `Settings/`, `Admin.php`, and no React app** — see the §2 decision.

**No `Editor.php`** — every schema node is fully derived from data that already exists (post title, featured image, category, author, date). There's no new field that needs to be filled in manually per post, so there's no need for Gutenberg integration. This is consistent with the *lightweight* philosophy in `PRODUCT_VISION.md` §4.

---

# 2. 🔶 DECISION — No Settings Page

## Analysis

Unlike General (7 settings sections) and Sitemap (4 settings sections), all the data needed by the 5 Schema nodes above is **already available automatically** from:
- The `SiteIdentity` Shared Service (§3) → `WebSite`, `Organization`
- Native WordPress post data (title, category, featured image, date, author) → `Article`, `BreadcrumbList`, `ImageObject`

There's no decision that reasonably needs to be handed to the user for Phase 1 (no "Enable/Disable per type" option requested by any brief).

## Recommendation: Don't build a Settings page at all for Phase 1

**Rationale:**
- `ENGINEERING_PRINCIPLES.md` #1 — Write with Purpose: *"Never write code just because you already know how."* Building an empty/minimal Settings page (just an on/off toggle) without a real need is a feature with no clear purpose.
- `DESIGN_SYSTEM.md` §14: *"Avoid... excessive settings."*
- If a toggle is later found to be genuinely needed (e.g. "Disable BreadcrumbList"), adding `Settings/` + `Admin.php` + a small React app later **requires no architecture change** — the module is already isolated (`ARCHITECTURE.md` §18 — Extensibility).

**Trade-off you should understand:** if you later want to disable one schema type without disabling the whole module, that isn't possible without additional coding in this Phase 1. If you think this is a realistic near-term need, let me know — I can add a simple toggle (not a full Settings page, just one option in General or even hardcode everything as always-on) before this is LOCKED.

## ✅ Confirmed

No Settings page in Phase 1. Brief rationale: none of the five schema nodes (WebSite, Organization, BreadcrumbList, Article/WebPage, ImageObject) have a single decision that reasonably needs to be handed to the user — everything is *fully derived* from data that already exists (Site Identity, post title, category, featured image, date). Building a UI for something with no actual choice only adds surface area to maintain with no real benefit (`ENGINEERING_PRINCIPLES.md` #1 — Write with Purpose), and the module can still be extended with a toggle at any time without changing its foundation (`ARCHITECTURE.md` §18 — Extensibility) — so this isn't a decision that closes any doors, only one that defers the cost until it's actually proven necessary.

---

# 3. 🔶 DECISION — SiteIdentity Shared Service

Per the earlier discussion in that conversation. Final summary:

## 3.1 Location & Structure

```text
includes/Services/SiteIdentity.php
```

Namespace: `Lunar\SEO\Services\SiteIdentity` (alongside the existing `Lunar\SEO\Services\OptionManager`).

## 3.2 Data Moved From General

| Field | From (General) | To (Shared Service) | Used by Schema for |
|---|---|---|---|
| `website_name` | `lunar_seo_general_settings.site_info.website_name` | `lunar_seo_site_identity.website_name` | `Organization.name`, `WebSite.name` |
| `alternate_website_name` | `lunar_seo_general_settings.site_info.alternate_website_name` | `lunar_seo_site_identity.alternate_website_name` | `Organization.alternateName` |
| `site_image_id` | `lunar_seo_general_settings.site_info.site_image_id` | `lunar_seo_site_identity.site_image_id` | `Organization.logo` (as a nested ImageObject) |

**Stays in General, does NOT move:** `title_separator` — purely a `<title>` formatting need, not relevant to Schema.

## 3.3 New Storage Option

```
lunar_seo_site_identity   (autoload = yes, same rationale as General — read on every frontend request)
```

## 3.4 Fallback Read (per the agreed approach)

```php
namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SiteIdentity {

    private const OPTION_KEY = 'lunar_seo_site_identity';

    private OptionManager $option_manager;

    public function __construct( OptionManager $option_manager ) {
        $this->option_manager = $option_manager;
    }

    public function get_website_name(): string {
        return $this->get_field( 'website_name', '' );
    }

    public function get_alternate_website_name(): string {
        return $this->get_field( 'alternate_website_name', '' );
    }

    public function get_site_image_id(): int {
        return (int) $this->get_field( 'site_image_id', 0 );
    }

    /**
     * Read a field from the NEW location (lunar_seo_site_identity). If
     * it's empty/never been filled in, fall back to reading from the
     * OLD location (lunar_seo_general_settings.site_info) - bridging
     * data users already filled in before SiteIdentity existed,
     * WITHOUT a migration/copy process that would need a separate
     * activation hook.
     *
     * Once a user re-saves via the Site Info UI (General), General's
     * REST handler writes to the NEW location (see §3.5), so this
     * fallback naturally stops being used for that field.
     */
    private function get_field( string $key, $default ) {
        $new = get_option( self::OPTION_KEY, [] );

        if ( isset( $new[ $key ] ) && '' !== $new[ $key ] ) {
            return $new[ $key ];
        }

        return $this->option_manager->get( 'general', 'site_info', $key, $default );
    }

    public function set( array $data ): bool {
        $current = get_option( self::OPTION_KEY, [] );

        return update_option( self::OPTION_KEY, array_merge( $current, $data ), true );
    }
}
```

## 3.5 Changes to the General Module (Minimal Change — 4 points)

| File | Change |
|---|---|
| `modules/general/Services/PlaceholderResolver.php` | `get_site_name()`: read via `SiteIdentity::get_website_name()`, not `OptionManager` directly |
| `modules/general/Renderers/OpenGraphRenderer.php` | Line ~199: `site_image_id` via `SiteIdentity::get_site_image_id()` |
| `modules/general/Renderers/TwitterCardRenderer.php` | Line ~178: same as above |
| `modules/general/Settings/Settings.php` | REST *save* handler: the `website_name`/`alternate_website_name`/`site_image_id` fields are now written via `SiteIdentity::set()`, no longer into `lunar_seo_general_settings` |

**No change** to: `SiteInfo.php` (field schema/sanitization stays exactly the same — only the final *storage destination* differs, sanitization still happens here before being passed to `SiteIdentity`), the React admin app (`src/admin/app.js` — the REST payload shape is unchanged), `Editor.php`, and every other Renderer/Service that doesn't touch `website_name`/`site_image_id`.

**Constructor Injection**: `PlaceholderResolver`, `OpenGraphRenderer`, `TwitterCardRenderer`, `Settings.php` (General) each receive `SiteIdentity` via the constructor — identical pattern to how `OptionManager` works today, no Service Locator (`GENERAL_MODULE_ARCHITECTURE.md` §8, LOCKED).

---

# 4. 🔶 DECISION — `Article` Node Coverage: Post Only, or Post+Page?

The brief only mentions *"Article (per post)"*. But the General module supports Content settings for Post **and** Page equally. It needs to be decided how Page is treated.

## Options

| Option | Description | Trade-off |
|---|---|---|
| **A. Article only for `post`, Page gets no schema node beyond WebSite/Organization/ImageObject** | Matches the brief literally | A Page (e.g. an "About" or "Contact" page) gets no rich-result benefit from Article at all, even though it might be representative |
| **B. Article for `post`, `WebPage` (a generic schema type) for `page`** (**recommended**) | Adds one small new node type; `WebPage` only needs basic fields (name, url, isPartOf → WebSite) | A little extra work (1 small class), but Page still gets a valid schema representation without forcing `Article` onto it (which is technically incorrect for a non-article page like "Contact") |

## Recommendation: **Option B**

**Rationale:** Your site is dominated by documentation/wiki pages (category = game, article = walkthrough) — likely mostly the `post` post type, but WordPress always has at least a few `Page`s (a Homepage if using a static page, About, etc.). Leaving Page with **no schema node at all** beyond Organization/WebSite feels like an unnecessary gap, when `WebPage` is the most basic and "safest" node in Schema.org — it needs no extra fields beyond what's already available (`name`, `url`), and carries none of the *"structured data isn't representative"* risk that was the main concern in your ImageObject brief.

If you feel Page doesn't need any schema at all in Phase 1 (e.g. because static pages on your site are few and not a priority), Option A is also valid — let me know your preference.

## ✅ Confirmed

**Option B is applied** — `ArticleNode` for the `post` post type, `WebPageNode` for the `page` post type.

---

# 5. Schema Graph Strategy (`@graph` JSON-LD)

## 5.1 Decision

One `<script type="application/ld+json">` per page, containing a **single `@graph`** with several nodes connected via `@id` (not several separate `<script>` tags per type). This is the standard pattern used by major SEO plugins (Yoast, RankMath) and recommended by Google because it reduces duplicate entity references.

## 5.2 `@id` Scheme per Node

| Node | `@id` |
|---|---|
| `WebSite` | `{home_url}/#website` |
| `Organization` | `{home_url}/#organization` |
| `BreadcrumbList` | `{permalink}#breadcrumb` |
| `Article` / `WebPage` | `{permalink}#article` or `{permalink}#webpage` |
| `ImageObject` (Primary) | `{permalink}#primaryimage` |

Cross-node references use this `@id` (example: `Article.publisher` → `{"@id": "{home_url}/#organization"}`, instead of repeating the entire Organization object).

## 5.3 `SchemaGraphBuilder` — Orchestrator

```php
final class SchemaGraphBuilder {

    /** @var NodeInterface[] */
    private array $nodes;

    public function __construct( array $nodes ) {
        $this->nodes = $nodes;
    }

    public function build(): array {
        $graph = [];

        foreach ( $this->nodes as $node ) {
            $result = $node->get_node();

            if ( null !== $result ) {
                $graph[] = $result;
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];
    }
}
```

Each `Node` class decides for itself, via `get_node()`, whether it's *applicable* in the current context (e.g. `ArticleNode::get_node()` returns `null` if not `is_singular('post')`) — the orchestrator doesn't need to know per-type logic, it's a pure aggregator (same pattern as General's `Frontend.php`, which is a *"thin orchestrator"*, `GENERAL_MODULE_ARCHITECTURE.md` §6.1).

## 5.4 Output & Escaping

```php
// Frontend.php
$graph = $this->graph_builder->build();

if ( ! empty( $graph['@graph'] ) ) {
    printf(
        '<script type="application/ld+json">%s</script>' . "\n",
        wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
    );
}
```

`wp_json_encode()` (not native `json_encode()`) per `ARCHITECTURE.md` §16 — automatically applies WordPress's security filters. If `@graph` is empty (e.g. on a 404/search page with no applicable schema), **no `<script>` tag is printed at all** — consistent with `ARCHITECTURE.md` §10.

## 5.5 Per-Node Detail

**`WebSiteNode`** — `name` (from `SiteIdentity::get_website_name()`), `url` (`home_url()`), `publisher` (`@id` → Organization). Output on every page.

**`OrganizationNode`** — `name`, `alternateName` (skipped if empty), `url`, `logo` as a nested `ImageObject` (`url`, `width`, `height` from `wp_get_attachment_image_src( $site_image_id, 'full' )`). If `site_image_id` = 0 (never filled in), the `logo` field is skipped entirely (not rendered empty) — consistent with General's `Skip-Output Conditions` pattern (§6.5). Output on every page.

**`BreadcrumbListNode`** — for `post`: a chain from the post's primary category (`get_ancestors()` on the first category term, supporting both flat and nested structures) → Home. For `page`: a `get_post_ancestors()` chain (parent pages). Each item: `position`, `name`, `item` (URL). Output only in a singular context.

**`ArticleNode`** (for post types configured with `schema_node => 'article'`, `post` by default) / **`WebPageNode`** (for post types configured with `schema_node => 'webpage'`, `page` by default — see §0.1 for how this generalized from the original `post`/`page`-only design in §4): `headline` (the post's original title, **not truncated** — Google recommends ≤110 characters but doesn't require it; truncating risks cutting off meaning, so it's left full per `ENGINEERING_PRINCIPLES.md` — avoid over-engineering on assumptions that weren't asked for), `datePublished`/`dateModified` (`post_date`/`post_modified`, ISO 8601 format via `get_post_datetime()`), `author` (`Person`, `name` from `get_the_author_meta('display_name')` — no `url` in Phase 1, not requested), `image` (`@id` → ImageObject if a featured image exists), `publisher` (`@id` → Organization), `isPartOf` (`@id` → WebSite).

**`ImageObjectNode`** — **not a standalone top-level node**, called internally by `ArticleNode`/`WebPageNode` to build the nested `image` object. Sourced from `get_post_thumbnail_id()` → `wp_get_attachment_image_src( $id, 'full' )` for `url`/`width`/`height`. **No `license`/`creator`/`creditText`/`copyrightNotice` field** at all, per `LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md` §5 (LOCKED in its source document). If a post has no featured image, the parent node's `image` field is skipped — no empty ImageObject is forced in.

---

# 6. Dependency Injection

Same as General & Sitemap: every `Node` class and `SchemaGraphBuilder` receive their dependencies (`SiteIdentity`, `OptionManager` where needed) via Constructor Injection. No Service Locator (`GENERAL_MODULE_ARCHITECTURE.md` §8, same pattern as `SITEMAP_MODULE_ARCHITECTURE.md`).

---

# 7. Decision Summary

| # | Decision | Final Decision | Status |
|---|---|---|---|
| 🔶 1 | Phase 1 schema type scope | WebSite, Organization, BreadcrumbList, Article, ImageObject (Primary) | ✅ Confirmed |
| 🔶 2 | Schema Settings page | **None** in Phase 1 — fully automatic | ✅ Confirmed |
| 🔶 3 | SiteIdentity Shared Service + Fallback Read | Implemented as in §3 | ✅ Confirmed |
| 🔶 4 | Article coverage: Post only vs. Post+Page (via WebPage) | **Option B** — `WebPageNode` for Page | ✅ Confirmed |

---

**Status:** LOCKED — every architectural decision confirmed, ready to begin phased implementation.