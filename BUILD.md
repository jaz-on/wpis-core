# WordPress Is — build handbook

Single source of truth for the build phase. Product vision: `wordpress-is-vision.md`. Original staged prompts: `wordpress-is-cursor-plan.md` (adapted below).

---

## 3a. Prerequisites

### Tools (recommended versions)

- **PHP** **8.3.x** on the server; on your machine, PHP/Composer only for tooling and tests. Plugin **`Requires PHP`** should match the server (not WordPress.org—project will not ship there), e.g. **8.3** unless you intentionally support older runtimes.
- **Composer** 2.x
- **Node.js** 20 LTS (for theme tooling if/when `@wordpress/scripts` or similar is added)
- **Git**
- **WP-CLI** (optional; useful if your host allows SSH and before Chantier 2 CLI commands)
- **No dedicated local WordPress stack** — development is validated by deploying and testing on **`wpis.jasonrouet.com`** (see below).

### Target site: `wpis.jasonrouet.com`

1. Production runs **WordPress 6.9.4**, **PHP 8.3.x**, and the stack from the vision (Polylang, MCP Adapter, etc.).
2. Deploy the plugin codebase (GitHub **`jaz-on/wpis-plugin`**) into **`wp-content/plugins/wpis-plugin/`**, and **`wpis-theme`** into `wp-content/themes/wpis-theme/`, per your hosting workflow (SFTP, Git pull, CI—document the exact steps when stable).
3. Required plugins on that install: **Polylang** (free), **MCP Adapter**, **Action Scheduler** when needed for jobs.
4. Permalinks: **Post name** (or whatever Polylang’s `/en/` `/fr/` setup needs).

### Git / repo layout

- **Decided:** **Two separate GitHub repos** — **`jaz-on/wpis-plugin`** (plugin) and **`jaz-on/wpis-theme`**. Clone both into one Cursor workspace (sibling folders) if you want both open at once.
- **Naming:** Repo **`wpis-plugin`** / **`wpis-theme`**. On the server, the plugin lives in **`wp-content/plugins/wpis-plugin/`** with text domain **`wpis-plugin`** and main file **`wpis-plugin.php`**.
- Initialize each repo with `main`, add `.gitignore` (vendor/, node_modules/, .env).

### One-time setup (plugin)

From the **`wpis-plugin`** repo root (once Composer exists):

- `composer install`
- Run PHPCS and PHPUnit as defined in `composer.json` (added in Chantier 1).

### One-time setup (theme)

When Chantier 3 adds tooling:

- `npm install` / `npm run build` as per theme `package.json`.

### Activate plugin (on `wpis.jasonrouet.com`)

- Upload or deploy the plugin folder to `wp-content/plugins/wpis-plugin/`.
- In **Plugins**, activate **WordPress Is… Core**.

### Verification

- Site loads; **WordPress** version **6.9.x** (dashboard or `wp core version` over SSH if available)
- No fatals after deploy; plugin activates
- MCP-related notices only if expected (e.g. MCP Adapter inactive warning)
- Optional: `curl -sI https://wpis.jasonrouet.com/wp-json/wpis/v1/wpis` when MCP is wired
- After tests exist: `composer test` passes locally in the **`wpis-plugin`** clone (CI or your machine—no local WP required)

---

## 3b. Adapted chantiers (12 + notes)

**Legend:** Complexity S / M / L. Prompts are adapted from `wordpress-is-cursor-plan.md` with fixes from review.

**Note on reordering:** Phases in §3d may reorder chantier *execution* for speed vs. risk; numbering **keeps original IDs** for traceability.

### Chantier 1 — Plugin foundations

- **Goal:** CPT `quote`, taxonomies, meta, admin list, custom statuses, Composer PSR-4, baseline tests.
- **Dependencies:** none
- **Complexity:** L
- **Deliverables:** Registration complete; terms seeded on activation; admin columns/filters; PHPUnit green; PHPCS clean; no regressions to existing MCP server block in `wpis-plugin.php` (extend via new files; avoid breaking `mcp_adapter_init`).
- **Decided:** Register `_wpis_opposing_quote_id` and `_wpis_editorial_note` in **Chantier 1** with the rest of the meta (REST, sanitization, uninstall story)—Chantier 4 only consumes them.
- **Adapted prompt:** Use original Chantier 1 prompt with these changes: (1) Include meta `_wpis_opposing_quote_id` (int) and `_wpis_editorial_note` (string). (2) Replace “Brain Monkey, or just WP Mock” with **PHPUnit using WordPress test bootstrap** for registration tests; add Brain Monkey only if writing pure isolated units. (3) Clarify `register_post_status` for `rejected` and `merged` must work with CPT `quote` capabilities. (4) Keep MCP registration in `wpis-plugin.php` unchanged—load new code via `require` from `src/` bootstrap.
- **Done when:** Checklist in original deliverable + `composer test` + activates cleanly on **`wpis.jasonrouet.com`** (or next deploy there).

