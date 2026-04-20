# WordPress Is — Cursor build plan

**Purpose:** hand off the technical build from Claude web chat to Cursor, where code work belongs.

**How to use this doc:**
Each chantier below is designed as **one focused Cursor conversation**. Copy the prompt into a new Cursor chat, work the chantier to completion, commit, then move on. Don't try to combine chantiers — context windows and Cursor's agent work best on bounded goals.

---

## Prerequisites before starting

### Local dev setup
- [ ] WordPress 6.9.4 local install (Local WP, DDEV, Lando — your choice)
- [ ] Git repo initialized: `github.com/jaz-on/wpis-plugin` (plugin) + `github.com/jaz-on/wpis-theme` (theme, separate repo)
- [ ] Node 20+, Composer, WP-CLI available in Cursor terminal
- [ ] Polylang Free installed + activated (for the multilingual foundations)
- [ ] MCP Adapter kept installed but not touched for now

### Handoff inputs (all from this Claude conversation)
- `wordpress-is-vision.md` — the spec
- `wordpress-is-mockup.html` — the design reference (open it in a browser while working)
- `wpis-plugin.zip` — v0.1.0 scaffold from the **`wpis-plugin`** repo (unzip into `wp-content/plugins/wpis-plugin/` as starting point, or extract contents into your fresh repo)

### Rules of engagement with Cursor
- **One chantier, one branch, one PR.** Never mix concerns.
- **Commit incrementally**, even mid-chantier. Cursor's agent can get lost; having commits is your safety net.
- **Ask Cursor to write tests for anything non-trivial.** Not for views/templates, but yes for data layer, hooks, meta handlers.
- **When stuck, come back to Claude web chat** for design decisions, not to Cursor.

---

## Roadmap overview

| # | Chantier | Repo | Estimated effort | Blockers |
|---|----------|------|------------------|----------|
| 1 | Plugin foundations: CPT + taxonomies + meta | `wpis-plugin` | 1 session | — |
| 2 | Dedup + merge + counter sync logic | `wpis-plugin` | 1 session | #1 |
| 3 | Block theme skeleton with `theme.json` | `wpis-theme` | 1 session | — |
| 4 | Templates: home feed + single quote | `wpis-theme` | 1-2 sessions | #1, #3 |
| 5 | Templates: explore + taxonomy archive + static pages | `wpis-theme` | 1 session | #4 |
| 6 | Front-end submit form | `wpis-theme` or `wpis-plugin` | 1 session | #1 |
| 7 | Contributor profile page | `wpis-theme` + `wpis-plugin` | 1 session | #1, #6 |
| 8 | Polylang integration + FR/EN setup | both | 1 session | #1-#5 |
| 9 | Abilities + MCP wiring | `wpis-plugin` | 1 session | #1, #2 |
| 10 | Seed content (manual via admin, then scripted) | content work | ongoing | #1-#8 |
| 11 | Bots: Mastodon + Bluesky | `wpis-bot-mastodon`, `wpis-bot-bluesky` | 2 sessions each | #10 |
| 12 | Browser extension companion | separate repo | 2-3 sessions | #6 |

**Realistic MVP cut-off:** chantiers 1 through 8 + some seed content. That's a functional site you can show publicly. Everything else can come after.

---

## Chantier 1 — Plugin foundations

**Repo:** `wpis-plugin`
**Goal:** CPT `quote`, taxonomies (`sentiment`, `claim_type`), meta fields, admin list refinements. No custom admin UI — just native WP with sensible defaults.

### Prompt to paste into Cursor

