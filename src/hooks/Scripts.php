<?php

namespace jmucak\wpServiceDeregisterPack\hooks;

class Scripts {
	public function deactivate(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'remove_wp_block_library_css' ), 100 );
	}

	public function remove_wp_block_library_css(): void {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		/*  DISABLE GUTENBERG STYLE IN HEADER| WordPress 5.9 */
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}