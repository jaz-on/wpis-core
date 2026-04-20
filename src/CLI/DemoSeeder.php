<?php
/**
 * Demo quote seeding for staging and design QA.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\CLI;

use WPIS\Core\Activation;
use WPIS\Core\Constants;
use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * Creates and removes demo quotes (meta flag _wpis_demo_seed).
 */
final class DemoSeeder {

	private const DEMO_META = '_wpis_demo_seed';

	/**
	 * Delete all demo quotes.
	 *
	 * @return int Number of posts deleted.
	 */
	public static function erase(): int {
		$ids = get_posts(
			array(
				'post_type'      => QuotePostType::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::DEMO_META,
						'value' => '1',
					),
				),
			)
		);
		$n   = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				++$n;
			}
		}
		return $n;
	}

	/**
	 * Insert demo quotes with taxonomies, meta, and opposing pairs.
	 *
	 * @param int $count Target number of posts (rounded to dataset size).
	 * @return int Number of posts created.
	 */
	public static function seed( int $count = 24 ): int {
		Activation::seed_default_terms();

		$dataset = self::quote_dataset();
		if ( $count > 0 && $count < count( $dataset ) ) {
			$dataset = array_slice( $dataset, 0, $count );
		}

		$ids = array();
		foreach ( $dataset as $row ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => QuotePostType::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => self::title_from_content( (string) $row['content'] ),
					'post_content' => $row['content'],
					'post_author'  => 0,
				),
				true
			);
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}
			update_post_meta( (int) $post_id, self::DEMO_META, 1 );
			update_post_meta( (int) $post_id, '_wpis_counter', (int) $row['counter'] );
			update_post_meta( (int) $post_id, '_wpis_submission_source', 'form' );
			$platform = (string) $row['platform'];
			if ( ! in_array( $platform, Constants::SOURCE_PLATFORMS, true ) ) {
				$platform = 'blog';
			}
			update_post_meta( (int) $post_id, '_wpis_source_platform', $platform );
			update_post_meta( (int) $post_id, '_wpis_source_domain', sanitize_text_field( (string) $row['domain'] ) );

			wp_set_object_terms( (int) $post_id, (string) $row['sentiment'], SentimentTaxonomy::TAXONOMY, false );
			wp_set_object_terms( (int) $post_id, (string) $row['claim'], ClaimTypeTaxonomy::TAXONOMY, false );

			if ( ! empty( $row['editorial'] ) ) {
				update_post_meta( (int) $post_id, '_wpis_editorial_note', sanitize_textarea_field( (string) $row['editorial'] ) );
			}

			$ids[] = (int) $post_id;
		}

		$pairs = array(
			array( 0, 1 ),
			array( 2, 3 ),
			array( 4, 5 ),
			array( 6, 7 ),
			array( 8, 9 ),
			array( 10, 11 ),
		);
		foreach ( $pairs as $pair ) {
			$a = $ids[ $pair[0] ] ?? 0;
			$b = $ids[ $pair[1] ] ?? 0;
			if ( $a && $b ) {
				update_post_meta( $a, '_wpis_opposing_quote_id', $b );
				update_post_meta( $b, '_wpis_opposing_quote_id', $a );
			}
		}

		return count( $ids );
	}

	/**
	 * @param string $text Content.
	 * @return string
	 */
	private static function title_from_content( string $text ): string {
		$t = wp_html_excerpt( $text, 72, '…' );
		return $t ? $t : 'Quote';
	}

	/**
	 * Curated demo lines (EN) covering sentiments and claim types.
	 *
	 * @return array<int, array{content: string, sentiment: string, claim: string, counter: int, platform: string, domain: string, editorial?: string}>
	 */
	private static function quote_dataset(): array {
		return array(
			array(
				'content'   => 'WordPress is a security nightmare because anyone can publish a plugin.',
				'sentiment' => 'negative',
				'claim'     => 'security',
				'counter'   => 24,
				'platform'  => 'reddit',
				'domain'    => 'reddit.com',
			),
			array(
				'content'   => 'WordPress core is patched quickly; most incidents come from poorly maintained plugins, not the core team.',
				'sentiment' => 'positive',
				'claim'     => 'security',
				'counter'   => 18,
				'platform'  => 'blog',
				'domain'    => 'example.org',
				'editorial' => 'This tension shows up in almost every CMS: extensibility trades off against attack surface. The useful distinction is who maintains the code you run.',
			),
			array(
				'content'   => 'WordPress is slow if you stack twenty page builders and never turn on caching.',
				'sentiment' => 'negative',
				'claim'     => 'performance',
				'counter'   => 31,
				'platform'  => 'mastodon',
				'domain'    => 'mastodon.social',
			),
			array(
				'content'   => 'WordPress can be blazing fast with a sane theme, object cache, and a little discipline.',
				'sentiment' => 'positive',
				'claim'     => 'performance',
				'counter'   => 22,
				'platform'  => 'bluesky',
				'domain'    => 'bsky.app',
			),
			array(
				'content'   => 'WordPress is easy until you need something off the happy path; then you are debugging hooks at midnight.',
				'sentiment' => 'mixed',
				'claim'     => 'ease-of-use',
				'counter'   => 15,
				'platform'  => 'x',
				'domain'    => 'x.com',
			),
			array(
				'content'   => 'WordPress is the fastest way for a small team to ship a content site that non-developers can own.',
				'sentiment' => 'positive',
				'claim'     => 'ease-of-use',
				'counter'   => 27,
				'platform'  => 'linkedin',
				'domain'    => 'linkedin.com',
			),
			array(
				'content'   => 'WordPress is a dying ecosystem; everything interesting moved to React stacks.',
				'sentiment' => 'negative',
				'claim'     => 'modernity',
				'counter'   => 12,
				'platform'  => 'hn',
				'domain'    => 'news.ycombinator.com',
			),
			array(
				'content'   => 'WordPress ships block editing, interoperability APIs, and still powers most of the open web. That is not “dying.”',
				'sentiment' => 'positive',
				'claim'     => 'modernity',
				'counter'   => 40,
				'platform'  => 'youtube',
				'domain'    => 'youtube.com',
			),
			array(
				'content'   => 'WordPress community events feel welcoming in a way most tech scenes are not.',
				'sentiment' => 'positive',
				'claim'     => 'community',
				'counter'   => 19,
				'platform'  => 'mastodon',
				'domain'    => 'mastodon.online',
			),
			array(
				'content'   => 'WordPress community drama and vendor land grabs are exhausting.',
				'sentiment' => 'negative',
				'claim'     => 'community',
				'counter'   => 9,
				'platform'  => 'reddit',
				'domain'    => 'reddit.com',
			),
			array(
				'content'   => 'WordPress plugins let you integrate almost anything without forking the CMS.',
				'sentiment' => 'positive',
				'claim'     => 'ecosystem',
				'counter'   => 33,
				'platform'  => 'blog',
				'domain'    => 'wordpress.org',
			),
			array(
				'content'   => 'WordPress plugin quality is a lottery; the repo is too noisy to navigate safely.',
				'sentiment' => 'negative',
				'claim'     => 'ecosystem',
				'counter'   => 21,
				'platform'  => 'bluesky',
				'domain'    => 'bsky.app',
			),
			array(
				'content'   => 'WordPress is viable for agencies if you productize your stack and avoid one-off spaghetti.',
				'sentiment' => 'positive',
				'claim'     => 'business-viability',
				'counter'   => 14,
				'platform'  => 'linkedin',
				'domain'    => 'linkedin.com',
			),
			array(
				'content'   => 'WordPress projects underestimate maintenance; “cheap WordPress” becomes expensive fast.',
				'sentiment' => 'negative',
				'claim'     => 'business-viability',
				'counter'   => 16,
				'platform'  => 'reddit',
				'domain'    => 'reddit.com',
			),
			array(
				'content'   => 'WordPress accessibility improved, but themes still ship keyboard traps and low-contrast defaults.',
				'sentiment' => 'mixed',
				'claim'     => 'accessibility',
				'counter'   => 11,
				'platform'  => 'blog',
				'domain'    => 'webaim.org',
			),
			array(
				'content'   => 'WordPress is the pragmatic choice when compliance and editor training matter more than developer fashion.',
				'sentiment' => 'neutral',
				'claim'     => 'accessibility',
				'counter'   => 8,
				'platform'  => 'other',
				'domain'    => 'example.net',
			),
			array(
				'content'   => 'WordPress is bloated compared to a static site generator with twelve files.',
				'sentiment' => 'negative',
				'claim'     => 'performance',
				'counter'   => 17,
				'platform'  => 'hn',
				'domain'    => 'news.ycombinator.com',
			),
			array(
				'content'   => 'WordPress is boring technology in the best sense: boring keeps rent paid.',
				'sentiment' => 'positive',
				'claim'     => 'business-viability',
				'counter'   => 13,
				'platform'  => 'mastodon',
				'domain'    => 'mastodon.social',
			),
			array(
				'content'   => 'WordPress is the reason I could publish without learning Git first.',
				'sentiment' => 'positive',
				'claim'     => 'ease-of-use',
				'counter'   => 26,
				'platform'  => 'blog',
				'domain'    => 'personal.blog',
			),
			array(
				'content'   => 'WordPress is frustrating when hosts ship ancient PHP and blame the software.',
				'sentiment' => 'mixed',
				'claim'     => 'modernity',
				'counter'   => 10,
				'platform'  => 'x',
				'domain'    => 'x.com',
			),
			array(
				'content'   => 'WordPress is not one product; it is core, hosting, theme, plugins, and people.',
				'sentiment' => 'neutral',
				'claim'     => 'community',
				'counter'   => 35,
				'platform'  => 'blog',
				'domain'    => 'make.wordpress.org',
			),
			array(
				'content'   => 'WordPress is the CMS you recommend to relatives and regret when they install thirty plugins.',
				'sentiment' => 'mixed',
				'claim'     => 'ecosystem',
				'counter'   => 20,
				'platform'  => 'reddit',
				'domain'    => 'reddit.com',
			),
			array(
				'content'   => 'WordPress is the open web winning against pure SaaS capture, one install at a time.',
				'sentiment' => 'positive',
				'claim'     => 'community',
				'counter'   => 29,
				'platform'  => 'bluesky',
				'domain'    => 'bsky.app',
			),
			array(
				'content'   => 'WordPress is fine; the problem is consultants who sell magic and deliver technical debt.',
				'sentiment' => 'negative',
				'claim'     => 'business-viability',
				'counter'   => 7,
				'platform'  => 'linkedin',
				'domain'    => 'linkedin.com',
			),
		);
	}
}
