<?php
/**
 * Berichttypes, taxonomieën en velden.
 *
 * Datamodel:
 * - hsv_wedstrijd  : een agenda-item (wedstrijd, training/baanbezetting, vergadering, clubactiviteit).
 *                    Bezet één of meer locaties (hsv_locatie). Intern = niet in de openbare agenda, wel als bezet zichtbaar.
 * - hsv_startlijst : startlijst, hoort bij één wedstrijd (_hsv_wedstrijd_id). Meerdere per wedstrijd mogelijk.
 * - hsv_uitslag    : uitslag, hoort bij één wedstrijd. Meerdere per wedstrijd mogelijk.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'hsv_register_types' );

function hsv_register_types() {
	/* Wedstrijden / agenda */
	register_post_type( 'hsv_wedstrijd', array(
		'labels'        => array(
			'name'               => 'Agenda',
			'singular_name'      => 'Agenda-item',
			'add_new'            => 'Nieuw agenda-item',
			'add_new_item'       => 'Nieuw agenda-item toevoegen',
			'edit_item'          => 'Agenda-item bewerken',
			'new_item'           => 'Nieuw agenda-item',
			'view_item'          => 'Agenda-item bekijken',
			'search_items'       => 'Agenda doorzoeken',
			'not_found'          => 'Geen agenda-items gevonden',
			'not_found_in_trash' => 'Geen agenda-items in de prullenbak',
			'all_items'          => 'Alle agenda-items',
			'menu_name'          => 'Agenda',
		),
		'public'        => true,
		'has_archive'   => 'agenda',
		'rewrite'       => array( 'slug' => 'agenda', 'with_front' => false ),
		'menu_icon'     => 'dashicons-calendar-alt',
		'menu_position' => 4,
		'show_in_rest'  => true,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
	) );

	/* Startlijsten en uitslagen: zelfde opbouw, gekoppeld aan een wedstrijd */
	foreach ( hsv_list_types() as $type => $l ) {
		register_post_type( $type, array(
			'labels'          => array(
				'name'               => $l['plural'],
				'singular_name'      => $l['single'],
				'add_new'            => 'Nieuwe ' . strtolower( $l['single'] ),
				'add_new_item'       => 'Nieuwe ' . strtolower( $l['single'] ) . ' toevoegen',
				'edit_item'          => $l['single'] . ' bewerken',
				'view_item'          => $l['single'] . ' bekijken',
				'search_items'       => $l['plural'] . ' zoeken',
				'not_found'          => 'Geen ' . strtolower( $l['plural'] ) . ' gevonden',
				'not_found_in_trash' => 'Geen ' . strtolower( $l['plural'] ) . ' in de prullenbak',
				'all_items'          => $l['plural'],
				'menu_name'          => $l['plural'],
			),
			'public'          => true,
			'has_archive'     => $l['archive'],
			'rewrite'         => array( 'slug' => $l['slug'], 'with_front' => false ),
			'show_in_menu'    => 'edit.php?post_type=hsv_wedstrijd',
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'revisions', 'page-attributes' ),
			'capability_type' => 'post',
		) );
	}

	/* Taxonomieën */
	register_taxonomy( 'hsv_discipline', 'hsv_wedstrijd', array(
		'labels'            => array( 'name' => 'Disciplines', 'singular_name' => 'Discipline', 'add_new_item' => 'Nieuwe discipline', 'menu_name' => 'Disciplines' ),
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'agenda/discipline', 'with_front' => false ),
	) );
	register_taxonomy( 'hsv_soort', 'hsv_wedstrijd', array(
		'labels'            => array( 'name' => 'Soorten', 'singular_name' => 'Soort', 'add_new_item' => 'Nieuwe soort', 'menu_name' => 'Soorten' ),
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'agenda/soort', 'with_front' => false ),
	) );
	register_taxonomy( 'hsv_locatie', 'hsv_wedstrijd', array(
		'labels'            => array( 'name' => 'Locaties', 'singular_name' => 'Locatie', 'add_new_item' => 'Nieuwe locatie', 'edit_item' => 'Locatie bewerken', 'menu_name' => 'Locaties' ),
		'hierarchical'      => true,
		'public'            => true,
		'publicly_queryable' => false,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => false,
	) );

	/* Velden (ook via REST beschikbaar) */
	$auth = function () {
		return current_user_can( 'edit_posts' );
	};
	foreach ( hsv_wedstrijd_fields() as $key => $f ) {
		register_post_meta( 'hsv_wedstrijd', $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth ) );
	}
	foreach ( array_keys( hsv_list_types() ) as $type ) {
		register_post_meta( $type, '_hsv_wedstrijd_id', array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth ) );
		foreach ( array( '_hsv_bestand_id', '_hsv_link', '_hsv_afbeeldingen' ) as $key ) {
			register_post_meta( $type, $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth ) );
		}
	}
	register_term_meta( 'hsv_discipline', 'hsv_kleur', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
	register_term_meta( 'hsv_soort', 'hsv_kleur', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
	register_term_meta( 'hsv_soort', 'hsv_is_wedstrijd', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
	register_term_meta( 'hsv_locatie', 'hsv_volgorde', array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true ) );
}

function hsv_list_types() {
	return array(
		'hsv_startlijst' => array( 'single' => 'Startlijst', 'plural' => 'Startlijsten', 'slug' => 'startlijsten', 'archive' => false ),
		'hsv_uitslag'    => array( 'single' => 'Uitslag', 'plural' => 'Uitslagen', 'slug' => 'uitslagen', 'archive' => 'uitslagen' ),
	);
}

function hsv_wedstrijd_fields() {
	return array(
		'_hsv_start'        => array( 'label' => 'Begindatum', 'type' => 'date', 'required' => true ),
		'_hsv_end'          => array( 'label' => 'Einddatum', 'type' => 'date', 'help' => 'Leeg laten bij één dag.' ),
		'_hsv_time_start'   => array( 'label' => 'Begintijd', 'type' => 'time', 'help' => 'Leeg = hele dag. Bepaalt wanneer de locatie bezet is.' ),
		'_hsv_time_end'     => array( 'label' => 'Eindtijd', 'type' => 'time' ),
		'_hsv_time'         => array( 'label' => 'Tijd (toelichting)', 'type' => 'text', 'placeholder' => 'bijv. vanaf 09:00, aanmelden vanaf 08:30' ),
		'_hsv_voor'         => array( 'label' => 'Voor wie', 'type' => 'text', 'placeholder' => 'bijv. zaterdag paarden, zondag pony\'s' ),
		'_hsv_location'     => array( 'label' => 'Externe locatie / adres', 'type' => 'text', 'help' => 'Alleen invullen als het niet op de eigen accommodatie is. Hal, parcours of kantine kies je rechts bij "Locaties".' ),
		'_hsv_deadline'     => array( 'label' => 'Inschrijving sluit op', 'type' => 'date' ),
		'_hsv_signup_url'   => array( 'label' => 'Inschrijflink', 'type' => 'url', 'placeholder' => 'Equicompetition, MijnKNHS, …' ),
		'_hsv_signup_email' => array( 'label' => 'Of inschrijven per e-mail', 'type' => 'email' ),
		'_hsv_signup_label' => array( 'label' => 'Tekst inschrijfknop', 'type' => 'text', 'placeholder' => 'bijv. Inschrijven via Equicompetition' ),
		'_hsv_live'         => array( 'label' => 'Live uitslagen (link)', 'type' => 'url', 'help' => 'Alleen zichtbaar tijdens de wedstrijd.' ),
		'_hsv_intern'       => array( 'label' => 'Intern', 'type' => 'checkbox', 'text' => 'Niet in de openbare agenda tonen (bijv. bestuursvergadering). De locatie wordt wel als "bezet" getoond.' ),
	);
}

/* Standaard termen + kleuren uit de seizoenskalender */
function hsv_create_default_terms() {
	$terms = array(
		'hsv_discipline'       => array( 'Springen' => '#b2bf79', 'Dressuur' => '#849b6f', 'Clubactiviteit' => '#d9dfc0' ),
		'hsv_soort'            => array( 'Oefenwedstrijd' => '', 'KNHS-wedstrijd' => '#9b9074', 'Kampioenschap' => '#9b9074', 'Training' => '#dfe3d3', 'Vergadering' => '#e3e0d6' ),
		'hsv_locatie'          => array( 'Grote hal' => 1, 'Buitenparcours' => 2, 'Kantine' => 3 ),
	);
	foreach ( $terms as $tax => $list ) {
		foreach ( $list as $name => $value ) {
			if ( term_exists( $name, $tax ) ) {
				continue;
			}
			$t = wp_insert_term( $name, $tax );
			if ( is_wp_error( $t ) ) {
				continue;
			}
			if ( 'hsv_locatie' === $tax ) {
				update_term_meta( $t['term_id'], 'hsv_volgorde', $value );
				continue;
			}
			if ( $value ) {
				update_term_meta( $t['term_id'], 'hsv_kleur', $value );
			}
			if ( 'hsv_soort' === $tax && in_array( $name, array( 'Oefenwedstrijd', 'KNHS-wedstrijd', 'Kampioenschap' ), true ) ) {
				update_term_meta( $t['term_id'], 'hsv_is_wedstrijd', '1' );
			}
		}
	}
}
