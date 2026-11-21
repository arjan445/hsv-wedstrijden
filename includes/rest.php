<?php
/**
 * REST-endpoint voor FullCalendar: /wp-json/hsv/v1/agenda?start=…&end=…&discipline=…&soort=…&locatie=…
 * Geeft alleen openbare agenda-items (interne items niet).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'hsv/v1', '/agenda', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'start'      => array( 'type' => 'string', 'required' => true ),
			'end'        => array( 'type' => 'string', 'required' => true ),
			'discipline' => array( 'type' => 'string', 'default' => '' ),
			'soort'      => array( 'type' => 'string', 'default' => '' ),
			'locatie'    => array( 'type' => 'string', 'default' => '' ),
		),
		'callback'            => 'hsv_rest_agenda',
	) );
} );

function hsv_rest_agenda( WP_REST_Request $req ) {
	$start = substr( (string) $req['start'], 0, 10 );
	$end   = substr( (string) $req['end'], 0, 10 );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end ) || $end < $start ) {
		return new WP_Error( 'hsv_bad_range', 'Ongeldige periode.', array( 'status' => 400 ) );
	}
	// FullCalendar geeft een exclusieve einddatum; maximaal ±3 maanden per verzoek.
	$end = gmdate( 'Y-m-d', strtotime( $end . ' 12:00:00 -1 day' ) );
	if ( ( strtotime( $end ) - strtotime( $start ) ) > 100 * DAY_IN_SECONDS ) {
		$end = gmdate( 'Y-m-d', strtotime( $start . ' 12:00:00 +100 days' ) );
	}
	$disc  = sanitize_title( (string) $req['discipline'] );
	$soort = sanitize_title( (string) $req['soort'] );
	$posts = hsv_get_range( $start, $end, array( 'intern' => false, 'locatie' => sanitize_title( (string) $req['locatie'] ) ) );

	$out = array();
	foreach ( $posts as $p ) {
		if ( ( $disc && ! has_term( $disc, 'hsv_discipline', $p ) ) || ( $soort && ! has_term( $soort, 'hsv_soort', $p ) ) ) {
			continue;
		}
		$e  = hsv_wedstrijd( $p );
		$ev = array(
			'id'              => $e['id'],
			'title'           => html_entity_decode( $e['title'], ENT_QUOTES, 'UTF-8' ),
			'url'             => $e['url'],
			'backgroundColor' => $e['color'],
			'borderColor'     => $e['color'],
			'textColor'       => hsv_contrast( $e['color'] ),
			'extendedProps'   => array(
				'kind'     => $e['kind'],
				'time'     => $e['time_label'],
				'locaties' => hsv_locaties_label( $e ),
				'voor'     => $e['voor'],
				'open'     => $e['open'],
				'now'      => $e['now'] && $e['is_wedstrijd'],
				'dateText' => hsv_date_label( $e ),
			),
		);
		if ( $e['all_day'] ) {
			$ev['allDay'] = true;
			$ev['start']  = $e['start'];
			$ev['end']    = gmdate( 'Y-m-d', strtotime( $e['end'] . ' 12:00:00 +1 day' ) );
		} else {
			$ev['allDay'] = false;
			$ev['start']  = $e['start'] . 'T' . $e['time_start'] . ':00';
			$ev['end']    = $e['end'] . 'T' . ( $e['time_end'] ? $e['time_end'] : $e['time_start'] ) . ':00';
		}
		$out[] = $ev;
	}
	$res = rest_ensure_response( $out );
	$res->header( 'Cache-Control', 'public, max-age=300' );
	return $res;
}
