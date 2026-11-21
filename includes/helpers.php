<?php
/**
 * Hulpfuncties voor thema en templates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hsv_today() {
	return current_time( 'Y-m-d' );
}

function hsv_date( $ymd, $format ) {
	return $ymd ? wp_date( $format, strtotime( $ymd . ' 12:00:00' ), wp_timezone() ) : '';
}

/**
 * Alle gegevens van een wedstrijd als array.
 */
function hsv_wedstrijd( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$e = array( 'id' => $post->ID, 'title' => get_the_title( $post ), 'url' => get_permalink( $post ) );
	foreach ( array_keys( hsv_wedstrijd_fields() ) as $key ) {
		$e[ substr( $key, 5 ) ] = (string) get_post_meta( $post->ID, $key, true );
	}
	if ( ! $e['end'] || $e['end'] < $e['start'] ) {
		$e['end'] = $e['start'];
	}
	$today          = hsv_today();
	$e['past']      = $e['end'] && $e['end'] < $today;
	$e['now']       = $e['start'] && $e['start'] <= $today && $e['end'] >= $today;
	$e['closed']    = $e['deadline'] && $e['deadline'] < $today;
	$e['open']      = ! $e['past'] && ! $e['now'] && ! $e['closed'] && ( $e['signup_url'] || $e['signup_email'] );
	$e['color']     = hsv_wedstrijd_color( $post->ID );
	$e['kind']      = hsv_wedstrijd_kind( $post->ID );
	$e['intern']    = '1' === $e['intern'];
	$e['all_day']   = '' === $e['time_start'];
	$e['time_label'] = hsv_time_label( $e );
	$e['locaties']  = hsv_terms( $post->ID, 'hsv_locatie' );
	$e['is_wedstrijd'] = hsv_is_wedstrijd( $post->ID );
	return $e;
}

function hsv_terms( $post_id, $tax ) {
	$t = get_the_terms( $post_id, $tax );
	return ( $t && ! is_wp_error( $t ) ) ? $t : array();
}

/** Kleur: soort (bijv. KNHS-wedstrijd) gaat voor discipline. */
function hsv_wedstrijd_color( $post_id ) {
	foreach ( array( 'hsv_soort', 'hsv_discipline' ) as $tax ) {
		foreach ( hsv_terms( $post_id, $tax ) as $t ) {
			$c = get_term_meta( $t->term_id, 'hsv_kleur', true );
			if ( $c ) {
				return $c;
			}
		}
	}
	return '#e6e9dc';
}

/** Label zoals "Springen · KNHS-wedstrijd". */
function hsv_wedstrijd_kind( $post_id ) {
	$parts = array();
	foreach ( array( 'hsv_discipline', 'hsv_soort' ) as $tax ) {
		foreach ( hsv_terms( $post_id, $tax ) as $t ) {
			$parts[] = $t->name;
		}
	}
	return implode( ' · ', $parts );
}

function hsv_date_label( $e, $with_weekday = true ) {
	if ( ! $e['start'] ) {
		return '';
	}
	if ( $e['end'] === $e['start'] ) {
		return hsv_date( $e['start'], $with_weekday ? 'l j F Y' : 'j F Y' );
	}
	if ( substr( $e['start'], 0, 7 ) === substr( $e['end'], 0, 7 ) ) {
		return hsv_date( $e['start'], 'j' ) . ' t/m ' . hsv_date( $e['end'], 'j F Y' );
	}
	return hsv_date( $e['start'], 'j F' ) . ' t/m ' . hsv_date( $e['end'], 'j F Y' );
}

/** Tekst in het datumblokje: "10" of "13–15". */
function hsv_day_label( $e ) {
	if ( $e['end'] !== $e['start'] && substr( $e['start'], 0, 7 ) === substr( $e['end'], 0, 7 ) ) {
		return hsv_date( $e['start'], 'j' ) . '–' . hsv_date( $e['end'], 'j' );
	}
	return hsv_date( $e['start'], 'j' );
}

/** Tekstkleur (zwart/wit) die past op een achtergrondkleur. */
function hsv_contrast( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 6 !== strlen( $hex ) ) {
		return '#1d2414';
	}
	list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
	return ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) > 110 ? '#1d2414' : '#ffffff';
}

/** "19:00 – 21:00", "vanaf 19:00" of de vrije toelichting. */
function hsv_time_label( $e ) {
	if ( $e['time_start'] && $e['time_end'] ) {
		$t = $e['time_start'] . ' – ' . $e['time_end'];
	} elseif ( $e['time_start'] ) {
		$t = 'vanaf ' . $e['time_start'];
	} else {
		$t = '';
	}
	if ( $e['time'] ) {
		$t = $t ? $t . ' (' . $e['time'] . ')' : $e['time'];
	}
	return $t;
}

