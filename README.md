# WordPress Is… Core (`wpis-core`)

Core plugin for the [WordPress Is…](https://wpis.jasonrouet.com) project: the `quote` post type, taxonomies, merge and deduplication logic, public submission handling, REST endpoints and [Git Updater](https://git-updater.com/)–compatible metadata for installs that track this repository.

**Repository on GitHub:** [`jaz-on/wpis-core`](https://github.com/jaz-on/wpis-core).

### Monorepo and the canonical repository

This plugin is often vendored as `packages/wpis-core` inside a larger workspace. **GitHub [`jaz-on/wpis-core`](https://github.com/jaz-on/wpis-core)** remains the canonical source for releases, Git Updater and version history. If you see a nested `.git` inside that folder, it is usually a full clone used for subtree or standalone work—do not assume the monorepo root and the plugin repo share the same remote. Prefer **git subtree**, a **submodule** or a simple copy with a documented sync process so `main` on `jaz-on/wpis-core` stays the single place that matches the **`Version:`** header in `wpis-core.php`.

## Requirements

- WordPress **6.9+** (Abilities API)
- PHP **8.2+**
- [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) **required** (declared in plugin headers; WordPress 6.5+ shows plugin dependencies in **Plugins**)

## Installation

1. Clone or copy this repository into your WordPress plugins directory **as `wpis-core`** (the folder name must match the main plugin file `wpis-core.php`).

   ```text
   wp-content/plugins/wpis-core/
   ```

2. **Autoload:** the plugin uses `inc/autoload-runtime.php` (PSR-4 for `WPIS\Core\` from `src/`). You do **not** need to run Composer on the server. Optional: after `composer install` locally, `vendor/autoload.php` is used if present; otherwise the same classes load from `src/`.

3. Activate **WordPress Is… Core** in the Plugins screen.

4. If permalinks or archives 404, visit **Settings → Permalinks** and save once.

## Updates with Git Updater

This plugin declares a [Git Updater](https://git-updater.com/knowledge-base/required-headers/) source in `wpis-core.php`:

- `GitHub Plugin URI: https://github.com/jaz-on/wpis-core`
- `Primary Branch: main` (required because the default branch is `main`, not `master`)

Bump the **`Version:`** header in `wpis-core.php` when you ship changes you want sites to pull; Git Updater compares that to the latest commit on `main` (or to releases, if you configure release assets).

## Development

```bash
composer install
composer lint
```

Use the full `composer install` (with dev) locally for PHPCS and PHPUnit. The `vendor/` directory is not committed; production sites rely on the runtime PSR-4 autoloader in `inc/autoload-runtime.php`.

**PHPUnit** needs the WordPress test library and a MySQL (or MariaDB) server. One-time setup:

```bash
bash bin/install-wp-tests.sh wordpress_test root "" 127.0.0.1 latest
export WP_TESTS_DIR="${TMPDIR}/wordpress-tests-lib"   # adjust if your install script used another path
composer test
```

`bin/install-wp-tests.sh` is the standard script from [wp-cli/scaffold-command](https://github.com/wp-cli/scaffold-command/blob/master/templates/install-wp-tests.sh) (local `mysql` and `mysqladmin` required). Without `WP_TESTS_DIR`, `composer test` exits early with a short reminder.

Internal architecture notes, API hand-offs and Cursor rules are kept **outside** this repository (for example a local `.doc/` or `.cursor/` folder on your machine).
