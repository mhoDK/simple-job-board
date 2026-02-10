<?php
/** CF7‑→ Jobopslag */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Kør først når både ACF *og* CF7 er klar.
 * CF7’s hook wpcf7_mail_sent fyres EFTER plugins_loaded,
 * så ACF‑funktionerne er tilgængelige her.
 */
add_action( 'wpcf7_mail_sent', 'sjb_cf7_create_job_post' );

function sjb_cf7_create_job_post( $contact_form ) {

	$submission = WPCF7_Submission::get_instance();
	if ( ! $submission ) { return; }

	$data = $submission->get_posted_data();

	/* Sikkerhed: kun vores formular */
	if ( empty( $data['sjb_post_type'] ) || $data['sjb_post_type'] !== 'jobopslag' ) {
		return;
	}

	/* 1. Opret selve indlægget */
	$post_id = wp_insert_post( array(
		'post_type'   => 'jobopslag',
		'post_status' => 'pending',
		'post_title'  => sanitize_text_field( $data['job_titel']     ?? '' ),
		'post_content'=> wp_kses_post       ( $data['job_indhold']   ?? '' ),
	) );
	if ( ! $post_id ) { return; }

	/* 2. Gem ACF‑felter – BRUG felt‑NØGLER */
	if ( function_exists( 'update_field' ) ) {

		$map = array(
			// CF7‑felt‑navn  =>  ACF‑felt‑NØGLE
			'job_overskrift'  => 'field_682dac9d51f32',        // ← byt til din rigtige nøgle
			'kontakt_navn'    => 'field_682daca751f33',
			'kontakt_email'   => 'field_682dad141e10a',
			'kontakt_telefon' => 'field_682dad3a1e10b',
			'kontakt_web'     => 'field_682dad8f1e10c',
			'job_type'        => 'field_682dada21e10d',
			'job_deadline'    => 'field_682dadd01e10e',
			'job_location'    => 'field_682dae1b1e10f',
		);

		foreach ( $map as $form_key => $field_key ) {

			if ( empty( $data[ $form_key ] ) ) { continue; }

			$value = $data[ $form_key ];

			switch ( $form_key ) {
				case 'kontakt_email': $value = sanitize_email( $value ); break;
				case 'kontakt_web'  : $value = esc_url_raw   ( $value ); break;
				default             : $value = sanitize_text_field( $value );
			}

			update_field( $field_key, $value, $post_id );
		}
	}
}
