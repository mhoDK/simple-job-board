<?php
/** CPT: jobopslag  –  slug = /jobs/ */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function sjb_register_job_post_type() {

	$labels = array(
		'name'               => _x( 'Jobopslag', 'post type general name', 'sjb' ),
		'singular_name'      => _x( 'Jobopslag', 'post type singular', 'sjb' ),
		'menu_name'          => _x( 'Jobopslag', 'admin menu', 'sjb' ),
		'name_admin_bar'     => _x( 'Jobopslag', 'add new on admin bar', 'sjb' ),
		'add_new'            => _x( 'Tilføj nyt', 'job', 'sjb' ),
		'add_new_item'       => __( 'Tilføj nyt jobopslag', 'sjb' ),
		'new_item'           => __( 'Nyt jobopslag', 'sjb' ),
		'edit_item'          => __( 'Redigér jobopslag', 'sjb' ),
		'view_item'          => __( 'Se jobopslag', 'sjb' ),
		'all_items'          => __( 'Alle jobopslag', 'sjb' ),
		'search_items'       => __( 'Søg jobopslag', 'sjb' ),
		'not_found'          => __( 'Ingen jobopslag fundet.', 'sjb' ),
		'not_found_in_trash' => __( 'Ingen jobopslag i papirkurven.', 'sjb' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'jobs' ),   //  /jobs/…
		'has_archive'        => true,                        //  arkiv på /jobs/
		'hierarchical'       => false,
		'show_in_rest'       => true,
		'menu_position'      => 20,
		'supports'           => array( 'title', 'editor', 'thumbnail' ),
	);

	register_post_type( 'jobopslag', $args );
}
add_action( 'init', 'sjb_register_job_post_type' );