```
You are helping me build the foundations of a WordPress plugin whose directory and text domain are **`wpis-plugin`** (GitHub **`jaz-on/wpis-plugin`**).

Context: read the vision doc at the root of this workspace (wordpress-is-vision.md) for the full picture. Focus specifically on the "Database structure" and "Submission flow" sections.

Current state: the plugin v0.1.0 exists with an MCP server registration. You can keep that code as-is — don't touch it in this chantier.

Goal for this chantier:
1. Register a custom post type `quote` with:
   - Statuses: pending (default), publish, rejected, merged
   - Supports: title (optional, can be auto-generated), editor (the quote text), author, custom-fields, revisions
   - Public: true, but archive only for `publish` status
   - Labels in English
   - REST-enabled (show_in_rest: true)

2. Register two taxonomies bound to `quote`:
   - `sentiment` (flat): positive, negative, neutral, mixed — pre-seed these terms on plugin activation
   - `claim_type` (hierarchical): performance, security, ease-of-use, community, ecosystem, business-viability, accessibility, modernity — pre-seed as top-level terms on activation

3. Register these post meta fields on `quote`, all REST-enabled with show_in_rest:
   - _wpis_counter (integer, default 1)
   - _wpis_source_domain (string)
   - _wpis_source_platform (string, controlled via a PHP const list: mastodon, bluesky, linkedin, youtube, reddit, blog, x, hn, other)
   - _wpis_parent_id (integer, for merges)
   - _wpis_rejection_reason (string enum: off-topic, spam, duplicate, low-value, other)
   - _wpis_moderated_at (integer, unix timestamp)
   - _wpis_ai_snapshot (object, stores AI suggestions at submission)
   - _wpis_submission_source (string enum: form, extension, bot-mastodon, bot-bluesky)

4. Refine the admin quote list table:
   - Add columns: counter, sentiment, claim_type, source_platform, submission_source
   - Make sortable by counter and submission date
   - Add a filter dropdown by submission_source

5. Register the "rejected" and "merged" post statuses properly via register_post_status() — they should appear in the admin post list filters.

6. Write unit tests (PHPUnit + Brain Monkey, or just WP Mock) for the registration functions, covering: CPT registered, taxonomies registered, meta fields registered, statuses registered.

7. Ensure the plugin activates cleanly on a fresh WordPress 6.9.4 install with no PHP notices/warnings.

Requirements:
- PHP 7.4 minimum, prefer 8.0+ syntax where available
- Follow WordPress coding standards (PHPCS with WordPress ruleset)
- Use namespaces: `WPIS\Core\*`
- Organize in `src/` with PSR-4 autoloading via Composer
- Add the plugin header block and uninstall.php already in the repo — keep them, extend uninstall.php to clean up the registered terms if the user explicitly opted in (add a setting later, for now just leave a TODO)

Commit after: CPT, taxonomies, meta, admin columns, tests. Open a PR from a feature branch.
```

### Deliverable

- Plugin activates cleanly, CPT + taxonomies + meta visible and REST-exposed
- Pre-seeded terms on activation
- Refined admin list
- Tests passing

---

## Chantier 2 — Dedup, merge, counter sync

**Repo:** `wpis-plugin`
**Goal:** the logic that makes the project not a dumpster fire: deduplication helpers, merge operation, counter synchronization across Polylang translations.

### Prompt to paste into Cursor

```
We're extending the **`wpis-plugin`** WordPress package.

Read wordpress-is-vision.md sections "Deduplication and merge" and "Multilingual model (with Polylang)" carefully — they define the behavior precisely.

Goal for this chantier:

1. Merge operation:
   - Function `wpis_merge_quote( int $source_id, int $target_id ): WP_Error|true`
   - On merge:
     - Source quote status becomes `merged`
     - Source meta `_wpis_parent_id` = target_id
     - Target quote meta `_wpis_counter` = target.counter + source.counter (NOT +1 — sum the counters, since source might itself have absorbed duplicates before)
     - Source Polylang translations (if any) each merge into target's corresponding language (A-FR merges into B-FR, A-EN into B-EN)
     - Emit a `wpis_quote_merged` action hook with ($source_id, $target_id)
   - Flat hierarchy enforcement: if source has existing children pointing to it (`_wpis_parent_id = source.id`), re-parent them directly to target

2. Unmerge operation:
   - Function `wpis_unmerge_quote( int $quote_id ): WP_Error|true`
   - Restores quote to `publish`, clears _wpis_parent_id
   - Decrement target counter by source counter
   - Emit `wpis_quote_unmerged` action

3. Counter sync across Polylang:
   - Hook into Polylang's translation save
   - When _wpis_counter changes on any quote in a translation group, propagate to all other translations in the same group
   - Guard against infinite loops (a static flag or transient)

4. Duplicate detection helper:
   - Function `wpis_find_potential_duplicates( string $text, string $lang = 'en', int $threshold = 70 ): array`
   - Returns a ranked list of quote IDs with similarity score
   - First pass: simple similarity (similar_text() or levenshtein on normalized strings — lowercase, strip punctuation, collapse spaces)
   - Second pass (TODO comment in code): hook for future semantic similarity (embeddings). Don't implement the embeddings now, just leave the hook.
   - This helper is used by moderation and by the submission flow to show "potential duplicates" to moderators

5. WP-CLI commands:
   - `wp wpis merge <source> <target>`
   - `wp wpis unmerge <quote_id>`
   - `wp wpis find-duplicates <quote_id>` — output ranked list

6. Tests covering all of the above. This is the data-integrity core of the project — tests are non-negotiable here.

Commit in logical chunks. Open a PR.
```

