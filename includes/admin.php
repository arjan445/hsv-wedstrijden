<?php
/**
 * Beheer: invulvelden, koppelingen, kolommen en kleuren.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Metaboxen
 * ---------------------------------------------------------------- */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'hsv_wedstrijd_data', 'Gegevens', 'hsv_metabox_wedstrijd', 'hsv_wedstrijd', 'normal', 'high' );
	add_meta_box( 'hsv_bezetting', 'Bezetting locaties', 'hsv_metabox_bezetting', 'hsv_wedstrijd', 'side', 'high' );
	add_meta_box( 'hsv_wedstrijd_lists', 'Startlijsten & uitslagen', 'hsv_metabox_wedstrijd_lists', 'hsv_wedstrijd', 'side', 'high' );
	foreach ( hsv_list_types() as $type => $l ) {
		add_meta_box( 'hsv_list_data', $l['single'], 'hsv_metabox_list', $type, 'normal', 'high' );
	}
} );

function hsv_metabox_wedstrijd( $post ) {
	wp_nonce_field( 'hsv_save', 'hsv_nonce' );
	echo '<table class="form-table hsv-form"><tbody>';
	foreach ( hsv_wedstrijd_fields() as $key => $f ) {
		$val  = (string) get_post_meta( $post->ID, $key, true );
		$req  = ! empty( $f['required'] );
		$help = ! empty( $f['help'] ) ? '<p class="description">' . esc_html( $f['help'] ) . '</p>' : '';
		if ( 'checkbox' === $f['type'] ) {
			$input = sprintf( '<label><input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s> %3$s</label>', esc_attr( $key ), checked( $val, '1', false ), esc_html( $f['text'] ) );
		} else {
			$input = sprintf(
				'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" class="%4$s" placeholder="%5$s"%6$s>',
				esc_attr( $f['type'] ),
				esc_attr( $key ),
				esc_attr( $val ),
				in_array( $f['type'], array( 'date', 'time' ), true ) ? '' : 'regular-text',
				esc_attr( isset( $f['placeholder'] ) ? $f['placeholder'] : '' ),
				$req ? ' required' : ''
			);
		}
		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s%3$s</label></th><td>%4$s%5$s</td></tr>',
			esc_attr( $key ),
			esc_html( $f['label'] ),
			$req ? ' <span style="color:#b32d2e">*</span>' : '',
			$input, // phpcs:ignore
			$help // phpcs:ignore
		);
	}
	echo '</tbody></table>';
	echo '<p class="description">Flyer: gebruik de uitgelichte afbeelding. Kies rechts de locatie(s) die bezet zijn. Startlijsten en uitslagen voeg je toe via het blok rechts.</p>';
}

function hsv_metabox_bezetting( $post ) {
	echo '<div id="hsv-conflicts" data-post="' . (int) $post->ID . '" data-nonce="' . esc_attr( wp_create_nonce( 'hsv_conflicts' ) ) . '">';
	echo hsv_conflicts_html( 'auto-draft' === $post->post_status ? null : hsv_wedstrijd( $post ) ); // phpcs:ignore
	echo '</div>';
}

/** HTML voor de bezettingscontrole. */
function hsv_conflicts_html( $e ) {
	if ( ! $e || ! $e['start'] ) {
		return '<p class="description">Vul een datum in en kies rechts de locatie(s); hier zie je dan of die al bezet zijn.</p>';
	}
	if ( ! $e['locaties'] ) {
		return '<p class="description">Geen locatie gekozen, dus geen bezetting.</p>';
	}
	$c = hsv_conflicts( $e );
	if ( ! $c ) {
		return '<p style="color:#007017;margin:0"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html( hsv_locaties_label( $e ) ) . ' is vrij op dit moment.</p>';
	}
	$out = '<p style="color:#b32d2e;margin:0 0 6px"><span class="dashicons dashicons-warning"></span> <strong>Let op: dubbel geboekt</strong></p><ul style="margin:0">';
	foreach ( $c as $x ) {
		$o    = $x['item'];
		$out .= sprintf(
			'<li style="margin-bottom:6px"><a href="%s">%s</a><br><small>%s · %s · %s</small></li>',
			esc_url( get_edit_post_link( $o['id'] ) ),
			esc_html( $o['title'] ),
			esc_html( implode( ', ', $x['locaties'] ) ),
			esc_html( hsv_date_label( $o, false ) ),
			esc_html( $o['time_label'] ? $o['time_label'] : 'hele dag' )
		);
	}
	return $out . '</ul>';
}