### Chantier 2 — Dedup, merge, counter sync

- **Goal:** Merge/unmerge, duplicate helper, counter sync hooks, WP-CLI, tests.
- **Dependencies:** Chantier 1
- **Complexity:** L
- **Deliverables:** `wpis_merge_quote`, `wpis_unmerge_quote`, `wpis_find_potential_duplicates`, Polylang-aware counter sync (feature-detect Polylang; no-op if inactive), **group-level status propagation** per §3e (hooks on status transitions), WP-CLI commands, tests.
- **Decided:** **Group-level moderation** — see §3e (rule). Implement propagation in this chantier alongside merge/counter logic; Polylang Chantier 8 only verifies.
- **Adapted prompt:** Original Chantier 2 + explicitly: (1) counter sync implementation is **canonical in this chantier**; Chantier 8 only verifies with FR/EN content and adjusts Polylang settings—avoid duplicating sync logic in Chantier 8. (2) Implement **moderation status propagation** to all posts in a Polylang group when any sibling’s `quote` status changes (use `transition_post_status` or equivalent; guard against recursion).
- **Done when:** Merge/unmerge + sync covered by tests; WP-CLI smoke-tested.

### Chantier 3 — Block theme skeleton

- **Goal:** `theme.json`, local fonts, template parts, dark/light toggle, no templates yet.
- **Dependencies:** none (parallel with 1)
- **Complexity:** M
- **Deliverables:** Standalone block theme; tokens from mockup; `parts/header.html`, `parts/footer.html`; GDPR-friendly local fonts; theme toggle script; `prefers-color-scheme` + `data-theme`.
- **Decide before start:** Whether to add `@wordpress/scripts` in this chantier or only static theme.json + enqueue (scripts can wait until first block).
- **Adapted prompt:** Original Chantier 3 + replace “templates from parent” with **core block theme fallbacks** (no parent). Fix mockup reference: production theme must not load Google Fonts CDN—local files only.
- **Done when:** Theme activates; editor shows palette/fonts; header/footer visible on fallback templates.

### Chantier 4 — Templates: home + single quote

- **Goal:** `index.html` (feed) + `single-quote.html`; patterns/blocks as needed.
- **Dependencies:** 1, 3
- **Complexity:** L
- **Deliverables:** Home query for `quote`/`publish`; single template with opposing + stats + variants; URL query sort/filter; **infinite “load more” on the home feed** aligned with the mockup (REST or AJAX loading the next page of posts, with a11y: focus management + `aria-busy`), unless a technical blocker forces a short pagination-only interim.
- **Decide before start:** Dynamic blocks vs patterns-only tradeoff (time vs flexibility).
- **Adapted prompt:** Original Chantier 4 + implement **load-more/infinite scroll for the feed** as in the mockup; keep sort/filter behavior consistent with URL or documented JS contract.
- **Done when:** Real quotes render; single view complete; no critical a11y regressions.

### Chantier 5 — Templates: explore + taxonomy + static + 404 + search

- **Goal:** Remaining public templates and patterns.
- **Dependencies:** 4 (patterns reuse)
- **Complexity:** L
- **Deliverables:** Page template explore, `taxonomy-claim_type`, search, 404, static page shells; dynamic blocks as planned.
- **Adapted prompt:** Original Chantier 5 + ensure **Privacy Policy** page exists, linked from footer when RGPD copy exists. **Owner:** Jason Rouet; v1 can be placeholder / Claude-assisted draft; upgrade if traffic or compliance needs require it.
- **Done when:** IA complete; explore + archives work.

### Chantier 6 — Front-end submit form

