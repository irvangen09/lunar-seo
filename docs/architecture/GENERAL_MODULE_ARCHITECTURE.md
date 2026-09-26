# GENERAL_MODULE_ARCHITECTURE.md

**Project:** Lunar SEO
**Module:** General
**Version:** 1.3
**Status:** LOCKED
**Document Relationship:** Addendum to `ARCHITECTURE.md` and `PLUGIN_BLUEPRINT.md`. Does not replace them, only clarifies how the global architecture applies specifically to the General module.

> This document defines the technical architecture of the General module as an implementation foundation. Where this document conflicts with `ARCHITECTURE.md`, `PLUGIN_BLUEPRINT.md`, `CODING_STANDARD.md`, or `ENGINEERING_PRINCIPLES.md`, those documents take priority.

---

# 0. Revision History

This document was previously named `SEO_MODULE_ARCHITECTURE.md` under the **GameStuff Core** project, covering a module named **SEO**. Following the rebranding and module-structure simplification decisions, every reference was revised as follows:

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

**No architecture, roadmap, or technical decisions changed** — the full module content (Site Info, Content, Categories & Tags, Social, Verification, Robots & URL, Editor) was fully preserved. The previously separately-planned Breadcrumb module was **removed** from the roadmap; the remaining modules are: **General, Sitemap, Schema**.