/* Live controle in de editor */
add_action( 'wp_ajax_hsv_conflicts', function () {
	check_ajax_referer( 'hsv_conflicts', 'nonce' );
	$id = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
	if ( ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error();
	}
	$d     = function ( $k ) {
		$v = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : ''; // phpcs:ignore
		return $v;
	};
	$start = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d( 'start' ) ) ? $d( 'start' ) : '';
	$end   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d( 'end' ) ) ? $d( 'end' ) : '';
	$ts    = preg_match( '/^\d{2}:\d{2}$/', $d( 'time_start' ) ) ? $d( 'time_start' ) : '';
	$te    = preg_match( '/^\d{2}:\d{2}$/', $d( 'time_end' ) ) ? $d( 'time_end' ) : '';
	$locs  = isset( $_POST['locaties'] ) ? array_filter( array_map( 'absint', (array) $_POST['locaties'] ) ) : array();
	$terms = $locs ? get_terms( array( 'taxonomy' => 'hsv_locatie', 'include' => $locs, 'hide_empty' => false ) ) : array();
	$e     = array(
		'id'         => $id,
		'start'      => $start,
		'end'        => ( $end && $end >= $start ) ? $end : $start,
		'time_start' => $ts,
		'time_end'   => $te,
		'all_day'    => '' === $ts,
		'locaties'   => is_wp_error( $terms ) ? array() : $terms,
	);
	wp_send_json_success( array( 'html' => hsv_conflicts_html( $start ? $e : null ) ) );
} );

function hsv_metabox_wedstrijd_lists( $post ) {
	if ( 'auto-draft' === $post->post_status ) {
		echo '<p>Sla de wedstrijd eerst op (concept mag). Daarna kun je hier startlijsten en uitslagen toevoegen.</p>';
		return;
	}
	foreach ( hsv_list_types() as $type => $l ) {
		$items = hsv_get_lists( $post->ID, $type, array( 'publish', 'draft', 'pending', 'future', 'private' ) );
		echo '<h4 style="margin:12px 0 6px">' . esc_html( $l['plural'] ) . '</h4>';
		if ( $items ) {
			echo '<ul style="margin:0 0 8px">';
			foreach ( $items as $i ) {
				$status = 'publish' === $i->post_status ? '' : ' <em>(' . esc_html( get_post_status_object( $i->post_status )->label ) . ')</em>';
				printf( '<li><a href="%s">%s</a>%s</li>', esc_url( get_edit_post_link( $i->ID ) ), esc_html( get_the_title( $i ) ), $status ); // phpcs:ignore
			}
			echo '</ul>';
		} else {
			echo '<p style="margin:0 0 8px;color:#646970">Nog geen ' . esc_html( strtolower( $l['plural'] ) ) . '.</p>';
		}
		printf(
			'<a class="button" href="%s">+ %s</a>',
			esc_url( admin_url( 'post-new.php?post_type=' . $type . '&hsv_wedstrijd=' . $post->ID ) ),
			esc_html( $l['single'] )
		);
	}
}

