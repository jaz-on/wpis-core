# Remediation runbook: editor quirks and Abilities API

This runbook covers the full correction path for the WordPress admin editor issue where PHP notices appear before `<!DOCTYPE html>`, trigger quirks mode and break Gutenberg stability.

## Scope

- Primary file: `src/Abilities/AbilitiesRegistry.php`
- Supporting visibility: `src/CLI/WPIS_CLI_Command.php`

## 1) Code fix summary

The Abilities API category is now registered before abilities:

- Hook `wp_abilities_api_categories_init` registers category `wpis`
- Hook `wp_abilities_api_init` registers all `wpis/*` abilities

The ability names are also centralized to keep MCP allowlist and WP registration aligned.

## 2) Staging validation checklist

Run these checks on the remote staging site:

1. Open `/wp-admin/post.php?post=<id>&action=edit`.
2. Confirm the HTML starts directly with `<!DOCTYPE html>` (no PHP notice before it).
3. Confirm `Cannot modify header information - headers already sent` is absent.
4. Confirm browser is not in quirks mode.
5. Open the block editor console and verify Abilities API notices are gone:
   - no `Ability category "wpis" is not registered`
   - no `Ability "wpis/*" not found`
6. Edit and save the Home page and verify block validation is stable.

## 3) Production debug hardening

On production, keep debugging logs but prevent browser output pollution:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', '0' );
```

If you prefer no debug logs on production, set `WP_DEBUG` and `WP_DEBUG_LOG` to `false`, while still keeping `WP_DEBUG_DISPLAY` disabled.

## 4) Deployment checklist

1. Deploy updated `wpis-core` code.
2. Clear opcode cache if your host uses OPcache.
3. Clear any page or object cache layer.
4. Run `wp wpis doctor` and confirm:
   - `wp_register_ability_category: yes`
   - `wp_register_ability: yes`
5. Re-run the staging validation checklist on production.

## 5) Rollback plan

If the editor still shows pre-doctype notices after deployment:

1. Revert `wpis-core` to the previous known-good tag.
2. Keep `WP_DEBUG_DISPLAY` disabled to protect HTML output.
3. Capture fresh page source and `debug.log` for root-cause follow-up.
