<?php
/**
 * Permalink met datum: /agenda/springclinic-2026-11-23/ in plaats van /agenda/springclinic-3/.
 *
 * Alleen automatische slugs worden aangepast (leeg, gelijk aan de titel, titel-2, of al titel-datum).
 * Een zelf ingevulde slug blijft staan. Bij een datumwijziging verhuist de URL mee; WordPress
 * onthoudt de oude slug en stuurt die automatisch door.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Gewenste slug: titel + begindatum. */
function hsv_dated_slug( $post ) {
	$post  = get_post( $post );
	$start = (string) get_post_meta( $post->ID, '_hsv_start', true );
	$base  = sanitize_title( $post->post_title );
	if ( ! $start || '' === $base ) {
		return '';
	}
	return $base . '-' . $start;
}

/** Is de huidige slug automatisch (en mogen we hem dus aanpassen)? */
function hsv_is_auto_slug( $post ) {
	$name = (string) $post->post_name;
	if ( '' === $name || 0 === strpos( $name, '__trashed' ) ) {
		return true;
	}
	$base = sanitize_title( $post->post_title );
	$name = preg_replace( '/__trashed$/', '', $name );
	// titel, titel-2, titel-2026-11-23, titel-2026-11-23-2, of een eerdere titel-datum (titel gewijzigd).
	return (bool) preg_match( '/^' . preg_quote( $base, '/' ) . '(-\d+)?$/', $name )
		|| (bool) preg_match( '/-\d{4}-\d{2}-\d{2}(-\d+)?$/', $name );
}

function hsv_apply_dated_slug( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'hsv_wedstrijd' !== $post->post_type || in_array( $post->post_status, array( 'auto-draft', 'trash', 'inherit' ), true ) ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || ! hsv_is_auto_slug( $post ) ) {
		return;
	}
	$want = hsv_dated_slug( $post );
	if ( ! $want ) {
		return;
	}
	$want = wp_unique_post_slug( $want, $post->ID, 'publish', $post->post_type, 0 );
	if ( $want === $post->post_name ) {
		return;
	}
	remove_action( 'save_post_hsv_wedstrijd', 'hsv_apply_dated_slug', 99 );
	wp_update_post( array( 'ID' => $post->ID, 'post_name' => $want ) );
	add_action( 'save_post_hsv_wedstrijd', 'hsv_apply_dated_slug', 99 );
}
// Na het opslaan van de velden (die draaien op prioriteit 10).
add_action( 'save_post_hsv_wedstrijd', 'hsv_apply_dated_slug', 99 );

/** Eenmalig bij een update: bestaande agenda-items een datum-slug geven. */
function hsv_fix_all_slugs() {
	$ids = get_posts( array(
		'post_type'      => 'hsv_wedstrijd',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	foreach ( $ids as $id ) {
		hsv_apply_dated_slug( $id );
	}
}
