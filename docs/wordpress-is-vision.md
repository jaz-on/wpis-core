# WordPress is...

*A living archive of what people actually say about WordPress, and why context matters.*

**Last updated:** April 2026
**Project lead:** Jason Rouet (Jasonnade, `jaz_on`)
**Repository target:** `github.com/jaz-on/wpis-plugin` (plugin codebase; deploy folder on site remains `wpis-plugin/`)
**Site:** `wpis.jasonrouet.com` (subdomain, will migrate later)

---

## The vision

"WordPress is..." is a community-driven website that collects quotes from across the web completing the phrase *WordPress is [something]*. Good, bad, contradictory, nuanced: all of it.

The goal isn't to defend WordPress. It's to hold up a mirror.

When huge numbers of people over two decades have said completely opposing things about the same project, one that powers a large slice of the web and stands as one of the largest open-source efforts out there, that tells you something. The truth isn't binary. It depends on context: who's speaking, what they're building, their experience, their use case.

## What the site is really about

It's a rebuttal to lazy thinking. Not "WordPress is actually good", but "if you're going to criticize it or praise it, own your actual use case instead of speaking universally."

The site exists to:

- Surface the sheer volume and variety of arguments made about WordPress
- Show that contradictions often coexist and are both valid in different contexts
- Give the community counter-arguments and facts to push back on dismissive takes
- Help people who don't yet know WordPress see that it's more complex than the hot takes suggest
- Remind people that WordPress isn't just a CMS: it's an ecosystem, a community, a philosophy about the open web

## The deeper argument

WordPress is a tool in the web commons. It's helped millions of people publish and build online without needing to be programmers or have big budgets. Dismissing WordPress isn't just a tech opinion: it's a stance on what the web should be.

Criticism is welcome and healthy when it's grounded: performance issues when poorly configured, plugin ecosystem problems, accessibility gaps, security failures. Those are fair.

What the site pushes back on is the dismissal: "WordPress is bloated trash" from people who've used it once or who've never entered the community: the WordCamps, the meetups, the friendships, the careers built on it. A lot of people came for the code and stayed for everything else.

Saying "WordPress isn't for me" is fine. Saying "WordPress is bad" without context is missing the point.

## Who it's for

- **The WordPress community**, when they need counter-arguments or a reference to link to
- **People considering WordPress** who've only heard the hot takes
- **People dismissing WordPress** who might realize the conversation is more nuanced than they thought

---

## Current state (April 2026)

### What's done

- **Vision** captured and stable
- **Mockup** designed and iterated (11 screens, mobile-first, dark mode, dynamic filters + sort + infinite load, accessibility baseline)
- **WordPress install**: vanilla 6.9.4 on `wpis.jasonrouet.com`
- **MCP Adapter** plugin installed and active
- **`wpis-plugin` plugin v0.1.0** scaffolded: registers a dedicated MCP server exposing 2 core abilities
- **Stack choices** validated (see below)

### What's pending

- MCP bout-en-bout connectivity (Claude Desktop → WordPress): not blocking, see "Tooling strategy" below
- All the actual build work (theme, CPT, taxonomies, abilities, content, multilingual, bots)

### Decision: technical work moves to Cursor

Building the theme, plugin and content through Claude web chat is too constrained for the volume of work ahead. From here on:

- **Cursor handles code and repeatable technical tasks** (plugin code, block theme, templates, tests, deployment)
- **Claude web chat keeps design, strategy, copywriting, content review, audits** (where conversation and iteration matter more than tooling)
- **MCP integration is deferred** as a nice-to-have: not a prerequisite. It can come back later once the site has content, as a way to accelerate moderation and content ops.

---

## Stack decisions

### Platform

- **WordPress 6.9.4** (latest stable, ships with Abilities API in core)
- Hosting: existing setup under `jasonrouet.com`
- Cloudflare in front (existing)

### Theme

- **Block theme (FSE)**: no classical PHP templates unless absolutely necessary
- **Base**: either TT5/TT6 as parent or fork from scratch with minimal `theme.json`
- **Design tokens** from mockup: Fraunces (display), JetBrains Mono (meta), custom palette, dark mode support
- **Template parts**: header, footer
- **Templates**: home (feed), single-quote, archive-claim_type, page-submit, page-about, page-how, 404
- **Patterns**: quote card, explore tax grid, subcat bar, submit form

### Plugins (custom)

