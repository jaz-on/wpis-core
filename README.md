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

2. **Composer autoload:** the repository includes a production `vendor/` (autoload only) so Git Updater and plain `git pull` installs work without running Composer on the server. If you cloned an old copy or use a fork without `vendor/`, run:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Activate **WordPress Is… Core** in the Plugins screen.

4. If permalinks or archives 404, visit **Settings → Permalinks** and save once.

## Updates with Git Updater

This plugin declares a [Git Updater](https://git-updater.com/knowledge-base/required-headers/) source in `wpis-plugin.php`:

- `GitHub Plugin URI: https://github.com/jaz-on/wpis-plugin`
- `Primary Branch: main` (required because the default branch is `main`, not `master`)

Bump the **`Version:`** header in `wpis-plugin.php` when you ship changes you want sites to pull; Git Updater compares that to the latest commit on `main` (or to releases, if you configure release assets).

Maintainers: after any change to `composer.json`, `composer.lock`, or PSR-4 paths under `src/`, run `composer install --no-dev --optimize-autoloader` and **commit the updated `vendor/`** so sites that sync from `main` keep a working autoload. CI fails if `vendor/` drifts.

## Development

```bash
composer install
composer test
composer lint
```

Use the full `composer install` (with dev) locally for PHPCS and PHPUnit. Before pushing to `main`, refresh the committed production tree with `composer install --no-dev --optimize-autoloader` whenever autoload inputs changed.

## Theme contract

FSE block theme `wpis-theme` (separate path in the monorepo) expects the `quote` CPT, taxonomies, and REST rules documented in [docs/THEME-API-CONTRACT.md](docs/THEME-API-CONTRACT.md). If you add breaking URL or `register_post_type` changes, update that file and coordinate with the theme.

## Optional: Relevanssi (site search)

Install [Relevanssi](https://wordpress.org/plugins/relevanssi/) as a normal plugin. This repo includes a small integration that keeps internal `_wpis_*` meta out of the search index. Admin steps: [docs/RELEVANSSI.md](docs/RELEVANSSI.md).

## License

GPL-2.0-or-later
