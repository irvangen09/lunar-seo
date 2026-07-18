# SCHEMA_MODULE_ARCHITECTURE.md

**Project:** Lunar SEO
**Module:** Schema
**Version:** 1.1 (LOCKED — all 🔶 points confirmed)
**Status:** LOCKED

> This document defines the technical architecture of the Schema module. It follows the pattern already proven in `GENERAL_MODULE_ARCHITECTURE.md` and `SITEMAP_MODULE_ARCHITECTURE.md`. Scope decisions are sourced from `LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md` and the architecture discussion in this conversation.

---

# 0. Confirmed Scope

Based on `LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §9` and prior discussion, the Schema module's Phase 1 covers **5 schema types**, connected via a single `@graph` JSON-LD per page:

| Type | Coverage | Output Context |
|---|---|---|
| `WebSite` | Sitewide, defined once | Every page |
| `Organization` | Sitewide, defined once | Every page |
| `BreadcrumbList` | Per page | Singular (post/page) |
| `Article` | Per post | `post` post type only (see 🔶4) |
| `ImageObject` | Primary/Featured Image only | Post/Page with a featured image |

**Explicitly out of Phase 1 scope** (per the brief & `PROJECT_BRIEF.md — Features NOT Included`):
- Image License (`license`, `acquireLicensePage`, `creator`, `creditText`, `copyrightNotice`) — dropped from the plan entirely, not deferred.
- ImageObject for screenshots inside the article body — Primary Image only.
- `HowTo`, `FAQPage`, `VideoObject` — deferred until there's a clear content need supporting them.

---

# 1. Module Structure

```text
modules/schema/
├── Module.php                     → Module bootstrap (same pattern as General/Sitemap)
├── Services/
│   └── SchemaGraphBuilder.php     → Orchestrator: gathers the relevant nodes per context, assembles them into a single @graph
├── Nodes/                         → One class per schema type, each producing a node array ready for @graph
│   ├── NodeInterface.php          → Contract: get_node(): ?array (null = skip, not applicable in this context)
│   ├── WebSiteNode.php
│   ├── OrganizationNode.php
│   ├── BreadcrumbListNode.php
│   ├── ArticleNode.php
│   └── ImageObjectNode.php        → Used internally by ArticleNode (see §5.5), NOT a standalone top-level node
├── Frontend.php                   → Orchestrator: hooks wp_head, calls SchemaGraphBuilder, outputs <script type="application/ld+json">
└── Assets.php                     → Empty/not needed — NO Settings page (see §2)
```

**No `Settings/`, no `Admin.php`, and no React app** — see the decision in §2.

**No `Editor.php`** — every schema node is fully derived from data that already exists (post title, featured image, category, author, date). There's no new field that needs to be filled in manually per post, so there's no need for Gutenberg integration. This is consistent with the *lightweight* philosophy in `PRODUCT_VISION.md §4`.

---

# 2. 🔶 DECISION — No Settings Page

## Analysis

Unlike General (7 settings sections) and Sitemap (4 settings sections), all the data needed by the 5 Schema nodes above is **already available automatically** from:
- The `SiteIdentity` Shared Service (§3) → `WebSite`, `Organization`
- Native WordPress post data (title, category, featured image, date, author) → `Article`, `BreadcrumbList`, `ImageObject`

There's no decision that genuinely needs to be handed off to the user for Phase 1 (no "Enable/Disable per type" option was requested in any brief).

## Recommendation: Build no Settings page at all for Phase 1

**Rationale:**
- `ENGINEERING_PRINCIPLES.md #1 — Write with Purpose`: *"Never write code just because you know how."* Building an empty/minimal Settings page (just on/off toggles) with no real need is a feature with no clear purpose.
- `DESIGN_SYSTEM.md §14`: *"Avoid... excessive settings."*
- If a toggle turns out to be needed later (e.g. "Disable BreadcrumbList"), adding `Settings/` + `Admin.php` + a small React app down the line **requires no architectural change** — the module is already isolated (`ARCHITECTURE.md §18 — Extensibility`).

**Trade-off you should be aware of:** if you later want to disable one schema type without disabling the whole module, that isn't possible without additional coding in this Phase 1. If you think this is a realistic near-term need, let me know — I can add a simple toggle (not a full Settings page, just one option in General, or even hardcode everything as active) before this is LOCKED.

## ✅ Confirmed

No Settings page in Phase 1. Short rationale: none of the five schema nodes (WebSite, Organization, BreadcrumbList, Article/WebPage, ImageObject) have a single decision that reasonably needs to be handed to the user — everything is *fully derived* from existing data (Site Identity, post title, category, featured image, date). Building a UI for something with no actual choices only adds surface area to maintain with no real benefit (`ENGINEERING_PRINCIPLES.md #1 — Write with Purpose`), and the module can still be extended with a toggle at any time later without changing the foundation (`ARCHITECTURE.md §18 — Extensibility`) — so this isn't a decision that closes any doors, just one that defers the cost until it's actually proven necessary.