- **Goal:** Public submission → `pending` quote; confirmation page; spam/rate limits; media cleanup cron.
- **Dependencies:** 1
- **Complexity:** M
- **Deliverables:** `admin-post` handlers; honeypot + rate limit; optional screenshot upload with lifecycle; tests for validation/cron.
- **Decided:** Do **not** expose raw post IDs in public confirmation URLs. **Pattern:** on successful submit, generate an opaque single-use or time-limited token (e.g. random key in post meta, or `hash_hmac( 'sha256', $post_id . '|' . $issued, wp_salt( 'auth' ) )` with expiry), redirect to `/submitted/?token=…`. Validate in the template handler. WordPress core does not ship a “confirmation page” primitive; **nonces** protect actions, not bookmarkable thank-you URLs—so a **server-verified secret** is the standard approach.
- **Adapted prompt:** Original Chantier 6 unchanged in spirit; ensure redirect URL matches Polylang prefix once Chantier 8 lands (add TODO if built before 8).
- **Done when:** End-to-end manual submit works.

### Chantier 7 — Contributor profile

- **Goal:** Private `/profile/` with stats + list; REST endpoints.
- **Dependencies:** 1, 6 (submissions exist)
- **Complexity:** M
- **Deliverables:** `wpis_get_user_stats`, REST routes, `page-profile` template, `noindex`.
- **Adapted prompt:** Original Chantier 7 + REST routes namespaced and capability-checked.
- **Done when:** Logged-in-only; no data leak between users.

### Chantier 8 — Polylang + FR/EN

- **Goal:** Languages, switcher, translation pairs for quotes and pages; **verify** counter sync from Chantier 2.
- **Dependencies:** 1–7 (practically after 5 for content IA; after 2 for sync)
- **Complexity:** L
- **Deliverables:** EN/FR configured; `quote` + taxonomies translatable; switcher styled; static pages linked; admin language column.
- **Decided:** Group-level rule in §3e; this chantier validates behavior in FR/EN and Polylang UI only.
- **Adapted prompt:** Original Chantier 8 + remove duplicate counter-sync implementation—**test and fix only**. Align submit/confirm URLs with Polylang.
- **Done when:** FR/EN navigation works; counters consistent across translations.

### Chantier 9 — Abilities + MCP wiring

- **Goal:** Register `wpis/*` abilities and allowlist on MCP server.
- **Dependencies:** 1, 2 (and MCP Adapter installed)
- **Complexity:** M
- **Deliverables:** Abilities registered on `wp_abilities_api_init`; permissions; descriptions; tests for registration callbacks.
- **Adapted prompt:** Original Chantier 9 + verify against real **MCP Adapter** and **WordPress 6.9** ability APIs; integration smoke test optional.
- **Done when:** Abilities listed and callable by permitted users.

### Chantier 10 — Seed content

- **Goal:** Real quotes + sub-terms + opposing links for demos.
- **Dependencies:** 1–8 for full value
- **Complexity:** Ongoing
- **Deliverables:** Manual admin seed; optional WP-CLI later; corpus review via Claude web (per vision).
- **Done when:** MVP checklist quote count met.

### Chantier 11 — Bots (Mastodon + Bluesky)

- **Goal:** Separate plugins; Action Scheduler; pending posts; dedup via Chantier 2 helper.
- **Dependencies:** 10 (seed baseline)
- **Complexity:** L per platform
- **Done when:** Config UI + logs + safe failure modes.

### Chantier 12 — Browser extension

- **Goal:** MV3 extension posting to Chantier 6 endpoint.
- **Dependencies:** 6
- **Complexity:** L
- **Done when:** Submit from selection works on major browsers targeted.

---

## 3c. Cross-cutting concerns

- **PHPCS:** `wp-coding-standards/wpcs` + project `phpcs.xml.dist`; run via Composer script; optional PHPStan level 5 later.
- **Tests:** **PHPUnit** with **WordPress test suite** bootstrap for plugin integration tests; namespace test classes under `tests/`. Use Brain Monkey only for pure units if needed.
- **ESLint:** Add when theme has JS build (`@wordpress/eslint-plugin`); not required for Chantier 3 if only vanilla enqueue.
- **Git:** branches `feat/chantier-N-short-name`; **Conventional Commits** (`feat:`, `fix:`, `test:`); one PR per chantier when possible.
- **`.cursor/rules/`:** One rule file per package (`wpis.md`) — stack, prefixes `_wpis_` / `wpis_`, text domains, “no Oxford commas” in EN copy; **omit French Typo plugin** until added to vision.
- **Secrets:** `.env` not committed; bot API keys in **wp_options** or env-injected in CI; never log secrets.
- **Deployment:** Primary target **`wpis.jasonrouet.com`** — document the exact deploy path (SFTP, GitHub Actions, etc.) when stable. Cloudflare in front per vision.
- **Staging:** **No** separate staging; **no local WordPress** — tests and smoke checks happen on **`wpis.jasonrouet.com`**. Use backups, small changesets, and maintenance mode if you need to reduce risk.

