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
	add_meta_box( 'hsv_wedstrijd_data', 'Gegevens wedstrijd', 'hsv_metabox_wedstrijd', 'hsv_wedstrijd', 'normal', 'high' );
	add_meta_box( 'hsv_wedstrijd_lists', 'Startlijsten & uitslagen', 'hsv_metabox_wedstrijd_lists', 'hsv_wedstrijd', 'side', 'high' );
	foreach ( hsv_list_types() as $type => $l ) {
		add_meta_box( 'hsv_list_data', $l['single'], 'hsv_metabox_list', $type, 'normal', 'high' );
	}
} );

function hsv_metabox_wedstrijd( $post ) {
	wp_nonce_field( 'hsv_save', 'hsv_nonce' );
	echo '<table class="form-table hsv-form"><tbody>';
	foreach ( hsv_wedstrijd_fields() as $key => $f ) {
		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s%3$s</label></th><td><input type="%4$s" id="%1$s" name="%1$s" value="%5$s" class="regular-text" placeholder="%6$s"%7$s>%8$s</td></tr>',
			esc_attr( $key ),
			esc_html( $f['label'] ),
			! empty( $f['required'] ) ? ' <span style="color:#b32d2e">*</span>' : '',
			esc_attr( $f['type'] ),
			esc_attr( get_post_meta( $post->ID, $key, true ) ),
			esc_attr( isset( $f['placeholder'] ) ? $f['placeholder'] : '' ),
			! empty( $f['required'] ) ? ' required' : '',
			! empty( $f['help'] ) ? '<p class="description">' . esc_html( $f['help'] ) . '</p>' : ''
		);
	}
	echo '</tbody></table>';
	echo '<p class="description">Flyer: gebruik de uitgelichte afbeelding. Startlijsten en uitslagen voeg je toe via het blok rechts.</p>';
}

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
		echo esc_html( hsv_date_label( hsv_wedstrijd( $post_id ), false ) );
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

/* ------------------------------------------------------------------
 * Scripts
 * ---------------------------------------------------------------- */
add_action( 'admin_enqueue_scripts', function () {
	$screen = get_current_screen();
	if ( ! $screen || ! array_key_exists( $screen->post_type, hsv_list_types() ) || 'post' !== $screen->base ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'hsv-admin', HSV_URL . 'assets/admin.js', array( 'jquery' ), HSV_VERSION, true );
} );
