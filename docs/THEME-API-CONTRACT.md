# Theme API contract: `wpis-plugin` ↔ `wpis-theme`

This file is the hand-off note for the block theme. Version bumps follow `wpis-plugin` releases (see `Version:` in `wpis-plugin.php`); the theme can pin tested pairs in `README` if needed (no forced bump here).

## Post type *quote*

- **Constants** : `POST_TYPE` = `quote` ([`QuotePostType.php`](../src/PostTypes/QuotePostType.php)).
- **URL** : rewrite slug `quote` (single: `/quote/{post-name}/` ; post type archive: `/quote/`) — flush permalinks after install.
- **REST** : `rest_base` = `quotes` ; `show_in_rest` true. Standard `wp/v2/quotes` collection applies.
- **Visibility** : `public`, `publicly_queryable`, `has_archive` true. Disabling the plugin **removes** the CPT: expect 404 on those URLs (theme templates stay inert on vanilla WP).

## Taxonomies

- **sentiment** (slug) — not hierarchical, attached to `quote` ; `show_in_rest` true ; public URLs use rewrite slug `sentiment` (see [`SentimentTaxonomy.php`](../src/Taxonomies/SentimentTaxonomy.php)). Typical term slugs used in UI/CSS: `positive`, `negative`, `neutral`, `mixed` (see “Public REST: quote feed” below and [QuoteFeedEndpoint.php](../src/REST/QuoteFeedEndpoint.php)).
- **claim_type** (internal slug) — hierarchical ; `show_in_rest` with `rest_base` `claim-types` ; public term URLs use rewrite slug **`claim`** → `/claim/{term}/` (see [`ClaimTypeTaxonomy.php`](../src/Taxonomies/ClaimTypeTaxonomy.php)). FSE template name stays `taxonomy-claim_type.html` (WordPress uses the **taxonomy** key, not the rewrite slug).

## Post statuses (moderation)

- **Core** : `pending`, `publish`, `draft`, etc. **Custom** (admin-facing, not on the public “happy path”) : `rejected`, `merged` ([`PostStatuses.php`](../src/PostStatuses.php)). Public front queries use **`publish`**.

## Post meta (REST)

Registered in [`Meta/QuoteMeta.php`](../src/Meta/QuoteMeta.php). Keys relevant to the theme include:

- `_wpis_counter` (integer, `show_in_rest` with schema) — “echo” count in cards (min 1 in feed renderer).
- `_wpis_source_platform`, `_wpis_source_domain`, and other internal fields — use only if a block or template exposes them (optional).

*Auth* : public read of published posts is fine via REST; meta update callbacks require edit rights on the post.

## REST: authenticated (`wpis/v1`)

- `GET /wp-json/wpis/v1/my-stats` — logged in only ([`RestRegistrar`](../src/REST/RestRegistrar.php)).
- `GET /wp-json/wpis/v1/my-quotes` — **author**’s quotes (any status the query returns for the author) — for profile-style UIs.

## Public REST: quote feed (HTML)

- `GET /wp-json/wpis/v1/quote-feed` — **public** ; returns JSON `html` fragments built with classes `wpis-quote-card` / `wpis-sent-{sentiment}` ([`QuoteFeedEndpoint.php`](../src/REST/QuoteFeedEndpoint.php)). Parameters include `page`, `per_page`, `wpis_sort` (`date`|`counter`), `wpis_order`, `sentiment`, `claim_type`, `platform`, optional `lang` (Polylang). The **theme** may ignore this if it uses a block Query Loop and [`parts/quote-feed-card.html`](../../wpis-theme/parts/quote-feed-card.html) instead.

## Polylang (P.9)

- [`PolylangSetup.php`](../src/Polylang/PolylangSetup.php) registers `quote`, `sentiment`, `claim_type` for translation. **If Polylang is not active** : the plugin still runs; the `lang` param on `quote-feed` is a no-op. The theme should not assume translated duplicate posts until Polylang is configured.

## Seeding and QA (P.7)

- `wp wpis seed_demo` ([`WPIS_CLI_Command`](../src/CLI/WPIS_CLI_Command.php)) — creates demo `quote` posts (meta flag `_wpis_demo_seed`). **Erase** with `--erase`. Default terms: [`Activation::seed_default_terms`](../src/Activation.php).

## Submission / uploads

- **Not** the theme: handler and cron in [`Submission/`](../src/Submission/). The boundary is summarized in the repo [wpis-plugin-boundary-submit.md](../../docs/wpis-plugin-boundary-submit.md) and the theme [SUBMIT-BOUNDARY.md](../../wpis-theme/docs/SUBMIT-BOUNDARY.md).
