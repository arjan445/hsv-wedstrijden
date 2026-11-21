<?php
/**
 * iCal-feed: /agenda/?ical=1 (alle komende wedstrijden, ook als abonnement)
 * of ?ical=1 op een wedstrijdpagina (alleen die wedstrijd).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hsv_ical_url( $post_id = 0 ) {
	return $post_id ? add_query_arg( 'ical', 1, get_permalink( $post_id ) ) : add_query_arg( 'ical', 1, get_post_type_archive_link( 'hsv_wedstrijd' ) );
}

add_action( 'template_redirect', function () {
	if ( empty( $_GET['ical'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	if ( is_singular( 'hsv_wedstrijd' ) ) {
		$posts = array( get_queried_object() );
		$name  = sanitize_title( get_the_title() );
	} elseif ( is_post_type_archive( 'hsv_wedstrijd' ) ) {
		$posts = hsv_get_wedstrijden( array( 'when' => 'all', 'limit' => 200 ) );
		$posts = array_filter( $posts, function ( $p ) {
			return get_post_meta( $p->ID, '_hsv_end', true ) >= gmdate( 'Y-m-d', strtotime( '-60 days' ) );
		} );
		$name  = 'agenda-' . sanitize_title( get_bloginfo( 'name' ) );
	} else {
		return;
	}

	$esc   = function ( $s ) {
		$s = wp_strip_all_tags( html_entity_decode( (string) $s, ENT_QUOTES, 'UTF-8' ) );
		return str_replace( array( '\\', ';', ',', "\r\n", "\n" ), array( '\\\\', '\;', '\,', '\n', '\n' ), $s );
	};
	$host  = wp_parse_url( home_url(), PHP_URL_HOST );
	$lines = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//' . $esc( get_bloginfo( 'name' ) ) . '//Agenda//NL',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'X-WR-CALNAME:' . $esc( get_bloginfo( 'name' ) ),
		'X-WR-TIMEZONE:Europe/Amsterdam',
		'REFRESH-INTERVAL;VALUE=DURATION:PT12H',
	);
	foreach ( $posts as $p ) {
		$e = hsv_wedstrijd( $p );
		if ( ! $e || ! $e['start'] ) {
			continue;
		}
		$desc = array_filter( array( $e['time'], $e['voor'], get_the_excerpt( $p ), $e['url'] ) );
		$lines[] = 'BEGIN:VEVENT';
		$lines[] = 'UID:wedstrijd-' . $p->ID . '@' . $host;
		$lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z', strtotime( $p->post_modified_gmt . ' UTC' ) );
		$lines[] = 'DTSTART;VALUE=DATE:' . str_replace( '-', '', $e['start'] );
		$lines[] = 'DTEND;VALUE=DATE:' . gmdate( 'Ymd', strtotime( $e['end'] . ' 12:00:00 +1 day' ) );
		$lines[] = 'SUMMARY:' . $esc( $e['title'] );
		$lines[] = 'DESCRIPTION:' . $esc( implode( "\n", $desc ) );
		$lines[] = 'LOCATION:' . $esc( $e['location'] ? $e['location'] : apply_filters( 'hsv_default_location', get_bloginfo( 'name' ) ) );
		$lines[] = 'URL:' . $e['url'];
		$lines[] = 'END:VEVENT';
	}
	$lines[] = 'END:VCALENDAR';

	// Regels langer dan 75 tekens vouwen (RFC 5545).
	$out = '';
	foreach ( $lines as $line ) {
		while ( strlen( $line ) > 74 ) {
			$cut  = mb_strcut( $line, 0, 74, 'UTF-8' );
			$out .= $cut . "\r\n ";
			$line = substr( $line, strlen( $cut ) );
		}
		$out .= $line . "\r\n";
	}

	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: inline; filename="' . $name . '.ics"' );
	echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
} );
