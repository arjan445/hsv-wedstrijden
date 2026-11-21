<?php
/**
 * Plugin Name:       HSV Wedstrijden
 * Description:       Agenda, wedstrijden, startlijsten en uitslagen voor HSV De Paardenvriend.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Bongersonline
 * Author URI:        https://bongersonline.nl
 * Text Domain:       hsv-wedstrijden
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HSV_VERSION', '1.0.0' );
define( 'HSV_FILE', __FILE__ );
define( 'HSV_DIR', plugin_dir_path( __FILE__ ) );
define( 'HSV_URL', plugin_dir_url( __FILE__ ) );

require_once HSV_DIR . 'includes/post-types.php';
require_once HSV_DIR . 'includes/helpers.php';
require_once HSV_DIR . 'includes/admin.php';
require_once HSV_DIR . 'includes/frontend.php';
require_once HSV_DIR . 'includes/ical.php';

register_activation_hook( __FILE__, function () {
	hsv_register_types();
	hsv_create_default_terms();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
