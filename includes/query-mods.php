<?php
/** /jobs/alle/ endpoint + post_status‑filtrering */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Rewrite‑regel */
add_action( 'init', function () {
	add_rewrite_rule( '^jobs/alle/?$', 'index.php?post_type=jobopslag&sjb_scope=alle', 'top' );
} );

/* Registrér query‑var */
add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'sjb_scope';
	return $vars;
} );

/* Tilpas WP_Query */
add_action( 'pre_get_posts', function ( $q ) {

    if ( is_admin() || ! $q->is_main_query() ) { return; }

    // LAD preview køre uændret
    if ( $q->is_preview() ) { return; }

    if ( $q->get( 'post_type' ) === 'jobopslag' ) {

        // Standard­visning: kun aktive
        $q->set( 'post_status', 'publish' );

        // /jobs/alle/ viser også udløbne
        if ( 'alle' === $q->get( 'sjb_scope' ) ) {
            $q->set( 'post_status', array( 'publish', 'expired' ) );
        }
    }
} );
