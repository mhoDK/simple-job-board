<?php
/** Template loader for jobopslag arkiv */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Brug custom template for jobopslag-arkiv
 */
add_filter( 'template_include', 'sjb_load_custom_archive_template', 99 );

function sjb_load_custom_archive_template( $template ) {

	// Kun for jobopslag post type
	if ( is_post_type_archive( 'jobopslag' ) || ( is_archive() && get_post_type() === 'jobopslag' ) ) {

		// Find vores custom template
		$plugin_template = SJB_PATH . 'templates/archive-jobopslag.php';

		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}

	return $template;
}
