<?php
/**
 * Plugin Name:       HSV Wedstrijden
 * Description:       Agenda (wedstrijden, trainingen, vergaderingen), bezetting van locaties, startlijsten en uitslagen voor HSV De Paardenvriend.
 * Version:           1.4.1
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

define( 'HSV_VERSION', '1.4.1' );
define( 'HSV_FILE', __FILE__ );
define( 'HSV_DIR', plugin_dir_path( __FILE__ ) );
define( 'HSV_URL', plugin_dir_url( __FILE__ ) );

require_once HSV_DIR . 'includes/post-types.php';
require_once HSV_DIR . 'includes/helpers.php';
require_once HSV_DIR . 'includes/admin.php';
require_once HSV_DIR . 'includes/frontend.php';
require_once HSV_DIR . 'includes/ical.php';
require_once HSV_DIR . 'includes/rest.php';
require_once HSV_DIR . 'includes/slugs.php';

register_activation_hook( __FILE__, function () {
	hsv_register_types();
	hsv_create_default_terms();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/* Na een update: nieuwe standaardtermen aanmaken en permalinks verversen. */
add_action( 'init', function () {
	if ( get_option( 'hsv_version' ) === HSV_VERSION ) {
		return;
	}
	hsv_create_default_terms();
	foreach ( array( 'Oefenwedstrijd', 'KNHS-wedstrijd', 'Kampioenschap' ) as $name ) {
		$t = get_term_by( 'name', $name, 'hsv_soort' );
		if ( $t && '' === get_term_meta( $t->term_id, 'hsv_is_wedstrijd', true ) ) {
			update_term_meta( $t->term_id, 'hsv_is_wedstrijd', '1' );
		}
	}
	if ( version_compare( (string) get_option( 'hsv_version' ), '1.4.0', '<' ) ) {
		hsv_fix_all_slugs();
	}
	flush_rewrite_rules( false );
	update_option( 'hsv_version', HSV_VERSION );
}, 20 );