function hsv_metabox_list( $post ) {
	wp_nonce_field( 'hsv_save', 'hsv_nonce' );
	$current = absint( get_post_meta( $post->ID, '_hsv_wedstrijd_id', true ) );
	if ( ! $current && isset( $_GET['hsv_wedstrijd'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$current = absint( $_GET['hsv_wedstrijd'] ); // phpcs:ignore
	}
	$wedstrijden = get_posts( array(
		'post_type'      => 'hsv_wedstrijd',
		'post_status'    => array( 'publish', 'draft', 'future', 'private' ),
		'posts_per_page' => 300,
		'meta_key'       => '_hsv_start',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
	) );
	$file_id = absint( get_post_meta( $post->ID, '_hsv_bestand_id', true ) );
	$images  = (string) get_post_meta( $post->ID, '_hsv_afbeeldingen', true );
	?>
	<table class="form-table hsv-form"><tbody>
		<tr>
			<th scope="row"><label for="hsv_wedstrijd_id">Hoort bij wedstrijd <span style="color:#b32d2e">*</span></label></th>
			<td>
				<select id="hsv_wedstrijd_id" name="_hsv_wedstrijd_id" required style="min-width:340px">
					<option value="">— Kies een wedstrijd —</option>
					<?php foreach ( $wedstrijden as $w ) : ?>
						<option value="<?php echo (int) $w->ID; ?>" <?php selected( $current, $w->ID ); ?>><?php echo esc_html( hsv_date( get_post_meta( $w->ID, '_hsv_start', true ), 'd-m-Y' ) . ' – ' . get_the_title( $w ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row">Bestand (PDF, Excel)</th>
			<td class="hsv-file">
				<input type="hidden" name="_hsv_bestand_id" value="<?php echo $file_id ? (int) $file_id : ''; ?>">
				<span class="hsv-file-name"><?php echo $file_id ? esc_html( basename( (string) get_attached_file( $file_id ) ) ) : 'Geen bestand gekozen'; ?></span>
				<p>
					<button type="button" class="button hsv-file-pick">Bestand kiezen</button>
					<button type="button" class="button-link hsv-file-clear"<?php echo $file_id ? '' : ' hidden'; ?>>Verwijderen</button>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="hsv_link">Externe link</label></th>
			<td>
				<input type="url" id="hsv_link" name="_hsv_link" value="<?php echo esc_attr( get_post_meta( $post->ID, '_hsv_link', true ) ); ?>" class="regular-text" placeholder="https://equicompetition.nl/…">
				<p class="description">Bijvoorbeeld de startlijst of uitslag op Equicompetition, MijnKNHS of startlijsten.nl.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Afbeeldingen</th>
			<td class="hsv-gallery">
				<input type="hidden" name="_hsv_afbeeldingen" value="<?php echo esc_attr( $images ); ?>">
				<div class="hsv-gallery-preview" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
					<?php
					foreach ( array_filter( array_map( 'absint', explode( ',', $images ) ) ) as $img ) {
						echo wp_get_attachment_image( $img, array( 80, 80 ), false, array( 'style' => 'width:80px;height:80px;object-fit:cover;border-radius:4px' ) );
					}
					?>
				</div>
				<button type="button" class="button hsv-gallery-pick">Afbeeldingen kiezen</button>
				<button type="button" class="button-link hsv-gallery-clear"<?php echo $images ? '' : ' hidden'; ?>>Alles verwijderen</button>
				<p class="description">Bijvoorbeeld foto's van een uitslagenbord of een uitslag als afbeelding. Meerdere mogelijk.</p>
			</td>
		</tr>
	</tbody></table>
	<p class="description">Tekst of een tabel in de editor hierboven wordt ook getoond. Meerdere lijsten per wedstrijd? Maak er gewoon nog een aan, bijvoorbeeld "Zaterdag – paarden" en "Zondag – pony's". De volgorde stel je in bij "Volgorde" (rechts, onder Pagina-attributen; laag = eerst).</p>
	<?php
}

/* ------------------------------------------------------------------
 * Opslaan
 * ---------------------------------------------------------------- */
function hsv_can_save( $post_id ) {
	if ( ! isset( $_POST['hsv_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hsv_nonce'] ), 'hsv_save' ) ) {
		return false;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return false;
	}
	return current_user_can( 'edit_post', $post_id );
}

add_action( 'save_post_hsv_wedstrijd', function ( $post_id ) {
	if ( ! hsv_can_save( $post_id ) ) {
		return;
	}
	foreach ( hsv_wedstrijd_fields() as $key => $f ) {
		if ( 'checkbox' === $f['type'] ) {
			update_post_meta( $post_id, $key, empty( $_POST[ $key ] ) ? '' : '1' );
			continue;
		}
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = trim( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore
		switch ( $f['type'] ) {
			case 'url':
				$val = esc_url_raw( $raw );
				break;
			case 'email':
				$val = sanitize_email( $raw );
				break;
			case 'date':
				$val = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
				break;
			case 'time':
				$val = preg_match( '/^\d{2}:\d{2}$/', $raw ) ? $raw : '';
				break;
			default:
				$val = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, $key, $val );
	}
	// Einddatum altijd vullen zodat komend/afgelopen in één query kan.
	$start = get_post_meta( $post_id, '_hsv_start', true );
	$end   = get_post_meta( $post_id, '_hsv_end', true );
	if ( $start && ( ! $end || $end < $start ) ) {
		update_post_meta( $post_id, '_hsv_end', $start );
	}
} );

function hsv_save_list( $post_id ) {
	if ( ! hsv_can_save( $post_id ) ) {
		return;
	}
	if ( isset( $_POST['_hsv_wedstrijd_id'] ) ) {
		update_post_meta( $post_id, '_hsv_wedstrijd_id', absint( $_POST['_hsv_wedstrijd_id'] ) );
	}
	if ( isset( $_POST['_hsv_bestand_id'] ) ) {
		$id = absint( $_POST['_hsv_bestand_id'] );
		update_post_meta( $post_id, '_hsv_bestand_id', $id ? (string) $id : '' );
	}
	if ( isset( $_POST['_hsv_link'] ) ) {
		update_post_meta( $post_id, '_hsv_link', esc_url_raw( wp_unslash( $_POST['_hsv_link'] ) ) ); // phpcs:ignore
	}
	if ( isset( $_POST['_hsv_afbeeldingen'] ) ) {
		$ids = array_filter( array_map( 'absint', explode( ',', wp_unslash( $_POST['_hsv_afbeeldingen'] ) ) ) ); // phpcs:ignore
		update_post_meta( $post_id, '_hsv_afbeeldingen', implode( ',', $ids ) );
	}
}
add_action( 'save_post_hsv_startlijst', 'hsv_save_list' );
add_action( 'save_post_hsv_uitslag', 'hsv_save_list' );

/* Titelvoorstel bij "+ Uitslag" vanuit een wedstrijd */
add_filter( 'default_title', function ( $title, $post ) {
	if ( isset( $_GET['hsv_wedstrijd'] ) && array_key_exists( $post->post_type, hsv_list_types() ) ) { // phpcs:ignore
		$w = get_post( absint( $_GET['hsv_wedstrijd'] ) ); // phpcs:ignore
		if ( $w ) {
			$types = hsv_list_types();
			return $types[ $post->post_type ]['single'] . ' ' . get_the_title( $w );
		}
	}
	return $title;
}, 10, 2 );

/* ------------------------------------------------------------------
 * Kolommen in de overzichten
 * ---------------------------------------------------------------- */
add_filter( 'manage_hsv_wedstrijd_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( 'date' === $k ) {
			continue;
		}
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['hsv_datum']  = 'Datum';
			$new['hsv_lijsten'] = 'Startlijsten / uitslagen';
		}
	}
	return $new;
} );
add_action( 'manage_hsv_wedstrijd_posts_custom_column', function ( $col, $post_id ) {
	if ( 'hsv_datum' === $col ) {
		$e = hsv_wedstrijd( $post_id );
		echo esc_html( hsv_date_label( $e, false ) );
		if ( $e['time_label'] ) {
			echo '<br><small>' . esc_html( $e['time_label'] ) . '</small>';
		}
		if ( $e['intern'] ) {
			echo '<br><span style="color:#646970">intern</span>';
		}
		if ( ! $e['past'] && hsv_conflicts( $e ) ) {
			echo '<br><span style="color:#b32d2e"><span class="dashicons dashicons-warning" style="font-size:16px"></span> dubbel geboekt</span>';
		}
	} elseif ( 'hsv_lijsten' === $col ) {
		echo (int) count( hsv_get_lists( $post_id, 'hsv_startlijst' ) ) . ' / ' . (int) count( hsv_get_lists( $post_id, 'hsv_uitslag' ) );
	}
}, 10, 2 );
add_filter( 'manage_edit-hsv_wedstrijd_sortable_columns', function ( $cols ) {
	$cols['hsv_datum'] = 'hsv_datum';
	return $cols;
} );