- **`wpis-plugin`**: CPT, taxonomies, meta fields, MCP server, content-level logic (deduplication helpers, counter sync)
- **Bot plugins (later)**: `wpis-bot-mastodon`, `wpis-bot-bluesky`, each autonomous

### Plugins (third-party)

- **Polylang**: multilingual (FR/EN at launch)
- **MCP Adapter**: AI agent integration (kept installed, will be wired up later)
- **Action Scheduler**: background jobs (bots, merges, etc.)

### Content model (recap)

One custom post type `quote` with WordPress-native statuses (`pending` / `publish` / `rejected` / `merged`).

**Taxonomies**: `sentiment` (flat), `claim_type` (hierarchical: parent terms + sub-topics as children).
**Meta fields**: `counter`, `source_domain`, `source_platform`, `parent_quote_id` (for merges), `rejection_reason`, `ai_suggestions_snapshot`, `submission_source`, `contributor_id`.

**Multilingual**: one post per language, linked via Polylang's translation group. English is the matching pivot. No "master" post: all peers. Counter synchronized across the group.

---

## Submission flow

### Entry points

Four ways a quote can enter the system, all converging to a single moderation queue:

1. Manual submission via the website form
2. Browser extension (Firefox/Chrome companion, later)
3. Mastodon bot
4. Bluesky bot

### User submission (form)

- **No account required**: submissions possible anonymously. Account creation is optional and proposed after submission.
- **Form fields**: text and/or screenshot + URL. Platform auto-extracted from URL domain.
- **RGPD approach**:
  - Screenshots are deleted after text extraction and validation
  - Only the domain/platform is stored, not the full source URL
  - Quote text itself is anonymized (no author name, no profile reference)
  - Contributor accounts: standard rights (access, rectification, deletion, anonymization of contributions while keeping the quotes)

### Post-submission experience

The user sees:

- "Thanks, your submission is being reviewed."
- "There are currently X submissions pending moderation."
- Optional: "Create an account to track your contributions and access your contributor profile."

No automatic notifications when status changes. Contributors check their profile when they want.

### Moderation

**All moderation happens in the native WordPress admin**: the CPT's built-in screens (post list with filters by status/taxonomy/meta, edit screen, quick edit). No custom admin UI to design or maintain.

For each submission, Jaz sees:

- The raw submission: text + source domain (+ screenshot if provided, pending extraction)
- **AI suggestions**: sentiment, claim type, duplicate detection with side-by-side comparison against similar existing quotes (pre-computed on submission, stored in meta)
- Actions: validate as-is / edit / reject / merge with existing quote (increments counter instead of creating new entry)

AI tags everything on arrival: token cost is negligible. But Jaz validates both the content AND the AI's work on every submission. The AI is never authoritative.

### Rejects

- Archived with a short reason (off-topic, spam, inappropriate, already-rejected duplicate, etc.)
- Not deleted: the archive becomes a dataset to refine bots and auto-tagging patterns over time

### Duplicate handling

- When the AI detects similarity with an existing quote, it shows both side-by-side and proposes a merge
- Jaz confirms → counter on the existing quote increments
- Jaz rejects the merge → submission becomes its own new entry

### Contributor profile (private, self-only)

- Visible only to the contributor themselves: no public profiles, no pseudonyms exposed
- Blocks at launch:
  - List of their submissions with status (pending / validated / rejected / merged)
  - Personal stats (total submitted, validated, acceptance rate)
- **Ideas for later**: badges/gamification, personal positioning indicator (critic / defender / balanced)

---

## Automated collection (bots)

- **Launch scope**: Mastodon and Bluesky: both have open, free, simple APIs
- **Activation timing**: bots come online *after* Jaz has seeded a solid initial set of validated quotes: the manual base serves as the reference for what "good" content looks like
- **Validated content as living pattern source**: as the moderated database grows, it feeds back into the bots' detection logic. More validated content → better detection → richer database.
- **Progressive sophistication**: start simple (string match on "WordPress is..." variants + basic filters), then layer in semantic matching as the base grows
- **Moderation is non-negotiable**: all candidates (bots, extension, manual) land in the same queue
- **Ideas for later**: Reddit (API with rate limits), X (paid API, expensive), LinkedIn, TikTok, Instagram

## Browser extension (companion, to explore)

- Lightweight Firefox/Chrome extension
- Select text on any page → right-click → "Send to WordPress is..."
- Pre-fills the submission form with the quote and source URL
- Solves the problem of closed platforms where bots can't reach: the user becomes the bot

---

## Database structure (full spec)

### Core entity: the quote

