# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

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

[Unreleased]: ../../compare/1.0.1...HEAD
[1.0.1]: ../../compare/1.0.0...1.0.1
[1.0.0]: ../../releases/tag/1.0.0
