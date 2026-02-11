<?php
/*
Plugin Name: Simple Job Board
Description: Jobopslag via CF7, cron‑udløb og arkiv på /jobs/alle/.
Version:     1.0.0
Author:      vandpjat.dk
License:     GPL‑2.0+
Update URI: false
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SJB_PATH', plugin_dir_path( __FILE__ ) );

/** Inkluder del‑filer */
require_once SJB_PATH . 'includes/post-types.php';
require_once SJB_PATH . 'includes/cf7-integration.php';
require_once SJB_PATH . 'includes/cron.php';
require_once SJB_PATH . 'includes/query-mods.php';
require_once SJB_PATH . 'includes/blog-sync.php';
require_once SJB_PATH . 'includes/templates.php';

/** Aktivering/deaktivering */
function sjb_activate_plugin() {
	sjb_schedule_job_expiry_cron();
	flush_rewrite_rules();
}
function sjb_deactivate_plugin() {
	sjb_clear_job_expiry_cron();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sjb_activate_plugin' );
register_deactivation_hook( __FILE__, 'sjb_deactivate_plugin' );
