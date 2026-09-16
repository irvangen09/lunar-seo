# LUNAR_SEO_WIKI_INTEGRATION_CONTRACT.md
## Integration Contract Specification — Lunar SEO ↔ Lunar Wiki

**Status:** LOCKED — both 🔶 decisions below have been implemented across the 6 consumption points in §3.6
**Tier:** Ecosystem-level (lives in the `lunar-seo` repo, copied to `lunar-wiki` — same pattern as `LUNAR_BLOCKS_WIKI_INTEGRATION_CONTRACT.md`)
**Triggered by:** The `LUNAR_SEO_KICKOFF.md` §2 audit — 3 hardcoded `post`/`page` points that left the `wiki_article` CPT with no SEO features from Lunar SEO (General or Schema module). A 6th point (`Assets.php`) was found during implementation; see §3.6.

---

## 1. Purpose

This document is the **single source of truth** for how Lunar SEO and Lunar Wiki are allowed to communicate — following the exact same principle as `LUNAR_BLOCKS_WIKI_INTEGRATION_CONTRACT.md`:

- Lunar SEO **must not** contain a single line of code that references any Wiki domain concept (`wiki_article`, any Wiki taxonomy).
- Lunar Wiki **may** depend on Lunar SEO being active (soft, optional) — this direction follows `ARCHITECTURE.md` §19 (Lunar SEO stands alone, zero dependency; Lunar Wiki registers into its extension point).
- All communication goes through **one generic extension point**: the PHP filter `lunar_seo_supported_post_types`.

No other communication mechanism is valid outside what's defined here.

---

## 2. Dependency Direction

```text
Lunar SEO (standalone, zero dependency)
        ↑
        │ registers into the extension point
        │
Lunar Wiki (domain-specific, registers as a "provider")
```

Without Lunar Wiki active, the filter returns the default `['post', 'page']` — **identical behavior to the current state**. This satisfies the additive-only requirement (`CODING_STANDARD.md` §21): no existing Lunar SEO user sees a behavior change.

---

## 3. Extension Point — PHP Filter `lunar_seo_supported_post_types`

### 3.1 Name & Location

```text
Filter name  : lunar_seo_supported_post_types
Called in    : Lunar SEO — Services/SupportedPostTypes.php (new, see §3.3)
Registered by: a provider plugin (e.g. Lunar Wiki)
```

### 3.2 🔶 Decision #1 — Shape of the Filter Value

**Problem:** the General module distinguishes "post" vs. "page" behavior in two places (Content settings §2.2 of `GENERAL_MODULE_ARCHITECTURE.md`, and the Schema module distinguishes `ArticleNode` vs. `WebPageNode`, §4 of `SCHEMA_MODULE_ARCHITECTURE.md`). A flat slug list (`['post', 'page', 'wiki_article']`) can't express this distinction — it would need a second filter, even though the kickoff §2 explicitly wanted to check whether one filter is enough.

**Recommendation: an associative array with per-post-type config**, not a flat slug list:

```php
// Default value (no provider):
[
    'post' => [
        'content_group' => 'post',     // which Content settings section applies (General)
        'schema_node'   => 'article',  // ArticleNode vs. WebPageNode (Schema)
    ],
    'page' => [
        'content_group' => 'page',
        'schema_node'   => 'webpage',
    ],
]
```

A single filter is still enough — consistent with the kickoff §2 preference, and aligned with `ENGINEERING_PRINCIPLES.md` §13 (avoid two extension points when one already answers the need).

### 3.3 Registration Example (Lunar Wiki)

```php
add_filter( 'lunar_seo_supported_post_types', function ( array $post_types ): array {
	$post_types['wiki_article'] = [
		'content_group' => 'post',
		'schema_node'   => 'article',
	];

	return $post_types;
} );
```

### 3.4 🔶 Decision #2 — Does `wiki_article` Belong in the "post" Group, or a New Group?

**Problem:** `wiki_article` is literally neither `post` nor `page` — it needs to be decided whether it needs its own group (e.g. `"documentation"`) with its own Content/Schema section, or can simply be treated as equivalent to `post`.

**Recommendation: equivalent to `post`.** Rationale:
- `wiki_article` has the same structure as `post` (title, editor, date, author) — `WIKI_DATA_REFERENCE.md` §1 confirms `supports: title, editor, custom-fields`.
- A new group means a new Settings section in General (extra UI) + a new Schema node — a real cost (`ENGINEERING_PRINCIPLES.md` §7) with no proven need right now.
- This contract is additive-only — a new group can be added at any point later without changing the foundation, if `wiki_article` turns out to need different treatment from regular `post`.

### 3.5 Shared Service — `SupportedPostTypes`

