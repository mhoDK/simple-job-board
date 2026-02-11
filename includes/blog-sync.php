<?php
/** Auto-sync: Jobopslag → Blogindlæg */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Når job-status ændres, synkroniser til blog
 */
add_action( 'transition_post_status', 'sjb_sync_job_to_blog', 10, 3 );

function sjb_sync_job_to_blog( $new_status, $old_status, $post ) {

	// Kun jobopslag
	if ( $post->post_type !== 'jobopslag' ) {
		return;
	}

	// Hvis job bliver published
	if ( $new_status === 'publish' && $old_status !== 'publish' ) {
		sjb_create_blog_post_from_job( $post->ID );
	}

	// Hvis job bliver unpublished (expired eller draft)
	if ( $new_status !== 'publish' && $old_status === 'publish' ) {
		sjb_draft_linked_blog_post( $post->ID );
	}
}

/**
 * Opret blogindlæg fra jobopslag
 */
function sjb_create_blog_post_from_job( $job_id ) {

	$job = get_post( $job_id );
	if ( ! $job || $job->post_type !== 'jobopslag' ) {
		return;
	}

	// Tjek om der allerede er et linket blogindlæg
	$existing_blog_id = get_post_meta( $job_id, '_sjb_blog_post_id', true );
	if ( $existing_blog_id && get_post( $existing_blog_id ) ) {
		// Blogindlæg findes allerede, opdater det bare
		wp_update_post( array(
			'ID'          => $existing_blog_id,
			'post_status' => 'publish',
		) );
		return;
	}

	// Saml job-detaljer
	$job_title = $job->post_title;
	$job_url = get_permalink( $job_id );
	$jobs_archive_url = home_url( '/jobs/' );

	// Hent ACF-felter hvis de findes
	$job_location = get_field( 'field_682dae1b1e10f', $job_id ) ?: '';
	$job_type     = get_field( 'field_682dada21e10d', $job_id ) ?: '';
	$job_deadline = get_field( 'field_682dadd01e10e', $job_id ) ?: '';

	// Formatér deadline
	$deadline_text = '';
	if ( $job_deadline ) {
		$deadline_timestamp = strtotime( $job_deadline );
		$deadline_text = date_i18n( 'j. F Y', $deadline_timestamp );
	}

	// Byg professionelt blog-indhold
	$blog_content = sprintf(
		'<div style="background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); color: #fff; padding: 30px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">' .
		'<p style="margin: 0; font-size: 1.1em; font-weight: 500;">📢 Endnu et nyt jobopslag fra VVSarbejde.dk og jobportalen</p>' .
		'</div>' .

		'<h2 style="font-size: 1.8em; color: #333; margin: 20px 0;">%s</h2>',
		esc_html( $job_title )
	);

	// Tilføj job-detaljer hvis de findes
	if ( $job_location || $job_type || $deadline_text ) {
		$blog_content .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;">';

		if ( $job_type ) {
			$blog_content .= '<p style="margin: 8px 0;"><strong>📌 Type:</strong> ' . esc_html( $job_type ) . '</p>';
		}
		if ( $job_location ) {
			$blog_content .= '<p style="margin: 8px 0;"><strong>📍 Lokation:</strong> ' . esc_html( $job_location ) . '</p>';
		}
		if ( $deadline_text ) {
			$blog_content .= '<p style="margin: 8px 0;"><strong>📅 Ansøgningsfrist:</strong> ' . esc_html( $deadline_text ) . '</p>';
		}

		$blog_content .= '</div>';
	}

	// Call-to-action links
	$blog_content .= sprintf(
		'<div style="margin: 30px 0; padding: 25px; background: #fff; border: 2px solid #0073aa; border-radius: 8px;">' .
		'<p style="font-size: 1.1em; margin-bottom: 15px; color: #333;">Læs mere om stillingen og send din ansøgning:</p>' .
		'<p style="margin: 10px 0;">' .
		'<a href="%s" style="display: inline-block; padding: 12px 30px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; transition: background 0.3s;">Se hele jobopslaget →</a>' .
		'</p>' .
		'</div>' .

		'<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0; text-align: center;">' .
		'<p style="color: #666; margin-bottom: 15px;">Se alle vores ledige stillinger på jobportalen:</p>' .
		'<p><a href="%s" style="color: #0073aa; text-decoration: none; font-weight: 600; font-size: 1.1em;">VVSarbejde.dk Jobportal →</a></p>' .
		'</div>',
		esc_url( $job_url ),
		esc_url( $jobs_archive_url )
	);

	// Opret blogindlæg
	$blog_id = wp_insert_post( array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $job_title,
		'post_content' => $blog_content,
		'post_author'  => $job->post_author ?: get_current_user_id(),
	) );

	if ( $blog_id ) {
		// Gem link fra job til blog
		update_post_meta( $job_id, '_sjb_blog_post_id', $blog_id );
		update_post_meta( $blog_id, '_sjb_job_post_id', $job_id );

		// Hvis job har featured image, brug det også til blog
		if ( has_post_thumbnail( $job_id ) ) {
			$thumbnail_id = get_post_thumbnail_id( $job_id );
			set_post_thumbnail( $blog_id, $thumbnail_id );
		}
	}
}

/**
 * Sæt linket blogindlæg til draft når job deaktiveres
 */
function sjb_draft_linked_blog_post( $job_id ) {

	$blog_id = get_post_meta( $job_id, '_sjb_blog_post_id', true );

	if ( $blog_id && get_post( $blog_id ) ) {
		wp_update_post( array(
			'ID'          => $blog_id,
			'post_status' => 'draft',
		) );
	}
}

/**
 * Cleanup: Hvis jobopslag bliver slettet, slet også blogindlægget
 */
add_action( 'deleted_post', 'sjb_cleanup_blog_post_on_delete' );

function sjb_cleanup_blog_post_on_delete( $post_id ) {

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== 'jobopslag' ) {
		return;
	}

	$blog_id = get_post_meta( $post_id, '_sjb_blog_post_id', true );

	if ( $blog_id && get_post( $blog_id ) ) {
		wp_delete_post( $blog_id, true ); // Permanent slet
	}
}
