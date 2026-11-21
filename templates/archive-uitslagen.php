<?php
/**
 * /uitslagen/: alle wedstrijden met uitslagen, per jaar.
 *
 * @package hsv-wedstrijden
 */

get_header();

global $wpdb;
$ids   = hsv_wedstrijd_ids_with( 'hsv_uitslag' );
$years = array();
if ( $ids ) {
	$in    = implode( ',', array_map( 'intval', $ids ) );
	$years = $wpdb->get_col( "SELECT DISTINCT SUBSTR(meta_value,1,4) y FROM {$wpdb->postmeta} WHERE meta_key = '_hsv_start' AND post_id IN ($in) ORDER BY y DESC" ); // phpcs:ignore
}
$year = isset( $_GET['jaar'] ) ? preg_replace( '/\D/', '', wp_unslash( $_GET['jaar'] ) ) : ''; // phpcs:ignore
if ( ! in_array( $year, $years, true ) ) {
	$year = $years ? $years[0] : '';
}
$hsv_items = $year ? hsv_get_wedstrijden( array( 'when' => 'all', 'year' => $year, 'with_uitslagen' => true ) ) : array();
usort( $hsv_items, function ( $a, $b ) {
	return strcmp( get_post_meta( $b->ID, '_hsv_start', true ), get_post_meta( $a->ID, '_hsv_start', true ) );
} );
?>
<div class="hsv hsv-results">
	<div class="hsv-wrap">
		<header class="hsv-page-head">
			<div>
				<h1>Uitslagen</h1>
				<p class="hsv-lead">Uitslagen van wedstrijden bij onze vereniging.</p>
			</div>
			<a class="hsv-link-strong" href="<?php echo esc_url( get_post_type_archive_link( 'hsv_wedstrijd' ) ); ?>">naar de agenda →</a>
		</header>

		<?php if ( count( $years ) > 1 ) : ?>
			<nav class="hsv-filters hsv-toolbar" aria-label="Jaar">
				<?php foreach ( $years as $y ) : ?>
					<a class="hsv-chip<?php echo $y === $year ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'jaar', $y, get_post_type_archive_link( 'hsv_uitslag' ) ) ); ?>"><?php echo esc_html( $y ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( $hsv_items ) : ?>
			<?php foreach ( $hsv_items as $p ) : $e = hsv_wedstrijd( $p ); ?>
				<section class="hsv-result-group">
					<div class="hsv-result-head">
						<div class="hsv-date" style="background:<?php echo esc_attr( $e['color'] ); ?>;color:<?php echo esc_attr( hsv_contrast( $e['color'] ) ); ?>">
							<span class="hsv-date-d<?php echo strlen( hsv_day_label( $e ) ) > 3 ? ' is-range' : ''; ?>"><?php echo esc_html( hsv_day_label( $e ) ); ?></span>
							<span class="hsv-date-m"><?php echo esc_html( hsv_date( $e['start'], 'M' ) ); ?></span>
						</div>
						<div>
							<?php if ( $e['kind'] ) : ?><div class="hsv-kind"><?php echo esc_html( $e['kind'] ); ?></div><?php endif; ?>
							<h2 class="hsv-row-title"><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h2>
							<div class="hsv-row-meta"><?php echo esc_html( hsv_date_label( $e ) ); ?></div>
						</div>
					</div>
					<ul class="hsv-result-items">
						<?php foreach ( hsv_get_lists( $e['id'], 'hsv_uitslag' ) as $u ) : $i = hsv_list_item( $u ); ?>
							<li>
								<a href="<?php echo esc_url( $i['url'] ); ?>"><?php echo esc_html( $i['title'] ); ?></a>
								<span class="hsv-tags">
									<?php echo $i['file_url'] ? '<span>' . esc_html( strtoupper( $i['file_ext'] ) ) . '</span>' : ''; ?>
									<?php echo $i['link'] ? '<span>link</span>' : ''; ?>
									<?php echo $i['images'] ? '<span>' . count( $i['images'] ) . ' foto' . ( count( $i['images'] ) > 1 ? "'s" : '' ) . '</span>' : ''; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="hsv-empty">Er zijn nog geen uitslagen gepubliceerd.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
