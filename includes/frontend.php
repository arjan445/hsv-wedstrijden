<?php
/**
 * Voorkant: templates, styles, shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Plugin-templates gebruiken tenzij het thema een eigen versie heeft (in de themamap of in /hsv-wedstrijden/). */
add_filter( 'template_include', function ( $template ) {
	$map = array();
	if ( is_post_type_archive( 'hsv_wedstrijd' ) || is_tax( array( 'hsv_discipline', 'hsv_soort' ) ) ) {
		$map = array( 'archive-hsv_wedstrijd.php', 'archive-wedstrijd.php' );
	} elseif ( is_singular( 'hsv_wedstrijd' ) ) {
		$map = array( 'single-hsv_wedstrijd.php', 'single-wedstrijd.php' );
	} elseif ( is_post_type_archive( 'hsv_uitslag' ) ) {
		$map = array( 'archive-hsv_uitslag.php', 'archive-uitslagen.php' );
	} elseif ( is_singular( array( 'hsv_uitslag', 'hsv_startlijst' ) ) ) {
		$map = array( 'single-' . get_post_type() . '.php', 'single-lijst.php' );
	}
	if ( ! $map ) {
		return $template;
	}
	if ( basename( $template ) === $map[0] ) {
		return $template; // Thema heeft zelf een template in de hoofdmap.
	}
	return hsv_locate_template( $map[1] );
}, 20 );

/* Hoofdquery van archieven klein houden: de templates halen zelf hun data op. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( array( 'hsv_wedstrijd', 'hsv_uitslag' ) ) || $q->is_tax( array( 'hsv_discipline', 'hsv_soort' ) ) ) {
		$q->set( 'posts_per_page', 1 );
		$q->set( 'no_found_rows', true );
	}
} );

/* Archieven zonder berichten geven anders een 404. */
add_filter( 'pre_handle_404', function ( $handled, $q ) {
	if ( $q->is_post_type_archive( array( 'hsv_wedstrijd', 'hsv_uitslag' ) ) ) {
		return true;
	}
	return $handled;
}, 10, 2 );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'hsv-wedstrijden', HSV_URL . 'assets/hsv.css', array(), HSV_VERSION );
} );

/* Schema.org-gegevens voor Google op de wedstrijdpagina. */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'hsv_wedstrijd' ) ) {
		return;
	}
	$e = hsv_wedstrijd();
	if ( ! $e || ! $e['start'] ) {
		return;
	}
	$data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'SportsEvent',
		'name'        => $e['title'],
		'startDate'   => $e['start'],
		'endDate'     => $e['end'],
		'url'         => $e['url'],
		'eventStatus' => 'https://schema.org/EventScheduled',
		'location'    => array( '@type' => 'Place', 'name' => $e['location'] ? $e['location'] : get_bloginfo( 'name' ) ),
		'organizer'   => array( '@type' => 'SportsOrganization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
	);
	if ( has_post_thumbnail( $e['id'] ) ) {
		$data['image'] = get_the_post_thumbnail_url( $e['id'], 'large' );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data ) . "</script>\n";
} );

/* ------------------------------------------------------------------
 * Shortcodes
 * [hsv_agenda aantal="5" discipline="springen" soort="knhs-wedstrijd"]
 * [hsv_uitslagen aantal="3"]   laatste wedstrijden met uitslag
 * ---------------------------------------------------------------- */
add_shortcode( 'hsv_agenda', function ( $atts ) {
	$a = shortcode_atts( array( 'aantal' => 5, 'discipline' => '', 'soort' => '' ), $atts );
	$posts = hsv_get_wedstrijden( array( 'limit' => (int) $a['aantal'], 'discipline' => sanitize_title( $a['discipline'] ), 'soort' => sanitize_title( $a['soort'] ) ) );
	if ( ! $posts ) {
		return '<div class="hsv"><p class="hsv-empty">Er staan op dit moment geen wedstrijden gepland.</p></div>';
	}
	$out = '<div class="hsv"><div class="hsv-list">';
	foreach ( $posts as $p ) {
		$out .= hsv_render( 'parts/wedstrijd-rij.php', array( 'post' => $p ) );
	}
	return $out . '</div></div>';
} );

add_shortcode( 'hsv_uitslagen', function ( $atts ) {
	$a = shortcode_atts( array( 'aantal' => 3 ), $atts );
	return '<div class="hsv">' . hsv_render( 'parts/laatste-uitslagen.php', array( 'limit' => (int) $a['aantal'] ) ) . '</div>';
} );
