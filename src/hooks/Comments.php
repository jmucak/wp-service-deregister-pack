<?php

namespace jmucak\wpServiceDeregisterPack\hooks;

class Comments {
	public function deactivate(): void {
		add_action( 'widgets_init', array( $this, 'disable_rc_widget' ) );
		add_filter( 'wp_headers', array( $this, 'filter_wp_headers' ) );
		add_action( 'template_redirect', array( $this, 'filter_query' ), 9 );   // before redirect_canonical.

		// Admin bar filtering has to happen here since WP 3.6.
		add_action( 'template_redirect', array( $this, 'filter_admin_bar' ) );
		add_action( 'admin_init', array( $this, 'filter_admin_bar' ) );

		// Disable Comments REST API Endpoint
		add_filter( 'rest_endpoints', array( $this, 'filter_rest_endpoints' ) );

		add_action( 'wp_loaded', array( $this, 'init_wploaded_filters' ) );
	}

	public function disable_rc_widget(): void {
		unregister_widget( 'WP_Widget_Recent_Comments' );
		/**
		 * The widget has added a style action when it was constructed - which will
		 * still fire even if we now unregister the widget... so filter that out
		 */
		add_filter( 'show_recent_comments_widget_style', '__return_false' );
	}

	/**
	 * Remove the X-Pingback HTTP header
	 *
	 * @param $headers
	 *
	 * @return mixed
	 */
	public function filter_wp_headers( $headers ): mixed {
		unset( $headers['X-Pingback'] );

		return $headers;
	}

	/**
	 * Issue a 403 for all comment feed requests.
	 */
	public function filter_query(): void {
		if ( is_comment_feed() ) {
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Remove comment links from the admin bar.
	 */
	public function filter_admin_bar(): void {
		if ( is_admin_bar_showing() ) {
			// Remove comments links from admin bar.
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 10 );
		}
	}

	/**
	 * Remove the comments endpoint for the REST API
	 *
	 * @param $endpoints
	 *
	 * @return mixed
	 */
	public function filter_rest_endpoints( $endpoints ): mixed {
		if ( ! empty( $endpoints ) ) {
			foreach ( $endpoints as $key => $endpoint ) {
				if ( strpos( $key, 'comments' ) ) {
					unset( $endpoints[ $key ] );
				}
			}
		}

		return $endpoints;
	}

	public function init_wploaded_filters(): void {
		$posts_to_remove_comments = array( 'post', 'page', 'attachment' );
		foreach ( $posts_to_remove_comments as $to_remove ) {
			remove_post_type_support( $to_remove, 'comments' );
			remove_post_type_support( $to_remove, 'trackbacks' );
		}

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'filter_admin_menu' ), 9999 );  // do this as late as possible.
			add_action( 'admin_print_styles-index.php', array( $this, 'admin_css' ) );
			add_action( 'admin_print_styles-profile.php', array( $this, 'admin_css' ) );
			add_action( 'wp_dashboard_setup', array( $this, 'filter_dashboard' ) );
			add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
		} else {
			add_action( 'template_redirect', array( $this, 'check_comment_template' ) );

			add_filter( 'feed_links_show_comments_feed', '__return_false' );
		}
	}

	public function filter_admin_menu(): void {
		global $pagenow;

		if ( $pagenow == 'comment.php' || $pagenow == 'edit-comments.php' ) {
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );
		}

		if ( $pagenow === 'options-discussion.php' ) {
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );
		}
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	public function admin_css() {
		echo '<style>
			#dashboard_right_now .comment-count,
			#dashboard_right_now .comment-mod-count,
			#latest-comments,
			#welcome-panel .welcome-comments,
			.user-comment-shortcuts-wrap {
				display: none !important;
			}
		</style>';
	}

	public function filter_dashboard(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Replace the theme's comment template with a blank one.
	 * To prevent this, define DISABLE_COMMENTS_REMOVE_COMMENTS_TEMPLATE
	 * and set it to True
	 */
	public function check_comment_template(): void {
		if ( is_singular() ) {
			// Remove comment-reply script for themes that include it indiscriminately.
			wp_deregister_script( 'comment-reply' );
			// feed_links_extra inserts a comments RSS link.
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}
	}
}