### Deliverable

- Merge/unmerge works correctly for single-language and Polylang-linked quotes
- Counters stay in sync across translation groups
- Duplicate detection helper returns reasonable candidates
- WP-CLI commands functional
- Full test coverage on this layer

---

## Chantier 3 — Block theme skeleton

**Repo:** `wpis-theme`
**Goal:** empty but fully-configured block theme with `theme.json` matching mockup design tokens, font-face, template parts (header, footer), ready to receive templates.

### Prompt to paste into Cursor

```
We're creating a new WordPress block theme from scratch called wpis-theme.

Design reference: open wordpress-is-mockup.html in your browser and inspect the CSS. The mockup is the source of truth for design tokens.

Extract design tokens from the mockup:
- Typography:
  - Display: Fraunces (variable font, opsz + weight axes)
  - Monospace: JetBrains Mono
- Colors (light mode):
  - background: #f5f2ea
  - ink: #1a1a1a
  - muted: #6b6b6b
  - line: #1a1a1a
  - accent: #d64545
  - accent-soft: #fbe8e8
  - paper: #fdfbf5
  - sentiment: positive #2d6a4f, negative #9b2226, neutral #6b6b6b, mixed #b8860b
- Colors (dark mode): mirror values, check the mockup CSS :root[data-theme="dark"] block
- Spacing: default WP scale is fine
- Layout: content width 720px, wide 1200px, full = viewport

Goal for this chantier:

1. Scaffold the theme with the correct files:
   - style.css (header only, required by WP)
   - theme.json (the real config)
   - functions.php (minimal — font enqueueing, potentially image size registration)
   - templates/ (empty, templates come in chantier 4)
   - parts/ (header.html, footer.html)
   - patterns/ (empty, later chantiers)
   - assets/fonts/ (host Fraunces + JetBrains Mono locally, no Google Fonts CDN for GDPR)

2. theme.json should define:
   - All color palette entries (both modes if possible, or just light + we handle dark via CSS vars)
   - Typography: font families, font sizes (fluid), line heights
   - Layout widths
   - Spacing
   - Custom CSS props exposed via custom.*
   - appearanceTools enabled
   - settings.typography.fluid enabled

3. Template parts:
   - header.html — matches mockup header: site title on the left full-height, right side with two rows (lang switcher + theme toggle on top, main nav below). Use core/site-title, core/navigation, core/template-part.
   - footer.html — matches mockup footer: "WordPress Is… a Jasonnade project · open-source · community-driven" line + trademark line below. Keep it minimal.

4. Font loading:
   - Download Fraunces (variable) and JetBrains Mono from Google Fonts (or fontsource.org for Fraunces variable) to assets/fonts/
   - Register them in theme.json under settings.typography.fontFamilies with local font files
   - No external font requests (GDPR)

5. Verify theme activates cleanly, no PHP notices, templates from parent still show content (no templates in theme yet = fallback to defaults).

6. Light mode and dark mode both work via `prefers-color-scheme` AND a manual toggle (JS to flip data-theme attribute).
   - Dark mode JS can be in a small enqueued script OR inline in header.html
   - Respect user preference (localStorage)

7. No parent theme — this is a standalone block theme.

Commit the scaffold. Open a PR.
```

