# Contributing to Lunar SEO

Thanks for your interest in contributing. This document covers how to set up a development environment, the coding standards we follow, and the pull request workflow.

## Development Setup

Prerequisites: PHP 8.0+, Composer, Node.js (see the `@wordpress/scripts` version in `package.json`), and a local WordPress install (6.9+) for testing.

```bash
git clone <your-repository-url>
cd lunar-seo

composer install
npm install
```

Available scripts:

| Command | Purpose |
|---|---|
| `npm run build` | Production build (`build/*.js`, `build/style-*.css`) |
| `npm start` | Development build with watch mode |
| `npm run lint:js` | Lint JavaScript |
| `npm run format` | Auto-format JavaScript |
| `npm run plugin-zip` | Package the plugin into an installable `.zip` |

After `composer install` and `npm run build`, symlink or copy the project folder into `wp-content/plugins/` on your local WordPress install for testing.

## Coding Standards

A short summary (this doesn't replace code review, but sets the minimum bar):

- **PHP:** OOP, `Lunar\SEO\...` namespace following the PSR-4 structure in `composer.json`, one responsibility per class, follow the WordPress Coding Standards.
- **Dependency Injection:** Constructor Injection — avoid a Service Locator or reaching for global state directly.
- **Modules:** Each module (`modules/<name>/`) stands on its own and must not depend directly on another module. Cross-module communication goes through a Shared Service (`includes/Services/`) when truly needed.
- **Options:** One WordPress option per module (Options API), not many small scattered options.
- **JavaScript:** Modern ESNext, modular, use official `@wordpress/*` packages before adding a new dependency. No jQuery.
- **Security:** All input must be validated, sanitized, and escaped for its context (`esc_html`, `esc_attr`, `esc_url`, etc.).
- **Performance:** Avoid unnecessary database queries; assets are only loaded on the pages that actually need them (Admin/Editor/Frontend are kept separate).

If you add a new dependency (Composer or npm), explain why in the pull request description — new dependencies should be genuinely necessary, not just a convenience.

## Reporting Bugs / Requesting Features

Use GitHub Issues. For bug reports, please include:

- Lunar SEO, WordPress, and PHP versions.
- Steps to reproduce.
- Expected vs. actual behavior.

For security vulnerabilities, **do not** use public Issues — follow [SECURITY.md](SECURITY.md) instead.

## Pull Request Workflow

1. Fork the repository and create a new branch off `main` with a descriptive name (e.g. `fix/sitemap-cache-invalidation`, `feature/schema-video-object`).
2. Make sure `npm run build` and `npm run lint:js` run without errors before opening a PR.
3. Test your change manually on a WordPress install (this project doesn't have an automated test suite yet — PRs that add automated tests are very welcome, but clear manual verification is also accepted).
4. Open a pull request against `main`, explain **what** changed and **why**, and link any related issue.
5. Keep each pull request focused on a single change — avoid bundling unrelated changes together, so it stays easy to review.

## Code of Conduct

Participation in this project is governed by the [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).
