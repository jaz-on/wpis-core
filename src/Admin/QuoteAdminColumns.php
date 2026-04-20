<?php
/**
 * Admin list table columns for quotes.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Admin;

use WPIS\Core\Constants;
use WPIS\Core\PostTypes\QuotePostType;
/**
 * Custom columns, sorting, and filters.
 */
final class QuoteAdminColumns {

	/**
	 * Hook admin list enhancements.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'manage_' . QuotePostType::POST_TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . QuotePostType::POST_TYPE . '_posts_custom_column', array( self::class, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . QuotePostType::POST_TYPE . '_sortable_columns', array( self::class, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( self::class, 'orderby_meta' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'filter_submission_source' ) );
		add_action( 'pre_get_posts', array( self::class, 'filter_submission_source_query' ) );
	}

	/**
	 * Add custom columns after title.
	 *
	 * @param string[] $columns Columns.
	 * @return string[]
	 */
	public static function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				// Sentiment & claim type use core taxonomy columns (show_admin_column on each taxonomy).
				$new['wpis_counter']           = __( 'Counter', 'wpis-plugin' );
				$new['wpis_source_platform']   = __( 'Platform', 'wpis-plugin' );
				$new['wpis_submission_source'] = __( 'Submission', 'wpis-plugin' );
			}
		}
		return $new;
	}

	/**
	 * Render cell content.
	 *
	 * @param string $column Column id.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'wpis_counter':
				$v = get_post_meta( $post_id, '_wpis_counter', true );
				echo esc_html( (string) ( '' !== $v ? $v : '1' ) );
				break;
			case 'wpis_source_platform':
				$v = get_post_meta( $post_id, '_wpis_source_platform', true );
				echo esc_html( $v ? (string) $v : '—' );
				break;
			case 'wpis_submission_source':
				$v = get_post_meta( $post_id, '_wpis_submission_source', true );
				echo esc_html( $v ? (string) $v : '—' );
				break;
		}
	}

	/**
	 * Register sortable columns.
	 *
	 * @param string[] $columns Sortable map.
	 * @return string[]
	 */
	public static function sortable_columns( array $columns ): array {
		$columns['wpis_counter'] = '_wpis_counter';
		return $columns;
	}

	/**
	 * Sort by counter meta.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public static function orderby_meta( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-' . QuotePostType::POST_TYPE !== $screen->id ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( '_wpis_counter' === $orderby ) {
			$query->set( 'meta_key', '_wpis_counter' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Dropdown to filter by submission source.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	public static function filter_submission_source( string $post_type ): void {
		if ( QuotePostType::POST_TYPE !== $post_type ) {
			return;
		}

		$current = isset( $_GET['wpis_submission_source'] ) ? sanitize_key( wp_unslash( $_GET['wpis_submission_source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<select name="wpis_submission_source" id="wpis_submission_source">
			<option value=""><?php esc_html_e( 'All submission sources', 'wpis-plugin' ); ?></option>
			<?php foreach ( Constants::SUBMISSION_SOURCES as $src ) : ?>
				<option value="<?php echo esc_attr( $src ); ?>" <?php selected( $current, $src ); ?>>
					<?php echo esc_html( $src ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Apply submission source meta query on the posts list screen.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public static function filter_submission_source_query( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || QuotePostType::POST_TYPE !== $screen->post_type || 'edit' !== $screen->base ) {
			return;
		}

		if ( empty( $_GET['wpis_submission_source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$src = sanitize_key( wp_unslash( $_GET['wpis_submission_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $src, Constants::SUBMISSION_SOURCES, true ) ) {
			return;
		}

		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'   => '_wpis_submission_source',
			'value' => $src,
		);
		$query->set( 'meta_query', $meta_query );
	}
}