### Deliverable

- Block theme activates cleanly
- Design tokens accessible in the editor (you see the palette and fonts in the Style inspector)
- Header and footer render on every page (with default WP templates)
- Dark mode toggle works

---

## Chantier 4 — Templates: home + single quote

**Repo:** `wpis-theme`
**Goal:** the two most important templates of the site. Everything else flows from these.

### Prompt to paste into Cursor

```
We're building the first two templates of wpis-theme.

Reference: wordpress-is-mockup.html screens "home" (01) and "detail" (02). Open in browser, inspect the CSS and HTML, reproduce as closely as possible using native block primitives.

Goal for this chantier:

1. templates/index.html — the home page displaying a feed of `quote` posts.
   - Hero section at top: eyebrow, big "WordPress is…" title with red italic dots, intro paragraph, stats row (total quotes, platforms, languages)
     - Stats should come from dynamic blocks or a custom block — acceptable here to register one custom block via block.json (server-side rendered)
   - Feed: use core/query with post-type=quote, status=publish, per-page=18
   - Quote card pattern — register as a synced pattern or as a block template-part
   - Sort buttons (Recent/Most repeated/Random) and filter selects (Sentiment/Claim/Platform): these are client-side interactive. Register as a custom block or inline script. Start simple: sort = URL query param, filter = URL query param, no JS magic. Page reloads on change. It's fine.
   - "Load more" button: server-side pagination for now (next page), not infinite scroll. Infinite scroll can come later.

2. templates/single-quote.html — the individual quote page.
   - Breadcrumb: Feed / [claim_type] / this quote
   - Claim meta line: claim_type tag + submission count info
   - Big quote as h1 (core/post-title with custom styling, or custom block)
   - "Someone disagrees" block — this is the opposing view section. For MVP, make it manual: a custom field on the quote `_wpis_opposing_quote_id` pointing to another quote, rendered as a callout block
   - Spread stats: submissions, platforms, languages, variants merged — these are computed. Custom dynamic block.
   - Variants compact list: all quotes with `_wpis_parent_id = this.id` (the merged ones)
   - Editorial note: core/paragraph with specific styling — this is per-quote manual content stored as a custom meta `_wpis_editorial_note` OR just as part of post_content after the main quote. Decide what's cleaner, document in a comment.

3. Register two patterns for reuse:
   - `wpis/quote-card` — the card shown in feeds
   - `wpis/opposing-block` — the "someone disagrees" block

4. Accessibility:
   - Use semantic HTML (proper h1/h2/h3 hierarchy)
   - Quote cards are <a> elements wrapping the full card (focus-visible outline)
   - Breadcrumb uses <nav aria-label="Breadcrumb">
   - Skip-to-content link in header.html

5. Minimal JS:
   - One script that handles the client-side theme toggle (if not done in chantier 3)
   - No other JS yet — filter/sort work via URL params + page reload

Performance note: the feed page will have 18 quote cards with meta lookups. Use the wpis-counter meta as an indexed meta query if needed. Don't over-engineer, but don't ship N+1 queries either.

Commit in chunks. Open a PR.
```

### Deliverable

- Home page displays real quotes from the database in the mockup style
- Single quote page displays one quote with its opposing view and stats
- Sort + filter work via URL params
- Pagination via native "older posts"

---

## Chantier 5 — Templates: explore + taxonomy + static pages

**Repo:** `wpis-theme`
**Goal:** the rest of the public site templates.

### Prompt to paste into Cursor

