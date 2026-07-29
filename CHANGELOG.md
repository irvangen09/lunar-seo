# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.2] - 2026-07-29

Hasil re-audit (regression check) terhadap v1.0.1 — seluruh perbaikan v1.0.1 terverifikasi tanpa regresi. Rilis ini berisi temuan tambahan dari re-audit tersebut.

### Fixed

- Media upload preview images (Site Image, Default Social Image, Default Twitter Image) no longer render at native resolution — previously a selected image at the recommended size (1200×630px) could overflow the settings page horizontally.
- Excluded Items category checklist (Sitemap) is now height-constrained with scrolling, instead of growing unbounded on sites with many categories.
- Translated the one remaining Indonesian string (the "Dependencies not installed" admin notice) to English.
- The plugin now shows a clear admin notice when the PHP or WordPress version requirement isn't met, instead of silently doing nothing.
- Grouped related checkbox/button controls (Default Robots Meta, per-post Robots override, Title Separator picker) with proper group semantics (`role="group"` + accessible label) for screen readers.
- The character count indicator (SEO Title/Meta Description fields) is now announced to screen readers as it updates (`aria-live`).

### Changed

- Editor sidebar/document panel assets (General module) are no longer loaded on every Block Editor screen — only on `post`/`page`, the only post types the feature actually supports.
- Extracted the top-level admin menu slug into a new shared `AdminMenu` service (`includes/Services/AdminMenu.php`), removing a direct dependency of the Sitemap module on the General module's `Admin` class. This supersedes the "references General's menu slug constant directly" note from 1.0.1 — the underlying coupling issue is now fully resolved instead of merely reduced.
- `OptionManager` no longer updates its in-request cache when a settings write to the database fails.
- Meta description generation is now memoized per request, avoiding redundant processing when Meta Description, Open Graph, and Twitter Card descriptions are all generated for the same post.
- `uninstall.php` now explicitly requests an unlimited site list during multisite cleanup, instead of relying on the default 100-site query limit.

### Removed

- Removed dead code in `SitemapCache`: an `'index'` transient key was being deleted on every cache invalidation despite never actually being written anywhere.

## [1.0.1] - 2026-07-19

### Security

- Removed `JSON_UNESCAPED_SLASHES` from the Schema module's JSON-LD output. This flag disabled PHP's default protection against a `</script>` sequence (e.g. inside a post title, category name, or author name) prematurely closing the JSON-LD `<script>` tag, which could lead to stored XSS.

### Fixed

- "Show in search results" toggle (Categories & Tags) now actually affects the `robots` meta tag on category/tag archives — previously the setting was saved but never read anywhere, so disabling it had no effect.
- Fixed inconsistent escaping of `og:url`, `og:image`, and `twitter:image` (now use `esc_url()` instead of `esc_attr()`).
- Removed an unnecessary `wp_enqueue_media()` call on the Sitemap settings page.
- SEO Title and Meta Description fields (Admin Settings and Editor sidebar) now have a proper accessible label for screen readers.
- "Show in search results" toggle no longer visually defaults to off on a fresh install when nothing has been saved yet.
- Priorities dropdown (Sitemap) now correctly reflects saved whole-number values (`1.0`, `0.0`).
- Excluded Items category checklist no longer silently caps at the first 100 categories.
- Settings pages no longer get stuck on an endless loading spinner if the initial settings request fails; an error notice is shown instead.
- Bundled translations will now actually load (`load_plugin_textdomain()` was never called).
- Translated remaining Indonesian UI strings to English for consistency.

### Changed

- Sitemap cache no longer invalidates on post revisions/autosaves, reducing unnecessary cache regeneration.
- Sitemap's admin submenu now references General's menu slug constant directly instead of a duplicated string.
- Extracted a shared `useRestSettings` hook, removing duplicated fetch/save logic between the General and Sitemap Settings apps.

### Removed

- Removed `readme.txt` (duplicated by `README.md`).
- Removed unused code: `Bootstrap::get_option_manager()`/`get_site_identity()`, `TitleResolver::get_site_name()`, orphaned `template-field.js` component.

## [1.0.0] - 2026-07-18

### Added

- **General** module: SEO Title & Meta Description (global + per-post override via the Gutenberg sidebar), Canonical URL, Robots Meta, Open Graph, Twitter Card, Verification (Google Search Console/Bing/Yandex), Remove Category/Tag Base.
- **Sitemap** module: automatic XML Sitemap with event-driven caching (transient, no expiration), Automatic Priority Calculation (rank-based linear interpolation), pagination above 1000 URLs per sitemap, Excluded Items, per-type Priority/Changefreq settings.
- **Schema** module: `@graph` JSON-LD containing `WebSite`, `Organization`, `BreadcrumbList`, `Article` (post)/`WebPage` (page), `ImageObject` (primary image only) — fully automatic, no settings page.
- Shared `SiteIdentity` service for site identity data used across modules (General & Schema).
- `uninstall.php` — cleans up all options, post meta, and transients created by the plugin on uninstall.
- CSS styling for the Settings pages (Admin/Editor) and a friendlier variable placeholder chip UI.

[Unreleased]: ../../compare/1.0.2...HEAD
[1.0.2]: ../../compare/1.0.1...1.0.2
[1.0.1]: ../../compare/1.0.0...1.0.1
[1.0.0]: ../../releases/tag/1.0.0
