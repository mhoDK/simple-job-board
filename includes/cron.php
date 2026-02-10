<?php
/** Daglig cron: markerer udløbne job */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function sjb_schedule_job_expiry_cron() {
	if ( ! wp_next_scheduled( 'sjb_job_expiry_event' ) ) {
		wp_schedule_event( time(), 'daily', 'sjb_job_expiry_event' );
	}
}
add_action( 'init', 'sjb_schedule_job_expiry_cron' );

function sjb_clear_job_expiry_cron() {
	$timestamp = wp_next_scheduled( 'sjb_job_expiry_event' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'sjb_job_expiry_event' );
	}
}

add_action( 'sjb_job_expiry_event', 'sjb_mark_expired_jobs' );

function sjb_mark_expired_jobs() {

	$today = date( 'Y-m-d' );

	$expired_jobs = get_posts( array(
		'post_type'      => 'jobopslag',
		'post_status'    => array( 'publish' ),
		'meta_key'       => 'job_deadline',
		'meta_value'     => $today,
		'meta_compare'   => '<',
		'meta_type'      => 'DATE',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $expired_jobs as $post_id ) {
		wp_update_post( array(
			'ID'          => $post_id,
			'post_status' => 'expired',
		) );
	}
}