```
Building out the remaining templates of wpis-theme.

Reference: mockup screens 03 (explore), 04 (taxonomy), 05 (search), 06 (about), 07 (how), 08 (submit), 09 (submitted), 10 (empty/404).

Goal for this chantier:

1. templates/page-explore.html — a page template assigned to a Page called "Explore"
   - List each claim_type parent term with: count, short description (from term description), sentiment breakdown bar (negative/positive/mixed ratio)
   - Register a dynamic block `wpis/tax-overview-card` that renders one card given a term ID
   - A loop over all top-level claim_type terms rendering the block for each

2. templates/taxonomy-claim_type.html — archive template for claim_type terms
   - Hero: term name + description (short, one-liner)
   - Subcat bar: if current term has children, list them as chips with counts; if not, hide the bar
   - Quote feed (same as home feed, filtered by this term)

3. templates/taxonomy-sentiment.html — archive for sentiment terms (optional, can fall back to default taxonomy template)

4. templates/search.html — search results
   - core/search block prominently at top
   - core/query filtered to post-type=quote with search context
   - <mark> highlighting of matched terms

5. Static content pages (Pages in WP admin, not templates):
   - About — content from mockup screen 06
   - How it works — content from mockup screen 07, as 6 numbered steps (use core/list or custom pattern)
   - Submit — the submission form lives here but form itself is chantier 6. For now just a placeholder or the static copy + RGPD notice

6. templates/404.html — from mockup screen 10
   - Big red "?" character
   - "WordPress is… not here." heading
   - Link back to Explore

7. Register patterns for reusable bits:
   - `wpis/tax-hero`
   - `wpis/subcat-bar`
   - `wpis/how-step` (numbered step used on How it works)

8. Accessibility:
   - Proper heading hierarchy
   - <mark> on search highlights with aria-label
   - Skip links where useful

Commit in chunks. Open PR.
```

### Deliverable

- Explore page lists all claim types with sentiment breakdown
- Taxonomy archive pages work for all terms, with subcat navigation when children exist
- Search works
- Static pages (About, How it works, Submit placeholder) rendered
- 404 in project style

---

## Chantier 6 — Front-end submit form

**Repo:** `wpis-plugin` (form handling) + theme (form markup/style)
**Goal:** a working submission form that creates a pending `quote` post.

### Prompt to paste into Cursor

```
Build the front-end submission flow.

Reference: mockup screens 08 (submit) and 09 (submitted/confirm).

Approach: plain HTML form + WP admin-post.php handler. No heavy JS framework.

Goal for this chantier:

1. In wpis-theme, create a block pattern `wpis/submit-form` and use it in the Submit page.
   - Fields: quote text (textarea required if no screenshot), screenshot (file input optional), source URL (text input optional), RGPD consent checkbox (required), honeypot field (hidden, catches bots)
   - Submit button posts to admin-post.php with action=wpis_submit_quote
   - Nonce field with wp_nonce_field()

2. In this codebase, register the admin-post handler:
   - admin_post_wpis_submit_quote AND admin_post_nopriv_wpis_submit_quote
   - Validation:
     - Nonce valid
     - Honeypot empty (if filled, silently reject as spam)
     - At least text OR screenshot provided
     - Text length < 1000 chars
     - RGPD consent ticked
   - Processing:
     - Extract domain from URL if provided
     - If screenshot: upload to WP media library, flag as temporary (custom meta `_wpis_temporary_upload` = 1, cleanup cron will delete these after 7 days if attached quote was rejected or after quote was validated)
     - Create quote post with status=pending, author=current_user_id or 0 if anonymous
     - Set meta: _wpis_source_domain, _wpis_source_platform (derive from domain), _wpis_submission_source='form'
     - Fire action `wpis_quote_submitted` with post ID (for future AI pre-tagging hook)
   - Redirect to /submitted/?id={hash_of_post_id} on success

3. Create /submitted/ page:
   - Shows "Thanks, your submission is being reviewed"
   - Shows count of pending submissions (transparency indicator, queried on render)
   - Shows a short preview of what was submitted (decode hash, fetch post)
   - CTA to create account / back to feed / submit another

4. Spam protection:
   - Honeypot (already mentioned)
   - Rate limiting: one submission per IP per 5 minutes (transient-based check)
   - Max length enforced server-side

5. Cron job `wpis_cleanup_temporary_uploads` running daily: delete attachments flagged as temporary AND attached to rejected/still-pending quotes older than 7 days.

6. Write tests for: validation logic, rate limiting, cleanup cron.

Commit. Open PR.
```

### Deliverable

- Working form on public site
- Submissions create pending quote posts
- Confirmation page shown after submit
- Basic spam protection in place
- Temporary uploads cleaned up automatically

