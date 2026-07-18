# Lunar SEO

A lightweight, modular WordPress SEO plugin that follows WordPress Coding Standards.

Lunar SEO only ships the technical SEO features that are actually needed — a good fit for content-focused websites such as documentation sites, wikis, knowledge bases, and blogs. It is not an "all-in-one" plugin: there's no SEO Score, Readability Analysis, AI Writing, Analytics, or Redirect Manager. A handful of mature features beats a pile of half-finished ones.

## Requirements

| | Minimum |
|---|---|
| WordPress | 6.9 |
| PHP | 8.0 |

## Features

The plugin is built from three independent modules — each can evolve without affecting the others.

### General

- SEO Title & Meta Description (global, with per-post overrides via the Gutenberg sidebar)
- Canonical URL & Robots Meta
- Open Graph & Twitter Card
- Verification for Google Search Console, Bing Webmaster, and Yandex
- Remove Category/Tag Base from permalinks

### Sitemap

- Automatic XML Sitemap for every content type (posts, pages, categories, tags, authors, custom post types/taxonomies)
- Updates automatically whenever content is published, edited, or deleted — no manual regeneration needed
- Content exclusion (Excluded Items) and per-type Priority/Changefreq settings

### Schema

- Automatic structured data (JSON-LD): `WebSite`, `Organization`, `BreadcrumbList`, `Article`/`WebPage`, `ImageObject` (primary image only)
- Fully derived from data that already exists (title, featured image, category, author, date) — no additional settings to fill in manually

## Installation

1. Download the latest release (`.zip`) from the [Releases](../../releases) page, or clone this repository and build it yourself (see [CONTRIBUTING.md](CONTRIBUTING.md)).
2. Upload it via **Plugins → Add New → Upload Plugin** in wp-admin, or extract it to `/wp-content/plugins/`.
3. Activate the plugin from the **Plugins** menu.
4. Open the **Lunar SEO** menu in the admin sidebar to configure General Settings.
5. Open **Lunar SEO → Sitemap** to configure Sitemap Settings.
6. The Schema module runs automatically — no additional settings page.

## FAQ

**Can Lunar SEO be used alongside another SEO plugin?**

Not recommended. Lunar SEO isn't designed to run side-by-side with other SEO plugins that produce similar output (meta tags, sitemaps, structured data), since that risks duplication. Disable the overlapping features on the other plugin, or use only one SEO plugin at a time.

**Is my settings data lost if the plugin is deactivated?**

No. Data is only removed when the plugin is fully deleted (not just deactivated) via the Plugins page. See `uninstall.php` for exactly what gets cleaned up.

**Where can I check the generated XML sitemap?**

Visit `/sitemap.xml` on your site's domain.

## Architecture

Technical architecture documentation for each module (class structure, design decisions, trade-offs) is available under [`docs/architecture/`](docs/architecture/):

- [General](docs/architecture/GENERAL_MODULE_ARCHITECTURE.md)
- [Sitemap](docs/architecture/SITEMAP_MODULE_ARCHITECTURE.md)
- [Schema](docs/architecture/SCHEMA_MODULE_ARCHITECTURE.md)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, coding standards, and the pull request workflow.

## Support & Maintenance

See [MAINTENANCE.md](MAINTENANCE.md) for the versioning policy, WordPress/PHP support, and the status of each module.

To report a security vulnerability, do not use public GitHub Issues — follow [SECURITY.md](SECURITY.md) instead.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPLv2 or later.