foreach ( array_keys( hsv_list_types() ) as $hsv_type ) {
	add_filter( "manage_{$hsv_type}_posts_columns", function ( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['hsv_wedstrijd'] = 'Wedstrijd';
			}
		}
		return $new;
	} );
	add_action( "manage_{$hsv_type}_posts_custom_column", function ( $col, $post_id ) {
		if ( 'hsv_wedstrijd' !== $col ) {
			return;
		}
		$w = absint( get_post_meta( $post_id, '_hsv_wedstrijd_id', true ) );
		if ( $w && get_post( $w ) ) {
			printf( '<a href="%s">%s</a><br><small>%s</small>', esc_url( get_edit_post_link( $w ) ), esc_html( get_the_title( $w ) ), esc_html( hsv_date( get_post_meta( $w, '_hsv_start', true ), 'j F Y' ) ) );
		} else {
			echo '<span style="color:#b32d2e">Niet gekoppeld</span>';
		}
	}, 10, 2 );
}

/* Filter "per wedstrijd" boven het overzicht van startlijsten/uitslagen */
add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( ! array_key_exists( $post_type, hsv_list_types() ) ) {
		return;
	}
	$current = isset( $_GET['hsv_wedstrijd_filter'] ) ? absint( $_GET['hsv_wedstrijd_filter'] ) : 0; // phpcs:ignore
	$ids     = hsv_wedstrijd_ids_with( $post_type );
	echo '<select name="hsv_wedstrijd_filter"><option value="">Alle wedstrijden</option>';
	foreach ( $ids as $id ) {
		printf( '<option value="%d"%s>%s</option>', (int) $id, selected( $current, $id, false ), esc_html( hsv_date( get_post_meta( $id, '_hsv_start', true ), 'd-m-Y' ) . ' – ' . get_the_title( $id ) ) );
	}
	echo '</select>';
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$type = $q->get( 'post_type' );
	if ( 'hsv_wedstrijd' === $type ) {
		$q->set( 'meta_key', '_hsv_start' );
		$q->set( 'orderby', 'meta_value' );
		if ( ! isset( $_GET['order'] ) ) { // phpcs:ignore
			$q->set( 'order', 'DESC' );
		}
	} elseif ( array_key_exists( (string) $type, hsv_list_types() ) && ! empty( $_GET['hsv_wedstrijd_filter'] ) ) { // phpcs:ignore
		$q->set( 'meta_key', '_hsv_wedstrijd_id' );
		$q->set( 'meta_value', absint( $_GET['hsv_wedstrijd_filter'] ) ); // phpcs:ignore
	}
} );