A single custom post type `quote` holds everything. WordPress-native statuses handle the lifecycle:

- `pending`: submitted, awaiting moderation
- `publish`: validated, visible on the site
- `rejected`: rejected, archived with a reason
- `merged`: fused with another quote, retained for history and pattern learning

No separate "submission" entity. Everything is a quote in a given state.

### Meta fields on each quote

**Content and source**
- Original text (as submitted): stored in `post_content`
- Detected language: Polylang handles this
- English translation (pivot for matching): stored as Polylang EN translation
- Source domain (not the full URL): `_wpis_source_domain`
- Source platform: `_wpis_source_platform` (controlled vocabulary)
- Temporary screenshot (deleted after extraction/validation)

**Submission metadata**
- Submission date: `post_date`
- Submission source: `_wpis_submission_source` (form / extension / bot-mastodon / bot-bluesky)
- Contributor ID: `post_author` (0 if anonymous)

**Counter**
- Number of reappearances: `_wpis_counter` (synchronized across all language versions of the same quote group via Polylang hook)

**Moderation**
- Status (WordPress-native)
- Rejection reason (if rejected): `_wpis_rejection_reason`
- Parent quote ID (if merged: flat structure, depth 1 max): `_wpis_parent_id`
- Moderation date: `_wpis_moderated_at`
- AI suggestions retained: `_wpis_ai_snapshot` (serialized: sentiment, claim_type, translation)

### Taxonomies

- **Sentiment** (`sentiment`, flat): positive / negative / neutral / mixed
- **Claim type** (`claim_type`, hierarchical): closed list (performance, security, ease of use, community, ecosystem, business viability, accessibility, modernity), each with optional children (sub-topics)

### Handled differently (not taxonomies)

- **Language**: managed by Polylang at the post level
- **Source platform**: stored as a custom field with controlled values (`mastodon`, `bluesky`, `linkedin`, `youtube`, `reddit`, `blog`, etc.)

### Multilingual model (with Polylang)

- Each quote exists as one post per active language, all linked via Polylang's translation group
- Launch languages: **FR + EN**, then ES / DE / IT / NL
- All translations in a group are equal peers (no "master" post)
- Original text is the user's submission; other languages are AI-translated at submission, validated by Jaz in moderation
- **Counter** is synchronized across all posts in the translation group

### Deduplication and merge

- **Matching** happens in the original language (against existing quotes in that language) AND via the English pivot (against all English translations)
- When a match is detected, the AI proposes a merge with side-by-side comparison; Jaz confirms or rejects
- **Merge**: the merged quote's posts all shift to status `merged`, each pointing to its language equivalent in the target group (A-FR → B-FR, A-EN → B-EN, etc.)
- **Flat hierarchy**: depth 1 only. Merging a group that already has merged children re-parents those children directly to the new target group
- **Unmerge** possible but rare; the merged quotes' original texts are preserved, enriching the pattern base for future matching

### Contributor accounts

- Standard WordPress users, extended with meta fields for personal stats (total submitted, validated, acceptance rate)
- Profile private (visible only to the contributor themselves)

---

## Tooling strategy

**Cursor**: primary build tool for all code work:
- Plugin development (`wpis-plugin`, later bot plugins)
- Block theme (FSE, custom templates and patterns)
- Tests, linting, deployment scripts
- Git workflow, pushes to GitHub

**Claude web chat**: strategy, design, writing, review:
- Design iteration (already done on mockup)
- Editorial voice (about page, how-it-works, submission flow copy)
- Taxonomy design and refinement
- Content review (quote curation guidelines)
- Audit and sanity checks

**MCP**: deferred:
- Kept installed and configured, but not wired up to anything custom for now
- Will come back once the site has real content and repetitive ops (AI-assisted moderation, bulk tagging) become the bottleneck

**GitHub**: version control as always:
- `github.com/jaz-on/wpis-plugin`
- `github.com/jaz-on/wpis-theme` (or similar)
- Actions for deployment to WordPress.org (if the plugin eventually ships publicly)

---

## Deliverables from Claude web chat (this conversation)

1. **`wordpress-is-vision.md`**: this document
2. **`wordpress-is-mockup.html`**: interactive low-fi mockup, 11 screens, validated
3. **`wpis-plugin.zip`** (from **`wpis-plugin`** repo): v0.1.0 plugin scaffold, MCP server registration (will be extended in Cursor)
4. **`wordpress-is-cursor-plan.md`**: staged roadmap for the build phase in Cursor

These four files are the handoff package. Cursor takes over from there.