---

# 3. 🔶 DECISION — SiteIdentity Shared Service

Per the earlier discussion in this conversation. Final summary:

## 3.1 Location & Structure

```text
includes/Services/SiteIdentity.php
```

Namespace: `Lunar\SEO\Services\SiteIdentity` (alongside the existing `Lunar\SEO\Services\OptionManager`).

## 3.2 Data Moved from General

| Field | From (General) | To (Shared Service) | Used by Schema for |
|---|---|---|---|
| `website_name` | `lunar_seo_general_settings.site_info.website_name` | `lunar_seo_site_identity.website_name` | `Organization.name`, `WebSite.name` |
| `alternate_website_name` | `lunar_seo_general_settings.site_info.alternate_website_name` | `lunar_seo_site_identity.alternate_website_name` | `Organization.alternateName` |
| `site_image_id` | `lunar_seo_general_settings.site_info.site_image_id` | `lunar_seo_site_identity.site_image_id` | `Organization.logo` (as a nested ImageObject) |

**Stays in General, NOT moved:** `title_separator` — purely a `<title>` formatting need, not relevant to Schema.

## 3.3 New Storage Option

```
lunar_seo_site_identity   (autoload = yes, same reasoning as General — read on every frontend request)
```

## 3.4 Fallback Read (as agreed)

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
     * Read the field from the NEW location (lunar_seo_site_identity).
     * If empty/never set, fall back to reading from the OLD location
     * (lunar_seo_general_settings.site_info) - bridging data the user
     * already filled in before SiteIdentity existed, WITHOUT a
     * migration/copy process that would need a separate activation
     * hook.
     *
     * Once the user re-saves via the Site Info UI (General), General's
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

## 3.5 Changes to the General Module (Minimal Change — 4 spots)

| File | Change |
|---|---|
| `modules/general/Services/PlaceholderResolver.php` | `get_site_name()`: reads via `SiteIdentity::get_website_name()`, not `OptionManager` directly |
| `modules/general/Renderers/OpenGraphRenderer.php` | ~Line 199: `site_image_id` via `SiteIdentity::get_site_image_id()` |
| `modules/general/Renderers/TwitterCardRenderer.php` | ~Line 178: same as above |
| `modules/general/Settings/Settings.php` | REST *save* handler: the `website_name`/`alternate_website_name`/`site_image_id` fields are written via `SiteIdentity::set()`, no longer stored in `lunar_seo_general_settings` |

**No changes** to: `SiteInfo.php` (field schema/sanitization stay exactly the same — only the final *storage destination* differs; sanitization still happens here before being passed on to `SiteIdentity`), the React admin app (`src/admin/app.js` — the REST payload shape is unchanged), `Editor.php`, and every other Renderer/Service that doesn't touch `website_name`/`site_image_id`.

**Constructor Injection**: `PlaceholderResolver`, `OpenGraphRenderer`, `TwitterCardRenderer`, and General's `Settings.php` each receive `SiteIdentity` via their constructor — the same pattern already used for `OptionManager`, with no Service Locator (`GENERAL_MODULE_ARCHITECTURE.md §8`, LOCKED).

---

# 4. 🔶 DECISION — `Article` Node Scope: Post Only, or Post+Page?

The brief only mentions *"Article (per post)"*. But the General module supports Content settings for Post **and** Page equally. It needs to be decided how Page is handled.

## Options

| Option | Description | Trade-off |
|---|---|---|
| **A. Article for `post` only, Page gets no schema node beyond WebSite/Organization/ImageObject** | Matches the brief literally | Page (e.g. an "About" or "Contact" page) gets no rich-result benefit from Article, even though it might be representative |
| **B. Article for `post`, `WebPage` (a generic schema type) for `page`** (**recommended**) | Adds one small node type; `WebPage` only needs basic fields (name, url, isPartOf → WebSite) | Slightly more work (one small class), but Page still gets a valid schema representation without forcing `Article` (which is technically incorrect for non-article pages like "Contact") |

## Recommendation: **Option B**

**Rationale:** Your site is dominated by documentation/wiki pages (category = game, article = walkthrough) — likely built mostly on the `post` post type, but WordPress always has at least a few `Page`s (a static homepage, About, etc.). Leaving Page **with no schema node at all** beyond Organization/WebSite feels like an unnecessary gap, when `WebPage` is the most basic and "safest" node in Schema.org — it needs no extra fields beyond what's already available (`name`, `url`), and carries none of the *"non-representative structured data"* risk that was the main concern in your ImageObject brief.

If you feel Page doesn't need any schema at all in Phase 1 (e.g. because static pages on your site are few and low priority), Option A is also valid — let me know your preference.

## ✅ Confirmed

**Option B adopted** — `ArticleNode` for the `post` post type, `WebPageNode` for the `page` post type.

