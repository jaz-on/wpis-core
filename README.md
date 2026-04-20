# WordPress Is… Core (`wpis-plugin`)

Core plugin for the [WordPress Is…](https://wpis.jasonrouet.com) project: the `quote` post type, taxonomies, merge and deduplication logic, public submission handling, REST endpoints, and [Git Updater](https://git-updater.com/)–compatible metadata for installs that track this repository.

## Requirements

- WordPress **6.9+** (Abilities API)
- PHP **8.2+**
- [MCP Adapter](https://github.com/WordPress/mcp-adapter) recommended if you want MCP exposure (optional for other features)

## Installation

1. Clone or copy this repository into your WordPress plugins directory **as `wpis-plugin`** (the folder name must match the main plugin file `wpis-plugin.php`).

   ```text
   wp-content/plugins/wpis-plugin/
   ```

2. From that directory, install PHP dependencies:

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

## Development

```bash
composer install
composer test
composer lint
```

## License

GPL-2.0-or-later
