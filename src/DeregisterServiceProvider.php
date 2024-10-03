<?php

namespace jmucak\wpServiceDeregisterPack;

use jmucak\wpServiceDeregisterPack\hooks\Comments;
use jmucak\wpServiceDeregisterPack\hooks\Posts;
use jmucak\wpServiceDeregisterPack\hooks\Scripts;
use jmucak\wpServiceDeregisterPack\hooks\WPEmbeds;
use jmucak\wpServiceDeregisterPack\hooks\WPEmoji;

class DeregisterServiceProvider {
	public function deactivate_comments(): void {
		( new Comments() )->deactivate();
	}

	public function deactivate_wp_embeds(): void {
		( new WPEmbeds() )->deactivate();
	}

	public function deactivate_wp_emoji(): void {
		( new WPEmoji() )->deactivate();
	}

	public function deactivate_default_posts(): void {
		( new Posts() )->deactivate();
	}

	public function deactivate_wp_scripts(): void {
		( new Scripts() )->deactivate();
	}

	public function deactivate_short_links(): void {
		// Remove shortlink from head
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );

		// Disable shortlink header
		remove_action( 'template_redirect', 'wp_shortlink_header', 10, 0 );
	}
}