/* ------------------------------------------------------------------
 * Kleur per discipline / soort
 * ---------------------------------------------------------------- */
foreach ( array( 'hsv_discipline', 'hsv_soort' ) as $hsv_tax ) {
	add_action( "{$hsv_tax}_add_form_fields", function () {
		echo '<div class="form-field"><label for="hsv_kleur">Kleur in agenda</label><input type="color" id="hsv_kleur" name="hsv_kleur" value="#b2bf79"><p>Bij een soort met kleur gaat die voor de kleur van de discipline.</p></div>';
	} );
	add_action( "{$hsv_tax}_edit_form_fields", function ( $term ) {
		$c = get_term_meta( $term->term_id, 'hsv_kleur', true );
		printf(
			'<tr class="form-field"><th><label for="hsv_kleur">Kleur in agenda</label></th><td><input type="color" id="hsv_kleur" name="hsv_kleur" value="%s"> <label><input type="checkbox" name="hsv_kleur_leeg" value="1"%s> geen eigen kleur</label></td></tr>',
			esc_attr( $c ? $c : '#b2bf79' ),
			checked( ! $c, true, false )
		);
	} );
	$save = function ( $term_id ) {
		if ( ! current_user_can( 'manage_categories' ) || ! isset( $_POST['hsv_kleur'] ) ) { // phpcs:ignore
			return;
		}
		if ( ! empty( $_POST['hsv_kleur_leeg'] ) ) { // phpcs:ignore
			delete_term_meta( $term_id, 'hsv_kleur' );
			return;
		}
		$c = sanitize_hex_color( wp_unslash( $_POST['hsv_kleur'] ) ); // phpcs:ignore
		if ( $c ) {
			update_term_meta( $term_id, 'hsv_kleur', $c );
		}
	};
	add_action( "created_{$hsv_tax}", $save );
	add_action( "edited_{$hsv_tax}", $save );
}

