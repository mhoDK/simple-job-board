<?php
/**
 * Template til /jobs/ og /jobs/alle/ arkiv
 * Viser jobopslag som en elegant liste
 */

get_header();
?>

<div class="sjb-job-archive" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">

	<header class="archive-header" style="margin-bottom: 40px;">
		<h1 style="font-size: 2.5em; margin-bottom: 10px; color: #333;">
			Ledige stillinger
		</h1>
		<p style="font-size: 1.1em; color: #666;">
			Se vores aktuelle ledige stillinger.
		</p>

		<!-- Navigation mellem visninger -->
		<div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
			<a href="<?php echo home_url('/jobs/'); ?>"
			   style="display: inline-block; padding: 8px 15px; background: <?php echo get_query_var('sjb_scope') !== 'alle' ? '#0073aa' : '#f0f0f0'; ?>; color: <?php echo get_query_var('sjb_scope') !== 'alle' ? '#fff' : '#333'; ?>; text-decoration: none; border-radius: 4px;">
				Aktive stillinger
			</a>
			<a href="<?php echo home_url('/jobs/alle/'); ?>"
			   style="display: inline-block; padding: 8px 15px; background: <?php echo get_query_var('sjb_scope') === 'alle' ? '#0073aa' : '#f0f0f0'; ?>; color: <?php echo get_query_var('sjb_scope') === 'alle' ? '#fff' : '#333'; ?>; text-decoration: none; border-radius: 4px;">
				Alle opslag
			</a>
			<a href="<?php echo home_url('/indsend-jobopslag/'); ?>"
			   style="display: inline-block; padding: 8px 15px; background: #28a745; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600;">
				+ Indsend jobopslag
			</a>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="sjb-job-list" style="background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">

			<?php
			$count = 0;
			while ( have_posts() ) : the_post();
				$count++;

				// Hent ACF-felter
				$deadline = get_field('job_deadline') ?: '';
				$location = get_field('job_location') ?: '';
				$job_type = get_field('job_type') ?: '';
				$is_expired = get_post_status() === 'expired';

				// Formatér deadline
				$deadline_formatted = '';
				if ( $deadline ) {
					$deadline_timestamp = strtotime( $deadline );
					$deadline_formatted = date_i18n( 'j. F Y', $deadline_timestamp );
				}
			?>

			<div class="sjb-job-item" style="border-bottom: 1px solid #e0e0e0; padding: 20px 30px; transition: background 0.2s; <?php if ($is_expired) echo 'opacity: 0.6;'; ?>">
				<a href="<?php the_permalink(); ?>" style="text-decoration: none; color: inherit; display: block;">
					<div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">

						<!-- Venstre side: Titel + metadata -->
						<div style="flex: 1; min-width: 250px;">
							<h2 style="margin: 0 0 8px 0; font-size: 1.4em; color: #0073aa; transition: color 0.2s;">
								<?php the_title(); ?>
								<?php if ($is_expired) : ?>
									<span style="font-size: 0.7em; color: #999; font-weight: normal;">(Udløbet)</span>
								<?php endif; ?>
							</h2>

							<div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.9em; color: #666;">
								<?php if ($job_type) : ?>
									<span>📌 <?php echo esc_html($job_type); ?></span>
								<?php endif; ?>

								<?php if ($location) : ?>
									<span>📍 <?php echo esc_html($location); ?></span>
								<?php endif; ?>
							</div>
						</div>

						<!-- Højre side: Deadline -->
						<div style="text-align: right; min-width: 180px;">
							<?php if ($deadline_formatted) : ?>
								<div style="font-size: 0.85em; color: #999; margin-bottom: 4px;">
									Ansøgningsfrist
								</div>
								<div style="font-size: 1em; font-weight: 600; color: <?php echo $is_expired ? '#999' : '#333'; ?>;">
									<?php echo $deadline_formatted; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</a>
			</div>

			<?php endwhile; ?>

		</div>

		<div style="margin-top: 20px; color: #666; font-size: 0.9em;">
			Viser <?php echo $count; ?> jobopslag
		</div>

	<?php else : ?>

		<div style="padding: 60px 20px; text-align: center; background: #f9f9f9; border-radius: 8px;">
			<p style="font-size: 1.2em; color: #666;">Ingen jobopslag fundet.</p>
		</div>

	<?php endif; ?>

</div>

<style>
/* Hover-effekt på job-items */
.sjb-job-item:hover {
	background: #f8f9fa !important;
}
.sjb-job-item:hover h2 {
	color: #005177 !important;
}
/* Fjern border på sidste item */
.sjb-job-item:last-child {
	border-bottom: none !important;
}
/* Responsivt design */
@media (max-width: 768px) {
	.sjb-job-archive {
		padding: 20px 15px !important;
	}
	.sjb-job-item {
		padding: 15px 20px !important;
	}
}
</style>

<?php get_footer(); ?>