---

## Chantier 7 — Contributor profile

**Repo:** `wpis-theme` + `wpis-plugin`
**Goal:** the private /profile/ page for logged-in contributors.

### Prompt to paste into Cursor

```
Build the contributor profile page.

Reference: mockup screen 11 (profile).

Approach:
- Login required. Redirect anonymous users to wp-login.
- Page is completely private — user sees only their own data.
- No role custom needed; any Subscriber role works.

Goal for this chantier:

1. In the **`jaz-on/wpis-plugin`** repository:
   - Function `wpis_get_user_stats( int $user_id ): array` returning:
     - total_submitted, validated, pending, rejected, merged, acceptance_rate (percentage)
   - Register REST endpoint /wp-json/wpis/v1/my-stats returning the current user's stats (auth required)
   - Register REST endpoint /wp-json/wpis/v1/my-quotes returning current user's own submissions with status

2. In wpis-theme:
   - templates/page-profile.html assigned to a Page called "My profile"
   - Profile header: "Your contributions", member-since date
   - Stats grid: 4 cards (total, validated, acceptance rate, pending) — custom dynamic block `wpis/user-stats` that fetches from the REST endpoint
   - Submissions list: the user's own quotes with status badges — custom dynamic block `wpis/user-submissions`

3. Privacy: profile page enforces login and renders nothing for guests (redirect). Also set X-Robots-Tag: noindex on this page via a header hook.

4. Access rights: a user can never see another user's profile data. If someone constructs a URL like /profile/?user_id=X, ignore it — always use get_current_user_id().

5. Tests for the stats calculation and the REST endpoints (auth enforced).

Commit. Open PR.
```

### Deliverable

- Logged-in users see their private profile with stats and submissions list
- Anonymous users redirected to login
- Profile not indexable by search engines

---

## Chantier 8 — Polylang + FR/EN

**Repo:** `wpis-plugin` + `wpis-theme`
**Goal:** multilingual setup with FR + EN, with the custom peer-translation model documented in vision.md.

### Prompt to paste into Cursor

```
Integrate Polylang and configure FR/EN.

Prerequisites:
- Polylang Free installed and activated
- Chantiers 1-7 complete

Read wordpress-is-vision.md section "Multilingual model (with Polylang)" — this is specific and custom.

Goal for this chantier:

1. Configure Polylang programmatically (via plugin activation hook if possible):
   - Enable languages: English (en_US) and French (fr_FR)
   - Make `quote` a translatable post type
   - Make `sentiment` and `claim_type` translatable taxonomies
   - Make meta fields synced across translations: _wpis_source_domain, _wpis_source_platform, _wpis_submission_source, _wpis_counter (COPIED, not shared — we sync manually in chantier 2's counter sync code)
   - Make sensitive meta NOT synced: _wpis_rejection_reason, _wpis_parent_id, _wpis_moderated_at (each translation has its own moderation state theoretically)

2. Implement the counter sync that chantier 2 scaffolded: on _wpis_counter update on any quote, propagate to all translations in the Polylang group.

3. Language switcher in the header:
   - Replace the static EN/FR switcher with Polylang's real language switcher
   - Style it to match mockup (small inline, bordered letters)

4. Translations for static pages (About, How it works, Submit, Submitted):
   - Create FR versions as Polylang translations
   - For now, copy the English content and ask Claude web chat for FR translation in a separate conversation
   - Link them via Polylang

5. On the admin, make sure:
   - Quote list table has a language column
   - Editing a quote shows the Polylang translation panel
   - Taxonomies can be filtered by language in admin

6. URL structure: /en/... and /fr/... prefixes (Polylang default). Confirm the redirect from root (/) goes to user's browser language.

7. Ensure all REST endpoints respect the current language context when queried.

8. Test that submitting a quote in FR creates an FR-only post (no auto-translation for MVP — AI translation comes in a later chantier). Moderation handles the translation manually.

Commit. Open PR.
```

### Deliverable

- FR and EN both active
- Quotes can have translations linked
- Language switcher works
- Counter syncs across translations
- Static pages translated

