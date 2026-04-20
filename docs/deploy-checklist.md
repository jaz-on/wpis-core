# Deploy checklist (WordPress Is…)

Use this after pulling `**wpis-plugin**` and `**wpis-theme**` to the server (e.g. `wpis.jasonrouet.com`).

## 1. Plugin

- Copy or pull into `wp-content/plugins/wpis-plugin/`.
- Run `**composer install --no-dev**` in that folder if your deploy omits `vendor/`.
- In **Plugins**, activate **WordPress Is… Core**.
- Visit **Settings → Permalinks** and click **Save** once (flushes rewrite rules for the `quote` CPT).

## 2. Confirm Quotes in admin

- As **Administrator**, open the dashboard sidebar: **Quotes** should appear (dashicon quote, near Posts).
- If it does not: plugin inactive, deploy missing `src/`, PHP fatal (check `debug.log`), or user role cannot `edit_posts`.
- On the server shell (optional): `wp post-type list --field=name | grep quote`

## 3. Theme

- Deploy into `wp-content/themes/wpis-theme/`.
- Activate **WPIS Theme**.
- **Reading**: set **Homepage** to your static front page if you use the marketing `front-page` template; assign **Posts page** to a page that should list quotes (uses `home.html`).

## 4. Polylang (if used)

- **Languages → Settings**: make **Quotes** and related taxonomies translatable.
- Create EN/FR (or your set) and translate key pages (Submit, Submitted, Explore, Profile, etc.).

## 5. Demo content (optional)

From the plugin directory:

```bash
wp wpis doctor
wp wpis seed_demo --count=24
```

Remove demo posts later:

```bash
wp wpis seed_demo --erase
```

## 6. Git Updater / CI

- If webhooks return **500**, inspect host **PHP error logs** and `wp-content/debug.log`; rotate any exposed webhook secrets.

