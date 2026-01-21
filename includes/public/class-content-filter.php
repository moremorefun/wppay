<?php
/**
 * Content Filter class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Frontend;

/**
 * Handles automatic content filtering to add donation button.
 */
class ContentFilter {

	/**
	 * Register the content filter.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'the_content', [ $this, 'append_donation_button' ], 100 );
	}

	/**
	 * Append donation button to post content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append_donation_button( string $content ): string {
		// Only add to single posts.
		if ( ! is_singular( 'post' ) || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Check if auto-add is enabled.
		$settings = get_option( 'paythefly_settings', [] );
		if ( empty( $settings['inline_button_auto'] ) ) {
			return $content;
		}

		// Check if project ID is configured.
		if ( empty( $settings['project_id'] ) ) {
			return $content;
		}

		// Enqueue required scripts.
		wp_enqueue_script( 'paythefly-shortcode' );
		wp_enqueue_style( 'paythefly-shortcode' );

		// Add donation button container (React will render the actual button inside).
		$button_html = '<div class="paythefly-inline-button-wrapper"></div>';

		return $content . $button_html;
	}
}
