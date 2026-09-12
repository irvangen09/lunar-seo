# Lunar SEO

A lightweight, modular WordPress SEO plugin. Each module — General, Sitemap, and Schema — works independently, with no required dependency on any other plugin.

## Features

### General
- SEO title and meta description, built from placeholder-based templates
- Canonical URLs, robots meta, Open Graph, and Twitter Card output
- Google, Bing, and Yandex site verification
- Per-post overrides from the Block Editor sidebar

### Sitemap
- Automatic XML sitemap covering posts, pages, categories, tags, authors, and any public custom post type or taxonomy
- Configurable priority and change frequency per content type
- Cache invalidated automatically when content changes, no manual regeneration needed

### Schema
- JSON-LD structured data (`@graph`): `WebSite`, `Organization`, `BreadcrumbList`, `Article`/`WebPage`, and featured image data
- Fully derived from existing site and content data, no configuration required

## Requirements

- PHP 8.0 or higher
- WordPress 6.9 or higher

## Installation

1. Download the latest release, or clone this repository.
2. Upload the `lunar-seo` folder to `/wp-content/plugins/`.
3. Activate the plugin from the Plugins screen in WordPress.

## Extensibility

By default, only the `post` and `page` post types receive SEO features. Other plugins can register additional post types through a filter:

```php
add_filter( 'lunar_seo_supported_post_types', function ( array $post_types ): array {
	$post_types['my_custom_type'] = [
		'content_group' => 'post',    // or 'page'
		'schema_node'   => 'article', // or 'webpage'
	];

	return $post_types;
} );
```

`content_group` determines which General module Content settings apply; `schema_node` determines which Schema module node is generated.

## Development

```bash
composer install
npm install
npm run build
```

`npm run start` watches JS/CSS while developing. `npm run plugin-zip` produces a distributable zip.

## License

GPL-2.0-or-later. See [LICENSE.md](LICENSE.md).