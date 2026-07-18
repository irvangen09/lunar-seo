# Maintenance & Support Policy

## Versioning

This project follows [Semantic Versioning](https://semver.org/) (`MAJOR.MINOR.PATCH`):

- **MAJOR** — backward-incompatible changes (e.g. option data structure changes, options removed).
- **MINOR** — backward-compatible feature additions (e.g. a new module, new options).
- **PATCH** — bug fixes with no intentional behavior change.

All changes are documented in [CHANGELOG.md](CHANGELOG.md).

## WordPress & PHP Support

| | Minimum | Tested up to |
|---|---|---|
| WordPress | 6.9 | Latest stable |
| PHP | 8.0 | — |

Minimum requirements may increase in a **MAJOR** release if an older WordPress Core or PHP version no longer receives security updates, and will always be documented in the Changelog with a clear reason.

## Module Status

| Module | Status | Notes |
|---|---|---|
| General | Stable | SEO Title/Meta Description, Canonical, Robots, Open Graph, Twitter Card, Verification, URL |
| Sitemap | Stable | Automatic XML Sitemap, event-driven caching, Auto Priority Calculation |
| Schema | Stable | JSON-LD `@graph`: WebSite, Organization, BreadcrumbList, Article/WebPage, ImageObject |

"Stable" means the module has been tested end-to-end on a real WordPress install and no structural changes are planned in the near term — it does not mean the module is entirely bug-free.

## Data Compatibility & Backward Compatibility

Changes to this plugin must not break existing configuration or stored data (options, post meta) without a clear, documented migration strategy in the Changelog. Changes that risk breaking compatibility go through a deprecation process (marked *deprecated* for at least one MINOR release) before being fully removed in the following MAJOR release, unless there's an urgent security reason to do otherwise.

## Feature Addition Philosophy

New modules or features are only added when they provide clear, genuine value and a well-defined scope — this project favors a small number of mature, maintainable modules over chasing feature count. Larger feature requests should be discussed first via GitHub Issues before moving to implementation (pull request).

## Releases

There's no fixed release schedule — releases happen as needed (bug fixes, security, or features that are ready). Security releases are prioritized and can ship off-schedule at any time.

## Contact & Support

Usage questions and bug reports: GitHub Issues.
Security vulnerabilities: see [SECURITY.md](SECURITY.md) — not through public Issues.
