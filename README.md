# WordPress Is… Core (`wpis-plugin`)

Core plugin for the [WordPress Is…](https://wpis.jasonrouet.com) project: the `quote` post type, taxonomies, merge and deduplication logic, public submission handling, REST endpoints and [Git Updater](https://git-updater.com/)–compatible metadata for installs that track this repository.

## Requirements

- WordPress **6.9+** (Abilities API)
- PHP **8.2+**
- [MCP Adapter](https://github.com/WordPress/mcp-adapter) recommended if you want MCP exposure (optional for other features)

## Installation

1. Clone or copy this repository into your WordPress plugins directory **as `wpis-plugin`** (the folder name must match the main plugin file `wpis-plugin.php`).

   ```text
   wp-content/plugins/wpis-plugin/
   ```

2. **Autoload:** the plugin uses `inc/autoload-runtime.php` (PSR-4 for `WPIS\Core\` from `src/`). You do **not** need to run Composer on the server. Optional: after `composer install` locally, `vendor/autoload.php` is used if present; otherwise the same classes load from `src/`.

3. Activate **WordPress Is… Core** in the Plugins screen.

4. If permalinks or archives 404, visit **Settings → Permalinks** and save once.

## Updates with Git Updater

This plugin declares a [Git Updater](https://git-updater.com/knowledge-base/required-headers/) source in `wpis-plugin.php`:

- `GitHub Plugin URI: https://github.com/jaz-on/wpis-plugin`
- `Primary Branch: main` (required because the default branch is `main`, not `master`)

Bump the **`Version:`** header in `wpis-plugin.php` when you ship changes you want sites to pull; Git Updater compares that to the latest commit on `main` (or to releases, if you configure release assets).

## Development

```bash
composer install
composer test
composer lint
```

Use the full `composer install` (with dev) locally for PHPCS and PHPUnit. The `vendor/` directory is not committed; production sites rely on the runtime PSR-4 autoloader in `inc/autoload-runtime.php`.

Internal architecture notes, API hand-offs and Cursor rules are kept **outside** this repository (for example a local `.doc/` or `.cursor/` folder on your machine).

## Optional: Relevanssi (site search)

Install [Relevanssi](https://wordpress.org/plugins/relevanssi/) as a normal plugin. This package includes a small integration that keeps internal `_wpis_*` meta out of the search index when Relevanssi is active.

## License

GPL-2.0-or-later