/** Locatienamen als tekst: "Grote hal, Kantine". */
function hsv_locaties_label( $e ) {
	return implode( ', ', wp_list_pluck( $e['locaties'], 'name' ) );
}

/** IDs van soorten die als wedstrijd tellen (homepage, uitslagen). */
function hsv_wedstrijd_soort_ids() {
	static $ids = null;
	if ( null === $ids ) {
		$ids = get_terms( array(
			'taxonomy'   => 'hsv_soort',
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_query' => array( array( 'key' => 'hsv_is_wedstrijd', 'value' => '1' ) ),
		) );
		$ids = is_wp_error( $ids ) ? array() : array_map( 'intval', $ids );
	}
	return $ids;
}

function hsv_is_wedstrijd( $post_id ) {
	$ids = wp_list_pluck( hsv_terms( $post_id, 'hsv_soort' ), 'term_id' );
	return (bool) array_intersect( array_map( 'intval', $ids ), hsv_wedstrijd_soort_ids() );
}

/** Locaties op volgorde. */
function hsv_get_locaties( $hide_empty = false ) {
	$terms = get_terms( array( 'taxonomy' => 'hsv_locatie', 'hide_empty' => $hide_empty ) );
	$terms = is_wp_error( $terms ) ? array() : $terms;
	usort( $terms, function ( $a, $b ) {
		return (int) get_term_meta( $a->term_id, 'hsv_volgorde', true ) <=> (int) get_term_meta( $b->term_id, 'hsv_volgorde', true );
	} );
	return $terms;
}

/** Meta-voorwaarde: interne items overslaan. */
function hsv_public_meta_query() {
	return array(
		'relation' => 'OR',
		array( 'key' => '_hsv_intern', 'compare' => 'NOT EXISTS' ),
		array( 'key' => '_hsv_intern', 'value' => '1', 'compare' => '!=' ),
	);
}

/**
 * Agenda-items die (deels) in een periode vallen, inclusief interne items.
 *
 * @param string $from Y-m-d.
 * @param string $to   Y-m-d.
 * @param array  $args locatie (slug), intern (bool, standaard true), exclude (ID), status.
 */
function hsv_get_range( $from, $to, $args = array() ) {
	$a = wp_parse_args( $args, array( 'locatie' => '', 'intern' => true, 'exclude' => 0, 'status' => 'publish', 'locatie_ids' => array() ) );
	$q = array(
		'post_type'      => 'hsv_wedstrijd',
		'post_status'    => $a['status'],
		'posts_per_page' => -1,
		'meta_key'       => '_hsv_start',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'post__not_in'   => $a['exclude'] ? array( (int) $a['exclude'] ) : array(),
		'meta_query'     => array(
			array( 'key' => '_hsv_start', 'value' => $to, 'compare' => '<=', 'type' => 'DATE' ),
			array( 'key' => '_hsv_end', 'value' => $from, 'compare' => '>=', 'type' => 'DATE' ),
		),
		'tax_query'      => array(),
	);
	if ( ! $a['intern'] ) {
		$q['meta_query'][] = hsv_public_meta_query();
	}
	if ( $a['locatie'] ) {
		$q['tax_query'][] = array( 'taxonomy' => 'hsv_locatie', 'field' => 'slug', 'terms' => $a['locatie'] );
	}
	if ( $a['locatie_ids'] ) {
		$q['tax_query'][] = array( 'taxonomy' => 'hsv_locatie', 'field' => 'term_id', 'terms' => array_map( 'intval', $a['locatie_ids'] ) );
	}
	return get_posts( $q );
}

/** Overlappen twee items in datum én tijd? */
function hsv_overlaps( $a, $b ) {
	if ( ! $a['start'] || ! $b['start'] || $a['start'] > $b['end'] || $b['start'] > $a['end'] ) {
		return false;
	}
	if ( $a['all_day'] || $b['all_day'] ) {
		return true;
	}
	// Tijden gelden per dag; bij meerdaagse items op elke dag dezelfde tijden.
	$a_end = $a['time_end'] ? $a['time_end'] : '23:59';
	$b_end = $b['time_end'] ? $b['time_end'] : '23:59';
	return $a['time_start'] < $b_end && $b['time_start'] < $a_end;
}

/**
 * Andere items die op dezelfde locatie(s) en hetzelfde moment gepland staan.
 *
 * @param array $e Item uit hsv_wedstrijd() (of dezelfde sleutels).
 * @return array[] [ 'item' => array, 'locaties' => string[] ]
 */
