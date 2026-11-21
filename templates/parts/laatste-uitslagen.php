<?php
/**
 * Laatste wedstrijden met een uitslag. $args['limit'].
 *
 * @package hsv-wedstrijden
 */

$hsv_items = hsv_get_wedstrijden( array( 'when' => 'all', 'with_uitslagen' => true, 'limit' => 50 ) );
usort( $hsv_items, function ( $a, $b ) {
	return strcmp( get_post_meta( $b->ID, '_hsv_start', true ), get_post_meta( $a->ID, '_hsv_start', true ) );
} );
$hsv_items = array_slice( $hsv_items, 0, isset( $args['limit'] ) ? (int) $args['limit'] : 3 );
if ( ! $hsv_items ) {
	echo '<p class="hsv-empty">Nog geen uitslagen.</p>';
	return;
}
?>
<ul class="hsv-latest">
	<?php foreach ( $hsv_items as $p ) : $e = hsv_wedstrijd( $p ); ?>
		<li>
			<a href="<?php echo esc_url( $e['url'] ); ?>#uitslagen"><?php echo esc_html( $e['title'] ); ?></a>
			<span><?php echo esc_html( hsv_date_label( $e, false ) ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
