<?php
/**
 * Shared curated quote rows for sample quote seeding.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\CLI;

/**
 * Curated sample lines (EN) covering sentiments and claim types.
 */
final class QuoteSampleDataset {

	/**
	 * @return list<array{content: string, sentiment: string, claim: string, counter: int, platform: string, domain: string, editorial?: string}>
	 */
	public static function get_rows(): array {
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
				'editorial' => 'Both statements describe the same software. Performance is overwhelmingly a function of <em>operator choices</em>: hosting tier, plugin diet, caching layer. Benchmarks that strip those context signals mislead more than they inform.',
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
				'editorial' => 'The "easy" and "hard" takes both hold because WordPress is a <em>layered</em> tool. The first mile (install, publish, edit) is easier than almost any alternative; the tenth mile (custom fields, multisite, headless) is genuinely harder.',
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
				'editorial' => '"Dying ecosystem" is almost always a <em>relative</em> claim — measured against whatever stack the speaker works on today. Absolute usage numbers tell a different story, and both can be true at once.',
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
				'editorial' => 'Large open-source communities cycle through governance crises; the WordPress one is <em>more visible</em> because the project is consumer-facing and commercially valuable. That visibility is both its strength and its strain.',
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
				'editorial' => 'The same <em>low barrier to publish</em> that makes the plugin repo feel like a lottery is also why WordPress covers niches no curated marketplace would. The tradeoff is inherent, not a design bug.',
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