function hsv_conflicts( $e ) {
	$loc_ids = array_map( 'intval', wp_list_pluck( $e['locaties'], 'term_id' ) );
	if ( ! $loc_ids || ! $e['start'] ) {
		return array();
	}
	$out = array();
	foreach ( hsv_get_range( $e['start'], $e['end'], array( 'locatie_ids' => $loc_ids, 'exclude' => $e['id'], 'status' => array( 'publish', 'future', 'private', 'draft' ) ) ) as $p ) {
		$o = hsv_wedstrijd( $p );
		if ( ! hsv_overlaps( $e, $o ) ) {
			continue;
		}
		$shared = array();
		foreach ( $o['locaties'] as $t ) {
			if ( in_array( (int) $t->term_id, $loc_ids, true ) ) {
				$shared[] = $t->name;
			}
		}
		$out[] = array( 'item' => $o, 'locaties' => $shared );
	}
	return $out;
}

/**
 * Wedstrijden ophalen.
 *
 * @param array $args when (upcoming|past|all), limit, discipline (slug), soort (slug), locatie (slug), year,
 *                    with_uitslagen (bool), only_wedstrijden (bool: alleen soorten die als wedstrijd tellen),
 *                    intern (bool: ook interne items, standaard niet).
 */
function hsv_get_wedstrijden( $args = array() ) {
	$a = wp_parse_args( $args, array( 'when' => 'upcoming', 'limit' => -1, 'discipline' => '', 'soort' => '', 'locatie' => '', 'year' => '', 'with_uitslagen' => false, 'only_wedstrijden' => false, 'intern' => false ) );
	$q = array(
		'post_type'      => 'hsv_wedstrijd',
		'post_status'    => 'publish',
		'posts_per_page' => $a['limit'],
		'meta_key'       => '_hsv_start',
		'orderby'        => 'meta_value',
		'order'          => 'past' === $a['when'] ? 'DESC' : 'ASC',
		'no_found_rows'  => true,
		'meta_query'     => array(),
		'tax_query'      => array(),
	);
	if ( 'upcoming' === $a['when'] ) {
		$q['meta_query'][] = array( 'key' => '_hsv_end', 'value' => hsv_today(), 'compare' => '>=', 'type' => 'DATE' );
	} elseif ( 'past' === $a['when'] ) {
		$q['meta_query'][] = array( 'key' => '_hsv_end', 'value' => hsv_today(), 'compare' => '<', 'type' => 'DATE' );
	}
	if ( ! $a['intern'] ) {
		$q['meta_query'][] = hsv_public_meta_query();
	}
	if ( $a['only_wedstrijden'] ) {
		$ids = hsv_wedstrijd_soort_ids();
		if ( ! $ids ) {
			return array();
		}
		$q['tax_query'][] = array( 'taxonomy' => 'hsv_soort', 'terms' => $ids );
	}
	if ( $a['year'] ) {
		$q['meta_query'][] = array( 'key' => '_hsv_start', 'value' => array( $a['year'] . '-01-01', $a['year'] . '-12-31' ), 'compare' => 'BETWEEN', 'type' => 'DATE' );
	}
	if ( $a['with_uitslagen'] ) {
		$ids = hsv_wedstrijd_ids_with( 'hsv_uitslag' );
		if ( ! $ids ) {
			return array();
		}
		$q['post__in'] = $ids;
	}
	foreach ( array( 'discipline' => 'hsv_discipline', 'soort' => 'hsv_soort', 'locatie' => 'hsv_locatie' ) as $k => $tax ) {
		if ( $a[ $k ] ) {
			$q['tax_query'][] = array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => $a[ $k ] );
		}
	}
	return get_posts( $q );
}

/** Openbare agenda-items die (deels) in een maand (Y-m) vallen. */
function hsv_get_month_wedstrijden( $ym, $discipline = '', $soort = '', $locatie = '' ) {
	$first = $ym . '-01';
	$last  = gmdate( 'Y-m-t', strtotime( $first . ' 12:00:00' ) );
	$posts = hsv_get_range( $first, $last, array( 'intern' => false, 'locatie' => $locatie ) );
	if ( $discipline || $soort ) {
		$posts = array_values( array_filter( $posts, function ( $p ) use ( $discipline, $soort ) {
			return ( ! $discipline || has_term( $discipline, 'hsv_discipline', $p ) ) && ( ! $soort || has_term( $soort, 'hsv_soort', $p ) );
		} ) );
	}
	return $posts;
}