---

## 3d. Execution order (phased)

**Phase 0 — Tooling:** PHP **8.3.x** / Composer / Node on your machine; PHPCS + PHPUnit scaffold in the **`wpis-plugin`** repo; **two repos** cloned; deploy path to **`wpis.jasonrouet.com`** understood.

**Phase 1 — Parallel foundation:** **Chantier 1** + **Chantier 3** (plugin data + theme shell).

**Phase 2 — Public shell:** **Chantier 4** then **Chantier 5** (demo-able browse/read site).

**Phase 3 — Interaction:** **Chantier 6** → **Chantier 7** (submit + profile).

**Phase 4 — Data integrity before i18n stress:** **Chantier 2** if not already completed after 1 (if you followed strict order 1→2→3, skip reorder). *If you optimized for demo: run Chantier 2 before Chantier 8 in all cases.*

**Phase 5 — Multilingual:** **Chantier 8**.

**Phase 6 — Post-MVP accelerators:** **Chantier 9** (MCP), **Chantier 10** (ongoing), **11**, **12**.

**Fastest first demo:** 1 + 3 → 4 (minimal) → 6 → seed (partial 10) → 5 → 7 → 2 → 8 → 9.

**Safe deferral:** 9, 11, 12 until MVP stable.

---

## 3e. Resolved decisions (April 2026)

### Polylang + WordPress: moderation rule (authoritative)

WordPress stores **`post_status` per post**. Polylang treats each language as a **separate post** linked in a **translation group**. Nothing in core or Polylang automatically keeps moderation status aligned across translations.

**Rule — group-level lifecycle:** For the `quote` CPT, any transition to **`publish`**, **`pending`**, **`rejected`**, or **`merged`** performed in wp-admin on one post in a Polylang translation group **must be applied to all other posts in that group** (same new status), using Polylang’s translation map (`PLL()->model->post->get_translations()` or current API equivalent).

**Rationale:** Matches the vision (one conceptual quote, merged counters, no contradictory FR/EN publication states). Merge/unmerge already implies multi-post translation coordination; moderation should behave the same way.

**Edge cases (document in code):**

- **Single-language post** (no siblings yet): no propagation.
- **New translation added later:** New sibling should inherit the **current** group status from an existing member (or be created already aligned by editorial workflow—avoid FR `publish` while EN is `pending`).

Per-translation divergence for `_wpis_rejection_reason` / `_wpis_moderated_at` is **not** used to mean different outcomes; if stored per post for audit, they can mirror the **same** moderator action timestamp/reason across the group.

### Other decisions

| Topic | Decision |
|--------|----------|
| Git | **Two separate repos:** `jaz-on/wpis-plugin` and `jaz-on/wpis-theme` (not a monorepo). |
| Environment | **No local WordPress.** Build and test by deploying to **`wpis.jasonrouet.com`**. |
| `Requires PHP` | Match server (e.g. **8.3**). Not targeting WordPress.org; no need for a low minimum. |
| Privacy Policy | **Not required** before launch or before the submission form; owner remains Jason Rouet when you add it. |
| Repo layout | GitHub **`wpis-plugin`** + **`wpis-theme`**; WordPress plugin directory on disk is **`wpis-plugin`**. Clone both into one workspace if useful. |
| Opposing / editorial meta | Register in **Chantier 1** (see chantier block). |
| Production PHP | **8.3.x** on host. |
| Submitted page URL | **Opaque token** (HMAC or random meta), not bare `?p=ID` — see Chantier 6. |
| Polylang | **Polylang Free** for MVP; revisit Pro only if needed. |
| Theme repo / slug | **wpis-theme** — repo, folder, GitHub slug, text domain. |
| Infinite scroll / load more | **In scope** for home feed (Chantier 4), per mockup. |
| Staging | **None** — same live site for verification; mitigate with backups and careful deploys. |


---

## Document history

- Generated from handoff review April 2026. Update as decisions land.
- April 2026: §3e filled with moderation rule + Jaz decisions (layout, PHP 8.3, Polylang Free, tokens, infinite scroll in scope, no staging).
- April 2026: separate repos, deploy-only testing on `wpis.jasonrouet.com`, flexible `Requires PHP`, Privacy Policy not a launch blocker.