---

## Chantier 9 — Abilities + MCP wiring

**Repo:** `wpis-plugin`
**Goal:** expose WPIS operations as MCP abilities, so Claude (or any MCP client) can manage content programmatically.

**Only do this once chantiers 1-8 are done.** MCP is an accelerator, not a foundation.

### Prompt to paste into Cursor

```
Wire up MCP abilities for WPIS operations.

Prerequisites:
- MCP Adapter plugin active
- **wpis-plugin** v0.1.0 already registers a dedicated MCP server at /wp-json/wpis/v1/wpis
- Chantiers 1-2 give us the data model and merge logic to expose

Goal for this chantier:

1. Register the following abilities via wp_register_ability(), on the wp_abilities_api_init action:

   - `wpis/quote-create` — create a new quote
     - Input: text (required), language (required, en or fr), source_url (optional), source_platform (optional), status (optional, defaults to pending)
     - Permissions: manage_options OR user can create posts
     - Output: { quote_id, status, url }

   - `wpis/quote-update` — update a quote's content or status
     - Input: quote_id, text?, status?, sentiment?, claim_type?
     - Permissions: edit_post capability for this quote
     - Output: { quote_id, updated_fields }

   - `wpis/quote-merge` — merge two quotes (wraps wpis_merge_quote from chantier 2)
     - Input: source_id, target_id
     - Permissions: manage_options
     - Output: { target_id, new_counter }

   - `wpis/quote-find-duplicates` — find potential duplicates (wraps wpis_find_potential_duplicates)
     - Input: text, language, threshold?
     - Permissions: edit_posts
     - Output: array of { quote_id, score, text_preview }

   - `wpis/stats-summary` — overall site stats
     - Input: none
     - Permissions: read (public)
     - Output: { total_quotes, by_status: {...}, by_sentiment: {...}, by_claim_type: {...}, by_language: {...} }

2. Update the MCP server registration in wpis-plugin.php to expose these abilities (add them to the $abilities filter callback).

3. Tests: for each ability, test that it's registered, has correct permission callback, and that execution returns expected output shape.

4. Document each ability with a clear `description` field — remember, these descriptions are what AI agents read to decide when to use them.

5. Optional: add `meta.mcp.public = true` on all these abilities so they'd also be available on the default MCP server if someone uses that instead.

Commit. Open PR.
```

### Deliverable

- Claude (or any MCP client) can create, update, merge quotes and read stats through MCP
- Abilities properly permissioned
- Tested

---

## Chantier 10 — Seed content

**No repo, this is content work.**
**Goal:** enough real quotes to make the site feel alive.

### Approach

1. Manual seed via WP admin: Jaz creates 30-50 quotes from his own LinkedIn feed, Mastodon, community friends. Uses the admin screens directly.

2. Create the initial claim_type children (sub-topics under each parent):
   - Security: Plugins, Core, E-commerce, Updates, Hosting
   - Performance: Hosting, Plugins, Images, Database
   - Community: WordCamps, Contributors, Forums, Values
   - etc. Refine based on real content.

3. Link opposing views: for ~10 seeded quotes, manually link an opposing quote via _wpis_opposing_quote_id. This demonstrates the "portrait of a claim" feature.

4. Once there's enough content, Claude web chat can help review the corpus for balance (ratio of positive/negative, spread across claim types, language distribution).

**Back to Claude web chat for:**
- Claim type naming refinements once real content surfaces gaps
- Editorial notes on some featured quotes
- FR translations of seeded EN quotes (or vice versa)

---

## Chantier 11 — Bots (Mastodon + Bluesky)

**Repos:** `wpis-bot-mastodon`, `wpis-bot-bluesky` (separate plugins)
**Goal:** automated candidate discovery from public firehoses.

**Do not attempt before chantier 10 has at least 50 seeded quotes.** The bots need validated patterns to learn from.

### High-level plan (full prompts when ready)

