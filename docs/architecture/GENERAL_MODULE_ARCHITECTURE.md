# GENERAL_MODULE_ARCHITECTURE.md

**Project:** Lunar SEO
**Module:** General
**Version:** 1.2
**Status:** LOCKED
**Document Relationship:** Addendum to `ARCHITECTURE.md` and `PLUGIN_BLUEPRINT.md`. Does not replace them, only clarifies how the global architecture is applied specifically to the General module.

> This document defines the technical architecture of the General module as an implementation foundation. If this document conflicts with `ARCHITECTURE.md`, `PLUGIN_BLUEPRINT.md`, `CODING_STANDARD.md`, or `ENGINEERING_PRINCIPLES.md`, those documents take priority.

---

# 0. Revision History

This document was previously named `SEO_MODULE_ARCHITECTURE.md` under the **GameStuff Core** project, covering a module named **SEO**. Following the rebrand and module-structure simplification decision, all references were revised as follows:

| Item | Old | New |
|---|---|---|
| Project/Product | GameStuff Core | **Lunar SEO** |
| Brand | GameStuff | **Lunar** |
| Plugin Slug | `gamestuff-core` | **`lunar-seo`** |
| Module Name | SEO | **General** |
| Module Namespace | `GameStuff\Core\Modules\SEO` | **`Lunar\SEO\Modules\General`** |
| Module Folder | `modules/seo/` | **`modules/general/`** |
| Option Prefix | `gamestuff_core_` | **`lunar_seo_`** |
| Module Option Name | `gamestuff_core_seo_settings` | **`lunar_seo_general_settings`** |

**No changes to the architecture, roadmap, or technical decisions** — the module's full scope (Site Info, Content, Categories & Tags, Social, Verification, Robots & URL, Editor) is preserved in full. The Breadcrumb module that was previously planned as a separate module has been **removed** from the roadmap; the remaining modules are: **General, Sitemap, Schema**.