---

# 5. Schema Graph Strategy (`@graph` JSON-LD)

## 5.1 Decision

One `<script type="application/ld+json">` per page, containing a single `@graph` with multiple nodes cross-referenced via `@id` (rather than separate `<script>` tags per type). This is the standard pattern used by other major SEO plugins and is recommended by Google, since it reduces duplicate entity references.

## 5.2 `@id` Scheme per Node

| Node | `@id` |
|---|---|
| `WebSite` | `{home_url}/#website` |
| `Organization` | `{home_url}/#organization` |
| `BreadcrumbList` | `{permalink}#breadcrumb` |
| `Article` / `WebPage` | `{permalink}#article` or `{permalink}#webpage` |
| `ImageObject` (Primary) | `{permalink}#primaryimage` |

Cross-node references use these `@id`s (e.g. `Article.publisher` → `{"@id": "{home_url}/#organization"}`, rather than repeating the entire Organization object).

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

Each `Node` class decides for itself, via `get_node()`, whether it's *applicable* in the current context (e.g. `ArticleNode::get_node()` returns `null` when it's not `is_singular('post')`) — the orchestrator doesn't need to know per-type logic, it's a pure aggregator (the same pattern as General's `Frontend.php`, a *"thin orchestrator"*, `GENERAL_MODULE_ARCHITECTURE.md §6.1`).

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

`wp_json_encode()` (not native `json_encode()`) per `ARCHITECTURE.md §16` — it automatically handles WordPress' security filters. If `@graph` is empty (e.g. a 404/search page where no schema applies), **no `<script>` is printed at all** — consistent with `ARCHITECTURE.md §10`.

## 5.5 Per-Node Detail

**`WebSiteNode`** — `name` (from `SiteIdentity::get_website_name()`), `url` (`home_url()`), `publisher` (`@id` → Organization). Output on every page.

**`OrganizationNode`** — `name`, `alternateName` (skipped if empty), `url`, `logo` as a nested `ImageObject` (`url`, `width`, `height` from `wp_get_attachment_image_src( $site_image_id, 'full' )`). If `site_image_id` is 0 (not set), the `logo` field is skipped entirely (not rendered empty) — consistent with General's `Skip-output Condition` pattern (`§6.5`). Output on every page.

**`BreadcrumbListNode`** — for `post`: a chain from the primary category (`get_ancestors()` on the post's first category term, supporting both flat and nested structures) → Home. For `page`: a `get_post_ancestors()` chain (parent page). Each item: `position`, `name`, `item` (URL). Output only in a singular context.

**`ArticleNode`** (for `post`) / **`WebPageNode`** (for `page`, per 🔶4): `headline` (the post's original title, **not truncated** — Google recommends ≤110 characters but doesn't require it; truncating risks cutting off meaning, so it's left in full per `ENGINEERING_PRINCIPLES.md — avoid over-engineering on assumptions that weren't requested`), `datePublished`/`dateModified` (`post_date`/`post_modified`, ISO 8601 format via `get_post_datetime()`), `author` (`Person`, `name` from `get_the_author_meta('display_name')` — no `url` in Phase 1, not requested), `image` (`@id` → ImageObject if there's a featured image), `publisher` (`@id` → Organization), `isPartOf` (`@id` → WebSite).

**`ImageObjectNode`** — **not a standalone top-level node**, called internally by `ArticleNode`/`WebPageNode` to build the nested `image` object. Sourced from `get_post_thumbnail_id()` → `wp_get_attachment_image_src( $id, 'full' )` for `url`/`width`/`height`. **No `license`/`creator`/`creditText`/`copyrightNotice` fields at all**, per `LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §5` (LOCKED in its source document). If a post has no featured image, the `image` field is skipped in the parent node — no empty ImageObject is ever forced in.

---

# 6. Dependency Injection

Same as General & Sitemap: every `Node` class and `SchemaGraphBuilder` receive their dependencies (`SiteIdentity`, `OptionManager` where needed) via Constructor Injection. No Service Locator (`GENERAL_MODULE_ARCHITECTURE.md §8`, same pattern in `SITEMAP_MODULE_ARCHITECTURE.md`).

---

# 7. Decision Summary

| # | Decision | Final Decision | Status |
|---|---|---|---|
| 🔶 1 | Phase 1 schema type scope | WebSite, Organization, BreadcrumbList, Article, ImageObject (Primary) | ✅ Confirmed |
| 🔶 2 | Schema Settings page | **None** in Phase 1 — fully automatic | ✅ Confirmed |
| 🔶 3 | SiteIdentity Shared Service + Fallback Read | Implemented as in §3 | ✅ Confirmed |
| 🔶 4 | Article scope: Post only vs. Post+Page (via WebPage) | **Option B** — `WebPageNode` for Page | ✅ Confirmed |

---

**Status:** LOCKED — all architectural decisions confirmed, ready to begin phased implementation.
