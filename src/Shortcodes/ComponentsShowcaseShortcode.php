<?php
/**
 * [wpis_components_showcase] — single-page design-system reference.
 *
 * Renders every shared front component in all meaningful states, using the
 * same `is-style-wpis-*` and `wpis-*` classes that production screens use.
 * When the showcase matches the mockup, every screen that reuses those
 * classes is aligned too.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Shortcodes;

/**
 * Registers [wpis_components_showcase].
 */
final class ComponentsShowcaseShortcode {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'wpis_components_showcase', array( self::class, 'render' ) );
	}

	/**
	 * @param array<string, string> $atts Shortcode atts.
	 * @return string
	 */
	public static function render( $atts ): string {
		unset( $atts );
		$out  = '<div class="wpis-showcase">';
		$out .= self::section( 'tokens', __( 'Tokens', 'wpis-core' ), self::render_tokens() );
		$out .= self::section( 'eyebrow', __( 'Eyebrow + is-word', 'wpis-core' ), self::render_eyebrow() );
		$out .= self::section( 'hero', __( 'Hero (title + intro + stats)', 'wpis-core' ), self::render_hero() );
		$out .= self::section( 'feed-controls', __( 'Feed header + sort + filter selects + load more', 'wpis-core' ), self::render_feed_controls() );
		$out .= self::section( 'quote-cards', __( 'Quote feed cards (sentiment variants)', 'wpis-core' ), self::render_quote_cards() );
		$out .= self::section( 'quote-detail', __( 'Quote detail (breadcrumb, meta, opposing, stats, editorial)', 'wpis-core' ), self::render_quote_detail() );
		$out .= self::section( 'subcat', __( 'Subcategory nav + chips', 'wpis-core' ), self::render_subcat() );
		$out .= self::section( 'tax-card', __( 'Claim-type card + sentiment bar', 'wpis-core' ), self::render_tax_card() );
		$out .= self::section( 'platform-card', __( 'Platform card', 'wpis-core' ), self::render_platform_card() );
		$out .= self::section( 'forms', __( 'Form controls + upload zone + RGPD notice + buttons', 'wpis-core' ), self::render_forms() );
		$out .= self::section( 'profile', __( 'Status badges + stat cards + submission list', 'wpis-core' ), self::render_profile() );
		$out .= self::section( 'prose', __( 'Pull quote + prose em', 'wpis-core' ), self::render_prose() );
		$out .= self::section( 'how', __( 'How-it-works step', 'wpis-core' ), self::render_how() );
		$out .= self::section( 'empty', __( 'Empty / 404 state', 'wpis-core' ), self::render_empty() );
		$out .= self::section( 'confirm', __( 'Submission confirmation', 'wpis-core' ), self::render_confirm() );
		$out .= '</div>';
		return $out;
	}

	/**
	 * Wrap a component demo in a titled section.
	 *
	 * @param string $slug  Section id.
	 * @param string $title Section heading.
	 * @param string $body  HTML body.
	 * @return string
	 */
	private static function section( string $slug, string $title, string $body ): string {
		return sprintf(
			'<section class="wpis-showcase-section" id="showcase-%1$s"><h2 class="wpis-showcase-title">%2$s</h2><div class="wpis-showcase-body">%3$s</div></section>',
			esc_attr( $slug ),
			esc_html( $title ),
			$body
		);
	}

	/**
	 * @return string
	 */
	private static function render_tokens(): string {
		$swatches = array(
			'bg'          => 'Background',
			'ink'         => 'Ink',
			'muted'       => 'Muted',
			'accent'      => 'Accent',
			'accent-soft' => 'Accent soft',
			'paper'       => 'Paper',
			'positive'    => 'Positive',
			'negative'    => 'Negative',
			'mixed'       => 'Mixed',
		);
		$out = '<div class="wpis-showcase-swatches">';
		foreach ( $swatches as $slug => $label ) {
			$out .= '<div class="wpis-showcase-swatch"><span class="wpis-showcase-chip" style="background: var(--' . esc_attr( $slug ) . ');"></span><code>--' . esc_html( $slug ) . '</code><small>' . esc_html( $label ) . '</small></div>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * @return string
	 */
	private static function render_eyebrow(): string {
		return '<p class="wp-block-paragraph is-style-wpis-eyebrow">A database of claims</p>'
			. '<p class="wp-block-paragraph">WordPress <span class="is-word">is</span> a phrase we finish together.</p>';
	}

	/**
	 * @return string
	 */
	private static function render_hero(): string {
		$items = array(
			array( '2,847', 'quotes collected' ),
			array( '12', 'platforms sourced' ),
			array( '6', 'languages' ),
			array( '47', 'pending moderation' ),
		);
		$stats = '<div class="wpis-hero-stats-inner">';
		foreach ( $items as $row ) {
			$stats .= '<p class="wp-block-paragraph"><strong>' . esc_html( $row[0] ) . '</strong>' . esc_html( $row[1] ) . '</p>';
		}
		$stats .= '</div>';

		return '<div class="wp-block-group is-style-wpis-hero" style="padding: 2.5rem 1.25rem 2rem;">'
			. '<p class="wp-block-paragraph is-style-wpis-eyebrow">A database of claims</p>'
			. '<h1 class="wp-block-heading is-style-wpis-hero-title">WordPress <span class="is-word">is</span><span class="dots">…</span></h1>'
			. '<p class="wp-block-paragraph is-style-wpis-hero-intro">A large slice of the web runs on it. Yet what people say about it ranges from <em>life-changing</em> to <em>worthless</em>. This is where those statements live.</p>'
			. '<div class="wp-block-group is-style-wpis-hero-stats">' . $stats . '</div>'
			. '</div>';
	}

	/**
	 * @return string
	 */
	private static function render_feed_controls(): string {
		return '<div class="wpis-feed-controls wpis-showcase-feed">'
			. '<div class="wpis-feed-header">'
			. '<p class="wpis-feed-title">The feed <button type="button" class="wpis-filter-show-toggle" aria-expanded="true">Hide filters</button></p>'
			. '<div class="wpis-feed-sort" role="tablist" aria-label="Sort feed">'
			. '<button type="button" class="wpis-feed-sort-btn is-active" data-sort="recent" aria-pressed="true">Recent</button>'
			. '<button type="button" class="wpis-feed-sort-btn" data-sort="repeated" aria-pressed="false">Most repeated</button>'
			. '<button type="button" class="wpis-feed-sort-btn" data-sort="random" aria-pressed="false">Random</button>'
			. '</div>'
			. '</div>'
			. '<div class="wpis-filter-selects" data-open="true">'
			. self::filter_select( 'sentiment', 'All sentiments', array( 'Negative (1247)', 'Positive (892)', 'Mixed (512)', 'Neutral (196)' ) )
			. self::filter_select( 'claim', 'All claim types', array( 'Performance (423)', 'Security (287)', 'Ease of use (341)', 'Community (512)', 'Ecosystem (398)', 'Modernity (503)' ) )
			. self::filter_select( 'platform', 'All platforms', array( 'Mastodon (841)', 'LinkedIn (623)', 'Reddit (412)', 'Bluesky (287)', 'Blog (202)' ) )
			. '</div>'
			. '<button type="button" class="wpis-load-more-btn" aria-label="Load more quotes">Load more <span class="wpis-load-more-count">(+10)</span></button>'
			. '<p class="wpis-no-results-msg" role="status" aria-live="polite" hidden>No quotes match these filters yet.</p>'
			. '</div>';
	}

	/**
	 * @param string   $id      Select id.
	 * @param string   $all     Placeholder label.
	 * @param string[] $options Option labels.
	 * @return string
	 */
	private static function filter_select( string $id, string $all, array $options ): string {
		$opts = '<option value="">' . esc_html( $all ) . '</option>';
		foreach ( $options as $label ) {
			$opts .= '<option value="' . esc_attr( $label ) . '">' . esc_html( $label ) . '</option>';
		}
		return '<div class="wpis-filter-select-wrap"><select class="wpis-filter-select" id="wpis-filter-' . esc_attr( $id ) . '" name="wpis_filter_' . esc_attr( $id ) . '">' . $opts . '</select></div>';
	}

	/**
	 * @return string
	 */
	private static function render_quote_cards(): string {
		$cards = array(
			array(
				'sentiment' => 'negative',
				'text'      => 'WordPress <span class="is-word">is</span> bloated and slow on shared hosting.',
				'claim'     => 'Performance',
				'count'     => 8,
				'platforms' => array( 'Mastodon', 'LinkedIn', 'Reddit' ),
				'langs'     => array( 'EN', 'FR' ),
				'date'      => 'APR 20',
			),
			array(
				'sentiment' => 'positive',
				'text'      => 'WordPress <span class="is-word">is</span> the reason I still have a career in the web.',
				'claim'     => 'Community',
				'count'     => 3,
				'platforms' => array( 'Bluesky', 'LinkedIn' ),
				'langs'     => array( 'EN' ),
				'date'      => 'APR 19',
			),
			array(
				'sentiment' => 'mixed',
				'text'      => 'WordPress <span class="is-word">is</span> fine for small sites but don’t scale it.',
				'claim'     => 'Business viability',
				'count'     => 11,
				'platforms' => array( 'LinkedIn', 'HN', 'YouTube' ),
				'langs'     => array( 'EN' ),
				'date'      => 'APR 15',
			),
		);
		$out = '';
		foreach ( $cards as $card ) {
			$platforms = esc_html( implode( ' · ', $card['platforms'] ) );
			$langs     = '';
			foreach ( $card['langs'] as $lang ) {
				$langs .= '<span class="wpis-lang-tag">' . esc_html( $lang ) . '</span>';
			}
			$out .= '<article class="wp-block-group is-style-wpis-quote-card wpis-sent-' . esc_attr( $card['sentiment'] ) . '">'
				. '<p class="wp-block-paragraph wpis-quote-excerpt">' . wp_kses_post( $card['text'] ) . '</p>'
				. '<div class="wp-block-group is-style-wpis-quote-footer">'
				. '<span class="wpis-quote-footer-left">'
				. '<span class="is-style-wpis-claim-tag">' . esc_html( $card['claim'] ) . '</span>'
				. '<span class="is-style-wpis-count-badge">×' . (int) $card['count'] . '</span>'
				. '<span class="wpis-quote-platforms">' . $platforms . '</span>'
				. '<span class="wpis-quote-langs">' . $langs . '</span>'
				. '</span>'
				. '<span class="wpis-quote-footer-right">' . esc_html( $card['date'] ) . '</span>'
				. '</div>'
				. '</article>';
		}
		return '<div class="wp-block-group is-style-wpis-feed">' . $out . '</div>';
	}

	/**
	 * @return string
	 */
	private static function render_quote_detail(): string {
		return '<div class="wp-block-group is-style-wpis-detail">'
			. '<p class="wpis-breadcrumb"><a href="#">Feed</a><span class="sep">/</span><a href="#">Security</a><span class="sep">/</span><span>Quote</span></p>'
			. '<div class="wpis-detail-claim-meta"><span class="is-style-wpis-claim-tag">Security</span><span class="dot">·</span><span>Negative</span><span class="dot">·</span><span>×24</span></div>'
			. '<h1 class="wpis-detail-quote">WordPress <span class="is-word">is</span> not secure: too many plugins with backdoors.</h1>'
			. '<blockquote class="wpis-detail-original">« WordPress is not secure: too many plugins with backdoors. » — reported by an anonymous contributor, April 2026.</blockquote>'
			. '<aside class="wpis-against-block"><p class="wpis-against-label">The opposing take</p>'
			. '<p class="wpis-against-quote">WordPress core itself has a strong security track record; the weak spot is the plugin ecosystem.</p>'
			. '<p class="wpis-against-meta"><span>Community</span><span>·</span><span>×6</span><a class="see-more" href="#">Read more →</a></p></aside>'
			. '<section class="wpis-spread-section"><p class="wpis-section-title">Where it spread</p>'
			. '<div class="wpis-spread-grid">'
			. self::spread_item( '24', 'total sightings' )
			. self::spread_item( '8', 'platforms' )
			. self::spread_item( '2', 'languages' )
			. self::spread_item( 'APR 08', 'first seen' )
			. '</div></section>'
			. '<section class="wpis-variants-compact"><p class="wpis-section-title">Variations</p>'
			. '<div class="wpis-variant-line"><p class="v-text">"WordPress <em>is</em> insecure by design."</p><p class="v-meta">Mastodon · FR · APR 17</p></div>'
			. '<div class="wpis-variant-line"><p class="v-text">"Every plugin is a 0-day waiting to happen."</p><p class="v-meta">X · EN · APR 14</p></div>'
			. '</section>'
			. '<aside class="wpis-editorial-note"><p class="note-label">Editorial note</p><p>The plugin blame is partly <em>fair</em>, but WordPress core reviews are among the most scrutinised in open source.</p></aside>'
			. '</div>';
	}

	/**
	 * @param string $big   Headline number.
	 * @param string $label Label.
	 * @return string
	 */
	private static function spread_item( string $big, string $label ): string {
		return '<div class="wpis-spread-item"><span class="big-num">' . esc_html( $big ) . '</span><span class="small-label">' . esc_html( $label ) . '</span></div>';
	}

	/**
	 * @return string
	 */
	private static function render_subcat(): string {
		$chips = array(
			array( 'All', 287, true ),
			array( 'Plugins', 142, false ),
			array( 'Core', 68, false ),
			array( 'Hosting', 41, false ),
			array( 'Supply chain', 36, false ),
		);
		$html  = '<p class="wpis-subcat-label">Narrow down</p>';
		$html .= '<nav class="wpis-subcat-nav" aria-label="Sub-categories">';
		foreach ( $chips as $chip ) {
			$active = $chip[2] ? ' active' : '';
			$html  .= '<a class="wpis-subcat-chip' . $active . '" href="#">' . esc_html( $chip[0] ) . '<span class="wpis-subcat-chip-count">' . (int) $chip[1] . '</span></a>';
		}
		$html .= '</nav>';
		return $html;
	}

	/**
	 * @return string
	 */
	private static function render_tax_card(): string {
		return '<div class="wp-block-group is-style-wpis-tax-grid">'
			. '<div class="wp-block-group is-style-wpis-tax-card">'
			. '<div class="wp-block-group is-style-wpis-tax-card-head"><h3 class="wp-block-heading"><a href="#">Security</a></h3><p class="is-style-wpis-tax-count wp-block-paragraph">287 quotes</p></div>'
			. '<p class="is-style-wpis-tax-desc wp-block-paragraph">How safe, exposed or resilient WordPress is. Mostly about the plugin ecosystem, not core.</p>'
			. '<div class="wpis-tax-bar-columns wp-block-columns" style="display:flex;margin:0 0 8px;border:1px solid var(--line);overflow:hidden;">'
			. '<div style="flex:0 0 52%;min-width:0;"><div class="wp-block-group wpis-tax-bar-seg neg" style="min-height:6px;background:var(--negative);"></div></div>'
			. '<div style="flex:0 0 27%;min-width:0;"><div class="wp-block-group wpis-tax-bar-seg pos" style="min-height:6px;background:var(--positive);"></div></div>'
			. '<div style="flex:0 0 21%;min-width:0;"><div class="wp-block-group wpis-tax-bar-seg mix" style="min-height:6px;background:var(--mixed);"></div></div>'
			. '</div>'
			. '<div class="wpis-tax-breakdown is-layout-flex wp-block-group" style="display:flex;flex-wrap:wrap;gap:12px;">'
			. '<p class="wp-block-paragraph" style="margin:0;display:flex;align-items:center;gap:4px;"><span class="wpis-dot neg" aria-hidden="true"></span>149 critical</p>'
			. '<p class="wp-block-paragraph" style="margin:0;display:flex;align-items:center;gap:4px;"><span class="wpis-dot pos" aria-hidden="true"></span>77 supportive</p>'
			. '<p class="wp-block-paragraph" style="margin:0;display:flex;align-items:center;gap:4px;"><span class="wpis-dot mix" aria-hidden="true"></span>61 mixed</p>'
			. '</div></div></div>';
	}

	/**
	 * @return string
	 */
	private static function render_platform_card(): string {
		$items = array(
			array( 'Mastodon', 841 ),
			array( 'LinkedIn', 623 ),
			array( 'Reddit', 412 ),
			array( 'Bluesky', 287 ),
		);
		$out   = '<div class="wp-block-group is-style-wpis-platform-grid">';
		foreach ( $items as $row ) {
			$out .= '<div class="wp-block-group is-style-wpis-platform-card"><h4 class="wp-block-heading"><a href="#">' . esc_html( $row[0] ) . '</a></h4><p class="is-style-wpis-platform-count wp-block-paragraph">' . (int) $row[1] . '</p></div>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * @return string
	 */
	private static function render_forms(): string {
		return '<div class="wpis-showcase-form">'
			. '<div class="form-group"><label for="wpis-demo-quote">The quote <span class="required">*</span></label><textarea id="wpis-demo-quote" rows="3" placeholder="WordPress is…"></textarea><p class="hint">Paste the original phrasing. You can translate in the next step.</p></div>'
			. '<div class="form-group"><label for="wpis-demo-url">Source URL</label><input type="url" id="wpis-demo-url" placeholder="https://" /></div>'
			. '<div class="upload-zone" tabindex="0">Drop a screenshot or click to upload — optional, deleted after moderation.</div>'
			. '<div class="rgpd-notice"><strong>Data notice</strong>Submissions are moderated; screenshots are deleted after review. No personal data is shared.</div>'
			. '<button type="button" class="btn-primary">Submit quote</button> <a class="btn-secondary" href="#">Cancel</a>'
			. '<span class="queue-indicator">Typically reviewed within 48 hours.</span>'
			. '</div>';
	}

	/**
	 * @return string
	 */
	private static function render_profile(): string {
		return '<div class="wp-block-group is-style-wpis-profile"><div class="wpis-profile">'
			. '<header class="profile-header"><h1>Your submissions</h1><p>Signed in as jaz · member since 2026</p></header>'
			. '<div class="stats-grid">'
			. self::stat_card( '12', 'SUBMITTED' )
			. self::stat_card( '7', 'PUBLISHED' )
			. self::stat_card( '3', 'PENDING' )
			. self::stat_card( '2', 'REJECTED' )
			. '</div>'
			. '<section class="submission-list"><h2>Recent</h2>'
			. self::sub_item( 'WordPress is the only CMS my non-technical clients can maintain.', 'validated', 'APR 13' )
			. self::sub_item( 'WordPress is still shipping accessibility bugs from years ago.', 'pending', 'APR 11' )
			. self::sub_item( 'WordPress is fine until you need something specific.', 'merged', 'APR 08' )
			. self::sub_item( 'Claim without source.', 'rejected', 'APR 04' )
			. '</section>'
			. '</div></div>';
	}

	/**
	 * @param string $value Number.
	 * @param string $label Label.
	 * @return string
	 */
	private static function stat_card( string $value, string $label ): string {
		return '<div class="stat-card"><div class="label">' . esc_html( $label ) . '</div><div class="value">' . esc_html( $value ) . '</div></div>';
	}

	/**
	 * @param string $text   Quote body.
	 * @param string $status One of pending|validated|rejected|merged.
	 * @param string $date   Date label.
	 * @return string
	 */
	private static function sub_item( string $text, string $status, string $date ): string {
		$label = array(
			'pending'   => 'Pending',
			'validated' => 'Validated',
			'rejected'  => 'Rejected',
			'merged'    => 'Merged',
		)[ $status ] ?? ucfirst( $status );
		return '<div class="sub-item"><p class="sub-text">' . esc_html( $text ) . '</p><div class="sub-meta-line"><span class="status-badge status-' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span><span class="sub-date">' . esc_html( $date ) . '</span></div></div>';
	}

	/**
	 * @return string
	 */
	private static function render_prose(): string {
		return '<div class="wp-block-group is-style-wpis-prose">'
			. '<p class="wp-block-paragraph">People repeat that WordPress <em>is</em> old, or WordPress <em>is</em> the web — and both are true at once. This site collects those sentences.</p>'
			. '<p class="wp-block-paragraph is-style-wpis-pull-quote">A large slice of the web runs on it. What people say about it ranges from <em>life-changing</em> to <em>worthless</em>.</p>'
			. '</div>';
	}

	/**
	 * @return string
	 */
	private static function render_how(): string {
		return '<div class="wp-block-group is-style-wpis-how">'
			. '<div class="is-style-wpis-how-step-list">'
			. self::how_step( '01', 'Collect', 'We capture what people <em>actually say</em> about WordPress across the open web and social platforms.', 'Extension, form, bots.' )
			. self::how_step( '02', 'Moderate', 'A small editorial loop deduplicates, classifies and pairs opposing views when they exist.', 'Humans + AI assist, never AI alone.' )
			. self::how_step( '03', 'Publish', 'Claims go live with source, sentiment, counter, and opposing take.', 'CC BY-NC, links preserved.' )
			. '</div></div>';
	}

	/**
	 * @param string $num   Step number.
	 * @param string $title Step title.
	 * @param string $body  Step body HTML.
	 * @param string $note  Footer note.
	 * @return string
	 */
	private static function how_step( string $num, string $title, string $body, string $note ): string {
		return '<div class="is-style-wpis-how-step"><div class="num">' . esc_html( $num ) . '</div><div class="body"><h3>' . esc_html( $title ) . '</h3><p>' . wp_kses_post( $body ) . '</p><p class="note">' . esc_html( $note ) . '</p></div></div>';
	}

	/**
	 * @return string
	 */
	private static function render_empty(): string {
		return '<div class="wp-block-group is-style-wpis-empty-state">'
			. '<p class="wpis-empty-symbol" aria-hidden="true">…</p>'
			. '<h2>Nothing here yet</h2>'
			. '<p>Try another claim type, or <a href="/submit/">submit a quote you saw somewhere</a>.</p>'
			. '<p><a class="btn-secondary" href="/">Back to the feed</a></p>'
			. '</div>';
	}

	/**
	 * @return string
	 */
	private static function render_confirm(): string {
		return '<div class="wp-block-group is-style-wpis-confirm">'
			. '<p class="wpis-confirm-mark" aria-hidden="true">…</p>'
			. '<h2>Thanks — your quote is in the queue</h2>'
			. '<p class="sub">A moderator will review it shortly. You don’t need an account to contribute, but signing in lets you track status.</p>'
			. '<div class="wpis-confirm-queue">Typically reviewed within <strong>48h</strong>.</div>'
			. '<p><a class="btn-secondary" href="/">Back to the feed</a></p>'
			. '</div>';
	}
}