```php
namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SupportedPostTypes {

	private const DEFAULT = [
		'post' => [
			'content_group' => 'post',
			'schema_node'   => 'article',
		],
		'page' => [
			'content_group' => 'page',
			'schema_node'   => 'webpage',
		],
	];

	public function all(): array {
		return apply_filters( 'lunar_seo_supported_post_types', self::DEFAULT );
	}

	public function is_supported( string $post_type ): bool {
		return isset( $this->all()[ $post_type ] );
	}

	public function content_group( string $post_type ): ?string {
		return $this->all()[ $post_type ]['content_group'] ?? null;
	}

	public function schema_node( string $post_type ): ?string {
		return $this->all()[ $post_type ]['schema_node'] ?? null;
	}
}
```

Constructor Injection into every consumption point (§3.6) — identical pattern to `SiteIdentity` (`SCHEMA_MODULE_ARCHITECTURE.md` §3.5), no Service Locator (`GENERAL_MODULE_ARCHITECTURE.md` §8).

### 3.6 Consumption Points (6 Hardcoded Points — 5 from the Kickoff Audit + 1 Found During Implementation)

| File | Before | After |
|---|---|---|
| `modules/general/Editor.php` | `const SUPPORTED_POST_TYPES = ['post','page']` | `foreach ( array_keys( $this->supported_post_types->all() ) as $post_type )` |
| `modules/general/Assets.php` *(found during implementation, not in the original kickoff audit)* | `in_array( $screen->post_type, Editor::SUPPORTED_POST_TYPES, true )` | `$this->supported_post_types->is_supported( $screen->post_type )` |
| `modules/general/Renderers/TitleRenderer.php` | `is_singular('post')` / `is_singular('page')` as two separate branches | Merged into one `is_singular()` branch reading `content_group( get_post_type() )` |
| `modules/general/Renderers/MetaRenderer.php` | Same as above | Same merged pattern |
| `modules/schema/Nodes/ArticleNode.php` | `is_singular('post')` | `is_singular() && 'article' === $this->supported_post_types->schema_node( get_post_type() )` |
| `modules/schema/Nodes/WebPageNode.php` | `is_singular('page')` | `is_singular() && 'webpage' === $this->supported_post_types->schema_node( get_post_type() )` |

### 3.7 Editor UI Surfaces (Added — Follow-up to §3.6)

The six points above cover the PHP side. Two further surfaces consume the same contract and must stay consistent with it:

| Surface | Behavior |
|---|---|
| Block Editor (`PluginSidebar`, `PluginDocumentSettingPanel`) | Gated **server-side only**, by `Assets.php` via `is_supported()`. The JavaScript bundle deliberately carries no post-type list of its own — if the bundle is enqueued, the post type is supported by definition. |
| Classic Editor (`MetaBox.php`) | Registers only where `is_supported()` is true **and** `use_block_editor_for_post_type()` is false, so the two editor surfaces never appear on the same screen. |

A post type registered through this filter therefore gets the per-post SEO fields in whichever editor the site actually uses, with no further code change in Lunar SEO.

---

## 4. Fallback Behavior (No Provider)

Without Lunar Wiki active, `apply_filters()` returns `self::DEFAULT` — behavior identical to the current hardcoded state. No fatal `class_exists()`/dependency check, zero cost when nothing registers (same pattern as `LUNAR_BLOCKS_WIKI_INTEGRATION_CONTRACT.md` §4.4).

---

## 5. Deliberately NOT Covered by This Contract

- **Sitemap module** — already generic via `ContentTypeRegistry::get_post_types(['public' => true])`, automatically covers `wiki_article` with no change needed.
- **`ImageObjectNode`** — called internally by `ArticleNode`/`WebPageNode` (`SCHEMA_MODULE_ARCHITECTURE.md` §5.5), automatically covered with no separate integration point.
- **Wiki-specific fields/taxonomies** (`wiki_field`, etc.) — entirely outside Lunar SEO's scope, not touched by this contract.

---

## 6. Compatibility & Versioning Strategy

Follows the `LUNAR_BLOCKS_WIKI_INTEGRATION_CONTRACT.md` §6 pattern: additive-only, the filter name and array structure don't change without a formal deprecation process. Changes are recorded in both repos' `CHANGELOG.md`.

---

## 7. Decision Summary

| # | Decision | Final Decision | Status |
|---|---|---|---|
| 🔶 1 | Shape of the filter value | Associative array per post type (`content_group` + `schema_node`), not a flat slug list | ✅ Implemented |
| 🔶 2 | `wiki_article` group | Equivalent to `post` (not a new group) | ✅ Implemented |

---

*Part of the Lunar ecosystem's gap-closing process. Both decisions have been implemented across the 6 code points in §3.6, with the editor UI surfaces in §3.7 following the same contract.*
