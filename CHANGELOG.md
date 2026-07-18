# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.0] - 2026-07-18

### Added

- **General** module: SEO Title & Meta Description (global + per-post override via the Gutenberg sidebar), Canonical URL, Robots Meta, Open Graph, Twitter Card, Verification (Google Search Console/Bing/Yandex), Remove Category/Tag Base.
- **Sitemap** module: automatic XML Sitemap with event-driven caching (transient, no expiration), Automatic Priority Calculation (rank-based linear interpolation), pagination above 1000 URLs per sitemap, Excluded Items, per-type Priority/Changefreq settings.
- **Schema** module: `@graph` JSON-LD containing `WebSite`, `Organization`, `BreadcrumbList`, `Article` (post)/`WebPage` (page), `ImageObject` (primary image only) — fully automatic, no settings page.
- Shared `SiteIdentity` service for site identity data used across modules (General & Schema).
- `uninstall.php` — cleans up all options, post meta, and transients created by the plugin on uninstall.
- CSS styling for the Settings pages (Admin/Editor) and a friendlier variable placeholder chip UI.

[Unreleased]: ../../compare/1.0.0...HEAD
[1.0.0]: ../../releases/tag/1.0.0