- Each bot = a separate plugin, activated independently
- Bot polls its platform's public API periodically (Action Scheduler cron)
- Simple string matching on "WordPress is…" variants (multiple languages)
- Creates `pending` quote posts via the plugin API (`wpis-plugin` repo) with _wpis_submission_source = bot-mastodon or bot-bluesky
- Deduplication via wpis_find_potential_duplicates before creating — if a close match exists, just increment counter
- Admin settings page per bot: API credentials, polling interval, keywords, on/off switch
- Observability: log every run with candidate count, created count, deduped count

Prompts for these will be written when we're ready to build them.

---

## Chantier 12 — Browser extension

**Repo:** `wpis-extension` (separate from WP entirely)
**Goal:** Firefox + Chrome extension that lets users contribute from any page.

**High-level plan:**
- Manifest V3
- Context menu item: "Send to WordPress is…"
- On click: open a popup pre-filled with selected text + page URL
- User can edit, add notes, submit
- Submits via the form endpoint (same as chantier 6 form)
- Authenticated? Optional — if user has logged in once via the extension, their user_id is attached. Otherwise anonymous.

Defer until the site is live and has some traction.

---

## Notes on working with Cursor effectively

### Cursor settings worth enabling
- PHPCS integration with WordPress ruleset
- PHPStan level 5+ for the plugin code
- `.cursor/rules/` file with project conventions (copy key decisions from vision.md)

### .cursor/rules template

Create `.cursor/rules/wpis.md` in each repo:

```
# WordPress Is — project rules

## Stack
- WordPress 6.9.4
- PHP 8.0+ syntax, 7.4 minimum compatibility
- Block theme (FSE), Polylang, MCP Adapter
- Custom plugin **`wpis-plugin`** in **`jaz-on/wpis-plugin`** (this repo OR sibling)

## Conventions
- PSR-4 autoload, namespace WPIS\Core\*
- WordPress coding standards for hooks, filenames, function names
- Meta field prefix: _wpis_
- Action/filter prefix: wpis_
- Text domain: wpis-plugin (or wpis-theme per repo)
- No Oxford commas in English copy
- French uses proper French typographic rules (we have a plugin for that: French Typo)

## Multilingual
- Polylang, peer translation model (no master)
- English is the matching pivot for deduplication
- Some meta copied across translations, some not — see wpis-plugin Polylang integration

## Testing
- PHPUnit for plugin code
- Mandatory tests for: data layer, merge logic, permissions, REST endpoints
- Optional for: templates, CSS, patterns

## Git
- Feature branches from main
- One chantier = one PR
- Commit messages in English, conventional commits format (feat:, fix:, docs:, refactor:)
```

### When Cursor goes off the rails
- Stop. Roll back to last commit. Restart the chat with a smaller prompt.
- If Cursor hallucinates a WordPress API function, insist on consulting the official docs (developer.wordpress.org)
- If Cursor proposes a third-party library for something WordPress does natively, reject it

---

## Back to Claude web chat for

- Editorial copy tweaks (about page, how it works, submission copy, empty states)
- Design refinements if something looks off once it's live
- Taxonomy naming once real quotes reveal gaps
- Content review: is the corpus balanced? Any red flags in the moderation queue?
- FR/EN translations of static content and seeded quotes
- LinkedIn + Mastodon announcement posts when you ship MVP

---

## Success criteria for "MVP ready"

- [ ] Chantiers 1-8 complete and merged
- [ ] At least 30 quotes seeded, covering most claim types and both languages
- [ ] Submission form tested end-to-end with 5+ real submissions reviewed
- [ ] Site passes accessibility audit (keyboard nav, contrast, screen reader smoke test)
- [ ] Site passes Lighthouse perf >85 on home and taxonomy pages
- [ ] RGPD mentions legal (privacy policy page) — Claude web chat can help draft
- [ ] Domain strategy decided: stay on wpis.jasonrouet.com or move to dedicated domain

When these are checked, announce it publicly on LinkedIn and Mastodon, open submissions to the community, and start thinking about bots.

---

## Final note

This plan is a map, not a script. Every chantier will surface things we didn't anticipate. That's fine. The vision doc is the north star — if a decision comes up that's not covered, ask "does this serve the vision?" not "what does the plan say?".

Good luck. And come back to Claude web chat anytime for the non-code stuff.