/* "Telt als wedstrijd" per soort, volgorde per locatie */
add_action( 'hsv_soort_add_form_fields', function () {
	echo '<div class="form-field"><label><input type="checkbox" name="hsv_is_wedstrijd" value="1"> Telt als wedstrijd</label><p>Wedstrijden komen op de homepage en bij de uitslagen. Trainingen en vergaderingen niet.</p></div>';
} );
add_action( 'hsv_soort_edit_form_fields', function ( $term ) {
	printf( '<tr class="form-field"><th>Wedstrijd</th><td><label><input type="checkbox" name="hsv_is_wedstrijd" value="1"%s> Telt als wedstrijd</label><p class="description">Wedstrijden komen op de homepage en bij de uitslagen. Trainingen en vergaderingen niet.</p></td></tr>', checked( get_term_meta( $term->term_id, 'hsv_is_wedstrijd', true ), '1', false ) );
} );
$hsv_save_soort = function ( $term_id ) {
	if ( current_user_can( 'manage_categories' ) && isset( $_POST['hsv_kleur'] ) ) { // phpcs:ignore
		update_term_meta( $term_id, 'hsv_is_wedstrijd', empty( $_POST['hsv_is_wedstrijd'] ) ? '0' : '1' ); // phpcs:ignore
	}
};
add_action( 'created_hsv_soort', $hsv_save_soort );
add_action( 'edited_hsv_soort', $hsv_save_soort );

add_action( 'hsv_locatie_add_form_fields', function () {
	echo '<div class="form-field"><label for="hsv_volgorde">Volgorde</label><input type="number" id="hsv_volgorde" name="hsv_volgorde" value="10" min="0"><p>Volgorde in het bezettingsoverzicht (laag = bovenaan).</p></div>';
} );
add_action( 'hsv_locatie_edit_form_fields', function ( $term ) {
	printf( '<tr class="form-field"><th><label for="hsv_volgorde">Volgorde</label></th><td><input type="number" id="hsv_volgorde" name="hsv_volgorde" value="%d" min="0"><p class="description">Volgorde in het bezettingsoverzicht (laag = bovenaan).</p></td></tr>', (int) get_term_meta( $term->term_id, 'hsv_volgorde', true ) );
} );
$hsv_save_loc = function ( $term_id ) {
	if ( current_user_can( 'manage_categories' ) && isset( $_POST['hsv_volgorde'] ) ) { // phpcs:ignore
		update_term_meta( $term_id, 'hsv_volgorde', absint( $_POST['hsv_volgorde'] ) ); // phpcs:ignore
	}
};
add_action( 'created_hsv_locatie', $hsv_save_loc );
add_action( 'edited_hsv_locatie', $hsv_save_loc );

/* ------------------------------------------------------------------
 * Scripts
 * ---------------------------------------------------------------- */
add_action( 'admin_enqueue_scripts', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}
	if ( array_key_exists( $screen->post_type, hsv_list_types() ) ) {
		wp_enqueue_media();
		wp_enqueue_script( 'hsv-admin', HSV_URL . 'assets/admin.js', array( 'jquery' ), HSV_VERSION, true );
	} elseif ( 'hsv_wedstrijd' === $screen->post_type ) {
		wp_enqueue_script( 'hsv-bezetting', HSV_URL . 'assets/bezetting.js', array( 'jquery' ), HSV_VERSION, true );
	}
} );