**Revision v1.1 (§7.1 correction):** §7.1 previously stated that data was accessed via `/wp/v2/settings` (WordPress' built-in generic REST API). This is **no longer accurate**, following the discovery of a bug: that generic endpoint fails to save nested object data even though the request appears to succeed. The actual implementation (and the standard pattern for every module since then, including Sitemap) uses a **custom REST route** per module. The §7.1 text has been corrected so the document remains an accurate Single Source of Truth — there is no change in code behavior, purely a documentation sync with the implementation already in place.

**Revision v1.2 (§6.3 correction):** §6.3 previously stated the Renderer contract method is named `render(): void`. The actual implementation uses `init(): void` instead — `TitleRenderer` needs to hook into `pre_get_document_title` (which fires before `wp_head`), while the other Renderers hook into `wp_head` directly; naming the shared method `init()` reflects that every Renderer's job is to *register* its own hook, not to render output immediately when called. This is a documentation sync only — no change in code behavior.

---

# 1. Decision Scope

This document covers 5 architectural decisions for the General module:

1. Module Structure
2. Option Data Model
3. Editor Integration Strategy
4. Auto-generate Service
5. Frontend Output Renderer

Features explicitly **excluded** from the General module's scope:

- Pinterest Verification
- AI-assist / AI Writing (auto-generated title/description is rule-based, not generative AI)
- A Schema tab in the Editor (Schema is a separate module per `PROJECT_BRIEF.md`/the revision document and `ARCHITECTURE.md` §4)

---

# 2. Module Structure

## 2.1 Decision

The internal structure of the General module is split by **responsibility**, not by UI section, following a subfolder pattern so each file stays small and maintainable as the plugin grows long-term.

## 2.2 Directory Structure

```text
modules/general/
├── Module.php                    → Module bootstrap (registers with the Module Registry)
├── Settings/
│   ├── Settings.php              → Orchestrator: registration via the Settings API
│   ├── SiteInfo.php               → Field schema + sanitization: Site Info
│   ├── Content.php                → Field schema + sanitization: Homepage/Post/Page/Search/404
│   ├── CategoriesTags.php        → Field schema + sanitization: Categories & Tags
│   ├── Social.php                 → Field schema + sanitization: Open Graph & Twitter Card
│   ├── Verification.php          → Field schema + sanitization: Google/Bing/Yandex
│   └── RobotsUrl.php             → Field schema + sanitization: Robots Default & URL
├── Services/
│   ├── TitleResolver.php         → Generates the SEO Title from the template + placeholders
│   ├── DescriptionGenerator.php  → Generates a fallback Meta Description (excerpt-based)
│   └── PlaceholderResolver.php   → Resolves {title}, {site_name}, {tagline}, {separator}, {query}, {term_title}
├── Renderers/
│   ├── TitleRenderer.php         → <title> tag
│   ├── MetaRenderer.php          → meta description, robots meta, canonical
│   ├── OpenGraphRenderer.php     → og:title, og:description, og:image, etc.
│   ├── TwitterCardRenderer.php   → twitter:card, twitter:title, etc.
│   └── VerificationRenderer.php  → verification meta tags
├── Admin.php                     → Registers the menu + renders the React admin app's root container
├── Editor.php                    → Editor integration (Gutenberg sidebar/panel)
├── Frontend.php                  → <head> output orchestrator (hooks wp_head)
├── Assets.php                    → Asset enqueueing (admin/editor/frontend, separated per context)
└── Helpers.php                   → Generic, non-generation utilities (not a place for business logic)
```

## 2.3 Rationale

- **One `Settings/` file per section**: each file only handles one section (field schema + sanitization), with `Settings.php` as a thin orchestrator. Keeps Single Responsibility and makes reviewing each part easier.
- **`Editor.php` kept separate from `Admin.php`**: different runtime contexts (Settings API/REST vs. Gutenberg/REST), different WordPress integration patterns.
- **`Services/` and `Renderers/` kept separate from `Helpers.php`**: `Helpers.php` is reserved for small generic utilities, not generation/render business logic.
- **Not yet promoted to Shared Services**: this logic is specific to the General module. Promotion to Shared Services only happens **once it's proven** to be needed by another module (Sitemap/Schema) in the future — avoiding premature abstraction.

---

# 3. Option Data Model

## 3.1 Decision

Global settings are stored in **`wp_options`** via the **WordPress Settings API**, not a custom database table. Per-post overrides are stored via the **Post Meta API**.

## 3.2 Storage Structure

- **Global Settings**: one option per module, containing every section as a nested array:
  ```
  lunar_seo_general_settings
  ```
  `autoload = yes` — because it's read on every frontend request to render meta tags, so WordPress preloads this option into memory as a single array without a separate query.

- **Editor Override (per-post)**: via `register_post_meta()`, exposed to the REST API/Gutenberg, with a `sanitize_callback` and `auth_callback` appropriate to the context.

## 3.3 Rationale

Per `ARCHITECTURE.md §12` and `CODING_STANDARD.md §7` (LOCKED — must use the Settings API/Options API). Technically, General's settings data pattern (one config blob, read often — written rarely) is an ideal case for `wp_options`:

| Aspect | `wp_options` | Custom Table |
|---|---|---|
| Caching | Automatic via the WP Object Cache | Must be built manually |
| Compatibility | Automatically compatible with backup/migration tools | Needs its own migration schema |
| Maintenance | No `dbDelta()`/table schema versioning | Must handle create/alter/drop table |
| Performance for this case | 1 query (even 0 if autoloaded) | Separate query, not part of autoload |

A custom table was rejected not because it's impossible, but because it adds long-term maintenance overhead with no real benefit for this data pattern (`ENGINEERING_PRINCIPLES.md #5 — Every Feature Has a Cost`).

---

# 4. Editor Integration Strategy

## 4.1 Decision

Uses the **native Gutenberg** pattern — a combination of `PluginSidebar` and `PluginDocumentSettingPanel` — rather than a custom modal popup.

## 4.2 Component Breakdown

| Component | Location | Rationale |
|---|---|---|
| SEO Preview (simulated Google result) | `PluginSidebar` | Needs enough width to be representative |
| Input form (SEO Title, Meta Description, Canonical, Robots override) | `PluginSidebar` | Sits alongside the preview for real-time updates |
| Compact status indicator (optional) | `PluginDocumentSettingPanel` | Quick-glance in the Document sidebar, without forcing the preview into a cramped space |

## 4.3 Rationale

- `DESIGN_SYSTEM.md §14`: *"Avoid wizards, popups, or unnecessary configuration steps."* A full modal overlay is considered closer to the "popup" pattern being avoided.
- Project documentation: no jQuery → supports a modern Gutenberg/REST approach.
- A native sidebar/panel automatically inherits Gutenberg's built-in accessibility and keyboard navigation (`CODING_STANDARD.md §14`).
- Data is stored via `register_post_meta()`, and REST communication uses `@wordpress/data` (the `core/editor` store) — fully WordPress-native, no custom AJAX.
- Fallback: a simple `add_meta_box()` (no jQuery) for compatibility with the Classic Editor/non-block content types.

---

# 5. Auto-generate Service

## 5.1 Decision

The auto-generate logic (Title Resolver, Description Generator, Placeholder Resolver) lives in the General module's `Services/`, executed **at runtime (on-the-fly)** during rendering — not precomputed on `save_post`.

## 5.2 Generation Rules

**SEO Title** — if empty, generated from the content's original title following the applicable template + placeholders (`{title} {separator} {site_name}`, etc.).

**Meta Description** — if empty, generated as a *static excerpt* with this fallback order:

1. `has_excerpt()` — use the manual WordPress excerpt if available.
2. The first meaningful content paragraph (skipping headings/shortcodes/images).
3. Truncated to roughly 155–160 characters without cutting a word mid-way; HTML/shortcodes are stripped before truncation.

> **Architectural note:** Meta description is **not** dynamically generated per user search query. Meta tags are static at crawl time, so there is no technical way for the server to know a user's search query at render time. Adapting the snippet to a search query is already the search engine's responsibility. The plugin's job is simply to provide a static excerpt that's representative of the content.

## 5.3 Rationale for Runtime (not Precompute)

The generation operation is lightweight string manipulation (no extra database query/external API), so its computational cost is small. Precomputing (on `save_post`) adds cache-invalidation complexity with no proportional benefit for such a lightweight operation (`ENGINEERING_PRINCIPLES.md — avoid over-engineering`). Migrating to precompute can be done later without changing the foundation, since the Service is already isolated.

---

# 6. Frontend Output Renderer

## 6.1 Decision

`Frontend.php` acts as a **thin orchestrator** that hooks into `wp_head`, calling separate Renderers per output category.

## 6.2 Renderer Categories & Render Order

1. **Title** — via the `pre_get_document_title`/`document_title_parts` filter (not a manual echo, avoiding conflicts with the theme).
2. **Meta** — meta description, robots meta, canonical.
3. **Open Graph** — only rendered if the "Enable Open Graph" toggle is on.
4. **Twitter Card** — only rendered if the "Enable Twitter Card" toggle is on.
5. **Verification** — only renders a tag for a platform whose field is filled in (empty fields are skipped, no empty tag is rendered).

## 6.3 Contract Between Renderers

Every Renderer has one consistent public method: **`init(): void`** (see Revision v1.2 note below). No formal interface/abstract class is used beyond a shared `RendererInterface` declaring this single method — naming consistency is enough for now (`ARCHITECTURE.md` — avoid abstraction that isn't yet needed).

Every Renderer only **consumes** the output of `TitleResolver`/`DescriptionGenerator`/`PlaceholderResolver` (§5) — it never duplicates generation logic.

## 6.4 Security & Escaping (Required)

- `esc_attr()` for `content=""` attributes.
- `esc_url()` for URLs (canonical, og:image, etc.).
- No raw `echo` of user data without context-appropriate escaping.

Per `ARCHITECTURE.md §16` and `CODING_STANDARD.md §12` (LOCKED).

## 6.5 Skip-output Conditions

Per `ARCHITECTURE.md §10 — "Frontend output is only loaded when needed"`, a Renderer is skipped entirely (not rendered empty) when:

- The related toggle (OG/Twitter) is off in Global Settings.
- The verification field is empty for a given platform.

---

# 7. Admin Settings Page — Rendering Approach

## 7.1 Decision

The General module's Settings page is rendered as a **React app** using `@wordpress/components`, consistent with the Editor Integration approach (§4) — not a classic PHP form (`add_settings_field`/`do_settings_sections`).

`Admin.php` is only responsible for:
- Registering the menu (`admin_menu`)
- Rendering an empty *root container* (`<div id="lunar-seo-general-settings-root">`) for React to mount into

Data is accessed via a **custom REST route** (`lunar-seo/v1/general-settings`), registered through `register_rest_route()` on the `rest_api_init` hook. `register_setting()` is still kept for Settings API compliance (see §3) — however `show_in_rest` is deliberately **disabled** (`false`) so the generic WordPress endpoint (`/wp/v2/settings`) is never used as an access path.

**Why the generic endpoint isn't used:** `/wp/v2/settings` was found to fail at saving nested object data correctly — the request appears to succeed (status 200), but the saved data doesn't match the submitted payload. This is a bug/limitation in that generic endpoint, not in our implementation. A custom REST route per module (pattern: `lunar-seo/v1/{module}-settings`) is now the standard for every module in the plugin, including Sitemap (see `SITEMAP_MODULE_ARCHITECTURE.md §2`).

## 7.2 Rationale

- The mockup UI (dynamic toggle switches, real-time character counters, accordions, media upload modal, insert-variable dropdown) would be hard to achieve with a classic PHP form without heavy JS.
- **Consistent** with the Editor approach — one JS/REST pattern across the whole plugin (`DESIGN_SYSTEM.md §18 — Component Consistency`).
- Still **Settings API compliant** — `register_setting` is still used for sanitization & storage; only the REST access path is custom (avoiding the generic endpoint bug) and the UI rendering approach is modern.

---

# 8. Dependency Injection

## 8.1 Decision

`OptionManager` is passed to every module and component that needs it via **Constructor Injection**, not accessed through a *Service Locator* (`Bootstrap::instance()->get_option_manager()`).

## 8.2 Rationale

A Service Locator creates a hidden dependency on global state, which conflicts with `CODING_STANDARD.md §3`: *"Avoid global state when not necessary."* Dependency Injection makes each class's requirements explicit and easy to trace. Components that don't need `OptionManager` (e.g. `Admin.php`, `Assets.php`) don't receive it — per `ENGINEERING_PRINCIPLES.md #1 — Write with Purpose`.

---

# 9. Decision Summary

| No | Area | Decision |
|---|---|---|
| 1 | Module Structure | Subfolders per responsibility (`Settings/`, `Services/`, `Renderers/`) |
| 2 | Option Data Model | `wp_options` via the Settings API (1 option per module) + Post Meta for overrides |
| 3 | Editor Integration | `PluginSidebar` (preview + form) + `PluginDocumentSettingPanel` (optional indicator) |
| 4 | Auto-generate Service | `Services/`, runtime execution, meta description = static excerpt |
| 5 | Frontend Output Renderer | Orchestrator + Renderer per category, hooks `wp_head` |
| 6 | Admin Settings Page | React app + custom REST route (not `/wp/v2/settings`), consistent with the Editor |
| 7 | Dependency Injection | Constructor Injection, no Service Locator |

---

**Status:** LOCKED
