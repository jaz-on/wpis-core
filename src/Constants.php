<?php
/**
 * Shared string constants for controlled vocabularies.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core;

/**
 * Class Constants
 */
final class Constants {

	public const SOURCE_PLATFORMS = array(
		'mastodon',
		'bluesky',
		'linkedin',
		'youtube',
		'reddit',
		'blog',
		'x',
		'hn',
		'other',
	);

	public const SUBMISSION_SOURCES = array(
		'form',
		'extension',
		'bot-mastodon',
		'bot-bluesky',
	);

	public const REJECTION_REASONS = array(
		'off-topic',
		'spam',
		'duplicate',
		'low-value',
		'other',
	);
}