/** IDs van wedstrijden waar minstens één startlijst/uitslag aan hangt. */
function hsv_wedstrijd_ids_with( $type ) {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = '_hsv_wedstrijd_id' AND p.post_type = %s AND p.post_status = 'publish'",
		$type
	) );
	return array_values( array_filter( array_map( 'absint', $ids ) ) );
}

/**
 * Startlijsten of uitslagen van een wedstrijd.
 *
 * @param string $type hsv_startlijst of hsv_uitslag.
 */
function hsv_get_lists( $wedstrijd_id, $type, $status = 'publish' ) {
	return get_posts( array(
		'post_type'      => $type,
		'post_status'    => $status,
		'posts_per_page' => -1,
		'meta_key'       => '_hsv_wedstrijd_id',
		'meta_value'     => (int) $wedstrijd_id,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		'no_found_rows'  => true,
	) );
}

/** Gegevens van één startlijst/uitslag. */
function hsv_list_item( $post ) {
	$post   = get_post( $post );
	$file   = absint( get_post_meta( $post->ID, '_hsv_bestand_id', true ) );
	$images = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_hsv_afbeeldingen', true ) ) ) );
	$ext    = $file ? strtolower( pathinfo( (string) get_attached_file( $file ), PATHINFO_EXTENSION ) ) : '';
	return array(
		'id'        => $post->ID,
		'title'     => get_the_title( $post ),
		'url'       => get_permalink( $post ),
		'wedstrijd' => absint( get_post_meta( $post->ID, '_hsv_wedstrijd_id', true ) ),
		'file_url'  => $file ? wp_get_attachment_url( $file ) : '',
		'file_ext'  => $ext,
		'link'      => (string) get_post_meta( $post->ID, '_hsv_link', true ),
		'images'    => array_values( $images ),
		'content'   => trim( (string) $post->post_content ),
	);
}

/* ------------------------------------------------------------------
 * Agenda-filters in de URL: ?weergave=maand&maand=2026-11&discipline=springen&soort=knhs-wedstrijd
 * ---------------------------------------------------------------- */
function hsv_agenda_state() {
	$view = ( isset( $_GET['weergave'] ) && in_array( $_GET['weergave'], array( 'maand', 'bezetting' ), true ) ) ? $_GET['weergave'] : 'lijst'; // phpcs:ignore WordPress.Security.NonceVerification
	$loc  = isset( $_GET['locatie'] ) ? sanitize_title( wp_unslash( $_GET['locatie'] ) ) : ''; // phpcs:ignore
	$week = ( isset( $_GET['week'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['week'] ) ) ? $_GET['week'] : current_time( 'Y-m-d' ); // phpcs:ignore
	$week = gmdate( 'Y-m-d', strtotime( $week . ' 12:00:00 -' . ( (int) gmdate( 'N', strtotime( $week . ' 12:00:00' ) ) - 1 ) . ' days' ) ); // maandag
	$ym   = ( isset( $_GET['maand'] ) && preg_match( '/^\d{4}-\d{2}$/', $_GET['maand'] ) ) ? $_GET['maand'] : current_time( 'Y-m' ); // phpcs:ignore
	$disc = isset( $_GET['discipline'] ) ? sanitize_title( wp_unslash( $_GET['discipline'] ) ) : ''; // phpcs:ignore
	$sort = isset( $_GET['soort'] ) ? sanitize_title( wp_unslash( $_GET['soort'] ) ) : ''; // phpcs:ignore
	if ( is_tax( 'hsv_discipline' ) ) {
		$disc = get_queried_object()->slug;
	}
	if ( is_tax( 'hsv_soort' ) ) {
		$sort = get_queried_object()->slug;
	}
	return array( 'view' => $view, 'ym' => $ym, 'disc' => $disc, 'soort' => $sort, 'locatie' => $loc, 'week' => $week );
}

function hsv_agenda_url( $args = array() ) {
	return esc_url( add_query_arg( array_filter( $args ), get_post_type_archive_link( 'hsv_wedstrijd' ) ) );
}

/* ------------------------------------------------------------------
 * Templates: thema kan overschrijven via /hsv-wedstrijden/<bestand> in het thema.
 * ---------------------------------------------------------------- */
function hsv_locate_template( $name ) {
	$theme = locate_template( array( 'hsv-wedstrijden/' . $name ) );
	return $theme ? $theme : HSV_DIR . 'templates/' . $name;
}

function hsv_template( $name, $args = array() ) {
	load_template( hsv_locate_template( $name ), false, $args );
}

function hsv_render( $name, $args = array() ) {
	ob_start();
	hsv_template( $name, $args );
	return ob_get_clean();
}