**Revision 1.1 (§7.1 correction):** §7.1 previously stated that data is accessed via `/wp/v2/settings` (WordPress's built-in generic REST API). This is **no longer accurate** since a bug was discovered: the generic endpoint fails to save nested object data even though the request appears to succeed. The actual implementation (and the standard pattern for every module since then, including Sitemap) uses a **custom REST route** per module. The §7.1 text was corrected so the document remains an accurate Single Source of Truth — there is no behavior change in the code, purely a documentation sync against the implementation already in production.

---

# 0.1 Revision 1.2 — Renderer Contract Corrected (§6.3)

§6.3 originally specified that each Renderer would expose a single `render(): void` method, with no formal interface — naming consistency alone was judged sufficient. The shipped implementation does not match that, and hasn't for some time: every Renderer implements `RendererInterface`, whose single method is `init(): void`.

The reason the original design didn't survive contact with the code is a real constraint, not drift for its own sake: the Renderers don't all attach to WordPress at the same point. `TitleRenderer` must register on the `pre_get_document_title`/`document_title_parts` filter (§6.2 item 1), which has to be in place before `wp_head` runs at all, while the other four attach to `wp_head` at their own priorities. A single `render(): void` called directly by the orchestrator cannot express that difference — so each Renderer registers its own hook inside `init()`, and the orchestrator stays thin by only calling `init()` on each one, without knowing where any of them attach.

The formal interface earns its place for the same reason: with the orchestrator holding a mixed array of Renderers and calling one method on each, the contract is doing real work rather than being decorative. §6.1 (thin orchestrator), §6.2 (render order), §6.4 (escaping), and §6.5 (skip conditions) are all unaffected. This document had fallen behind the code, not the other way around; §6.3 now describes what actually ships.

---

# 0.2 Revision 1.3 — Future Trigger Recorded for the Uniform Constructor (§8.3)

Every module (`General`, `Sitemap`, `Schema`) and `ModuleRegistry` share the same 4-parameter constructor (`OptionManager, SiteIdentity, AdminMenu, SupportedPostTypes`). Flagged during the Progressive Clean Code Audit as a "shotgun surgery" risk if a 5th Shared Service is ever added — every one of those call sites would need editing at once.

No 5th Shared Service exists or is planned as of this revision, so refactoring now would mean designing a bundling shape (a `SharedServices` value object, most likely) against a guess rather than an actual need — real cost today for a benefit that may never materialize, or may not match the shape actually needed when it does (`ENGINEERING_PRINCIPLES.md` #1). Left unaddressed with no marker, though, this is exactly the kind of item a busy future session skips past without noticing. §8.3 now records the trigger and the intended shape, so if the day comes, it's an execution decision, not a design discussion.

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
- AI-assist / AI Writing (auto-generate title/description is rule-based, not generative AI)
- Schema tab in the Editor (Schema is a separate module per `PROJECT_BRIEF.md`/its revision document and `ARCHITECTURE.md` §4)

---

# 2. Module Structure

## 2.1 Decision

The General module's internal structure is split by **responsibility**, not by UI section, following a subfolder pattern so each file stays small and maintainable as the plugin grows long-term.

## 2.2 Directory Structure

```text
modules/general/
├── Module.php                    → Module bootstrap (registration to the Module Registry)
├── Settings/
│   ├── Settings.php               → Orchestrator: registration via the Settings API
│   ├── SiteInfo.php               → Field schema + sanitization: Site Info
│   ├── Content.php                → Field schema + sanitization: Homepage/Post/Page/Search/404
│   ├── CategoriesTags.php         → Field schema + sanitization: Categories & Tags
│   ├── Social.php                 → Field schema + sanitization: Open Graph & Twitter Card
│   ├── Verification.php           → Field schema + sanitization: Google/Bing/Yandex
│   └── RobotsUrl.php              → Field schema + sanitization: Default Robots & URL
├── Services/
│   ├── TitleResolver.php          → Generate SEO Title from a template + placeholders
│   ├── DescriptionGenerator.php   → Generate a fallback Meta Description (excerpt-based)
│   └── PlaceholderResolver.php    → Resolve {title}, {site_name}, {tagline}, {separator}, {query}, {term_title}
├── Renderers/
│   ├── TitleRenderer.php          → <title> tag
│   ├── MetaRenderer.php           → Meta description, robots meta, canonical
│   ├── OpenGraphRenderer.php      → og:title, og:description, og:image, etc.
│   ├── TwitterCardRenderer.php    → twitter:card, twitter:title, etc.
│   └── VerificationRenderer.php   → Verification meta tags
├── Admin.php                      → Register the menu + render the root container for the React admin app
├── Editor.php                     → Editor integration (Gutenberg sidebar/panel)
├── Frontend.php                   → <head> output orchestrator (hooks wp_head)
├── Assets.php                     → Enqueue assets (admin/editor/frontend, separated per context)
└── Helpers.php                    → Generic utilities that aren't generation logic (not where business logic lives)
```

## 2.3 Rationale

- **`Settings/` per section**: each file handles exactly one section (field schema + sanitization), with `Settings.php` as a thin orchestrator. Maintains Single Responsibility and makes per-section review easier.
- **`Editor.php` separate from `Admin.php`**: different runtime contexts (Settings API/REST vs. Gutenberg/REST), different WordPress integration patterns.
- **`Services/` and `Renderers/` separate from `Helpers.php`**: `Helpers.php` is strictly for small generic utilities, not generate/render business logic.
- **Not yet promoted to Shared Services**: this logic is General-module-specific. Promotion to Shared Services only happens **once proven** necessary by another module (Sitemap/Schema) — avoiding premature abstraction.

---

# 3. Option Data Model

## 3.1 Decision

Global settings are stored in **`wp_options`** via the **WordPress Settings API**, not a custom database table. Per-post overrides are stored via the **Post Meta API**.

## 3.2 Storage Structure

- **Global Settings**: one option per module, containing every section as a nested array:
  ```
  lunar_seo_general_settings
  ```
  `autoload = yes` — because it's read on every frontend request to render meta tags, so WordPress preloads this option as a single in-memory array without a separate query.
- **Editor Override (per-post)**: via `register_post_meta()`, exposed to the REST API/Gutenberg, with a `sanitize_callback` and `auth_callback` appropriate to the context.

## 3.3 Rationale

Per `ARCHITECTURE.md` §12 and `CODING_STANDARD.md` §7 (LOCKED — mandates using the Settings API/Options API). Technically, General's settings data pattern (one config blob, read often, written rarely) is an ideal case for `wp_options`:

| Aspect | `wp_options` | Custom Table |
|---|---|---|
| Caching | Automatic via the WP Object Cache | Must be built manually |
| Compatibility | Automatically compatible with backup/migration tools | Needs its own migration schema |
| Maintenance | No `dbDelta()`/table schema versioning | Needs create/alter/drop table handling |
| Performance for this case | 1 query (even 0 if autoloaded) | Separate query, not part of autoload |

A custom table was rejected not because it's impossible, but because it adds ongoing maintenance burden with no real benefit for this data pattern (`ENGINEERING_PRINCIPLES.md` #5 — Every Feature Has a Cost).

---

# 4. Editor Integration Strategy

## 4.1 Decision

Uses the **native Gutenberg** pattern — a combination of `PluginSidebar` and `PluginDocumentSettingPanel` — not a custom modal popup.

## 4.2 Component Breakdown

| Component | Location | Rationale |
|---|---|---|
| SEO Preview (Google result simulation) | `PluginSidebar` | Needs a wide space to be representative |
| Input form (SEO Title, Meta Description, Canonical, Robots override) | `PluginSidebar` | Sits alongside the preview for real-time updates |
| Compact status indicator (optional) | `PluginDocumentSettingPanel` | Quick-glance in the Document sidebar, without forcing the preview into a cramped space |

## 4.3 Rationale

- `DESIGN_SYSTEM.md` §14: *"Avoid wizards, popups, or unnecessary configuration steps."* A full modal overlay is considered closer to the "popup" pattern being avoided.
- Project documents: no jQuery → favors a modern Gutenberg/REST-based approach.
- The native Sidebar/Panel automatically inherits Gutenberg's built-in accessibility and keyboard navigation (`CODING_STANDARD.md` §14).
- Data is stored via `register_post_meta()`, communicated to REST using `@wordpress/data` (the `core/editor` store) — fully WordPress-native, no custom AJAX.
- Fallback: a simple `add_meta_box()` (no jQuery) for Classic Editor/non-block content type compatibility.

---

# 5. Auto-generate Service

## 5.1 Decision

Auto-generate logic (Title Resolver, Description Generator, Placeholder Resolver) lives in the General module's `Services/`, executed **at runtime (on-the-fly)** during rendering — not precomputed on `save_post`.

## 5.2 Generation Rules

**SEO Title** — if empty, generated from the content's original title following the active template + placeholders (`{title} {separator} {site_name}`, etc.).

**Meta Description** — if empty, generated as a *static excerpt* with the following fallback order:

1. `has_excerpt()` — use the manual WordPress excerpt if available.
2. The first meaningful content paragraph (skipping headings/shortcodes/images).
3. Truncated to ±155–160 characters without cutting a word mid-way; HTML/shortcodes are stripped before truncation.

> **Architectural note:** the meta description is **not** generated dynamically per user search query. A meta tag is static at crawl time, so there's no technical way for the server to know a user's search query at render time. Adapting the snippet to a search query is already the search engine's responsibility. The plugin's responsibility is simply to provide a static excerpt that represents the content well.

## 5.3 Rationale for Runtime (not Precompute)

Generation is a lightweight string-manipulation operation (no extra database query/external API), so its computational cost is small. Precompute (`save_post`) adds cache-invalidation complexity with no proportional benefit for an operation this lightweight (`ENGINEERING_PRINCIPLES.md` — avoid over-engineering). Migrating to precompute can be done later without changing the foundation, since the Service is already isolated.

---

# 6. Frontend Output Renderer

## 6.1 Decision

`Frontend.php` acts as a **thin orchestrator** that hooks into `wp_head`, calling a separate Renderer per output category.

## 6.2 Renderer Categories & Render Order

1. **Title** — via the `pre_get_document_title`/`document_title_parts` filter (not a manual echo, avoiding conflicts with the theme).
2. **Meta** — meta description, robots meta, canonical.
3. **Open Graph** — only rendered if the "Enable Open Graph" toggle is on.
4. **Twitter Card** — only rendered if the "Enable Twitter Card" toggle is on.
5. **Verification** — only renders a tag for a platform whose field is filled in (empty fields are skipped, no empty tag is rendered).

## 6.3 Contract Between Renderers

Each Renderer implements `RendererInterface`, whose single public method is `init(): void`. A Renderer registers its own WordPress hook inside `init()` rather than being called directly by the orchestrator — see §0.1 for why this replaced the original `render(): void` design.

Each Renderer only **consumes** the result of `TitleResolver`/`DescriptionGenerator`/`PlaceholderResolver` (§5) — it never duplicates generation logic.

## 6.4 Security & Escaping (Required)

- `esc_attr()` for `content=""` attributes.
- `esc_url()` for URLs (canonical, og:image, etc.).
- No raw `echo` of user data without context-appropriate escaping.

Per `ARCHITECTURE.md` §16 and `CODING_STANDARD.md` §12 (LOCKED).

## 6.5 Skip-Output Conditions

Per `ARCHITECTURE.md` §10 — *"Frontend output is only loaded when needed"* — a Renderer is skipped entirely (not rendered empty) when:

- The relevant toggle (OG/Twitter) is off in Global Settings.
- The verification field for a given platform is empty.

---

# 7. Admin Settings Page — Rendering Approach

## 7.1 Decision

The General module's Settings page is rendered as a **React app** using `@wordpress/components`, consistent with the Editor Integration approach (§4) — not a classic PHP form (`add_settings_field`/`do_settings_sections`).

`Admin.php` is only responsible for:
- Menu registration (`admin_menu`)
- Rendering an empty *root container* (`<div id="lunar-seo-general-settings-root">`) for React to mount into

Data is accessed via a **custom REST route** (`lunar-seo/v1/general-settings`), registered via `register_rest_route()` on the `rest_api_init` hook. `register_setting()` is still kept for Settings API compliance (see §3) — but `show_in_rest` is deliberately **disabled** (`false`) so WordPress's generic endpoint (`/wp/v2/settings`) is never used as an access path.

**Why the generic endpoint isn't used:** `/wp/v2/settings` was found to fail at saving nested *object* data correctly — the request appears to succeed (status 200), but the saved data doesn't match the sent payload. This is a bug/limitation of that generic endpoint, not our implementation. A custom REST route per module (pattern: `lunar-seo/v1/{module}-settings`) is now the standard baseline for every module in the plugin, including Sitemap (see `SITEMAP_MODULE_ARCHITECTURE.md` §2).

## 7.2 Rationale

- The UI mockup (dynamic toggle switches, a real-time character counter, accordions, a media upload modal, an insert-variable dropdown) is hard to achieve with a classic PHP form without heavy JS.
- **Consistent** with the Editor approach — a single JS/REST pattern across the whole plugin (`DESIGN_SYSTEM.md` §18 — Component Consistency).
- Still **Settings API compliant** — `register_setting` is still used for sanitization & storage, only the REST access path (avoiding the generic endpoint's bug) and the modern UI rendering approach are custom.

---

# 8. Dependency Injection

## 8.1 Decision

`OptionManager` is passed to every module and component that needs it via **Constructor Injection**, not accessed through a *Service Locator* (`Bootstrap::instance()->get_option_manager()`).

## 8.2 Rationale

A Service Locator creates a hidden dependency on global state, conflicting with `CODING_STANDARD.md` §3: *"Avoid global state where not needed."* Dependency Injection makes each class's requirements explicit and easy to trace. Components that don't need `OptionManager` (e.g. `Admin.php`, `Assets.php`) don't receive it — per `ENGINEERING_PRINCIPLES.md` #1 — Write with Purpose.

## 8.3 Future Trigger — a 5th Shared Service

Every module's constructor (`General`, `Sitemap`, `Schema`) and `ModuleRegistry` currently accept the same 4 Shared Services, in the same order: `OptionManager, SiteIdentity, AdminMenu, SupportedPostTypes`. This is deliberate today (§8.1) — each parameter is explicit and traceable, and 4 is not yet an unreasonable number to read at a call site.

**If a 5th Shared Service is ever needed**, do not add a 5th constructor parameter to all of these call sites individually. Bundle the existing 4 (plus the new one) into a single `SharedServices` value object instead, and change every constructor to accept that one object. This keeps each individual service's own responsibility unchanged (`SharedServices` is a plain carrier, not a Service Locator — nothing is resolved lazily or hidden; every dependency a class actually uses is still visible by what it reads off the object) while turning "add a 5th parameter everywhere" into "add one property to one class."

This is scope for whenever that need actually arrives — no `SharedServices` class exists yet, and none should be built ahead of an actual 5th service, per §8.2's own reasoning applied to itself.

---

# 9. Decision Summary

| No | Area | Decision |
|---|---|---|
| 1 | Module Structure | Subfolders per responsibility (`Settings/`, `Services/`, `Renderers/`) |
| 2 | Option Data Model | `wp_options` via the Settings API (1 option per module) + Post Meta for overrides |
| 3 | Editor Integration | `PluginSidebar` (preview + form) + `PluginDocumentSettingPanel` (optional indicator) |
| 4 | Auto-generate Service | `Services/`, executed at runtime, meta description = static excerpt |
| 5 | Frontend Output Renderer | Orchestrator + one Renderer per category implementing `RendererInterface::init()` (see §0.1), each registering its own hook |
| 6 | Admin Settings Page | React app + custom REST route (not `/wp/v2/settings`), consistent with the Editor |
| 7 | Dependency Injection | Constructor Injection, no Service Locator |

---

**Status:** LOCKED