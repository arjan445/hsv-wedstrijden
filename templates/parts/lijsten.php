<?php
/**
 * Startlijsten of uitslagen van een wedstrijd.
 * $args: wedstrijd (ID), type (hsv_startlijst|hsv_uitslag), heading (bool, standaard true).
 *
 * @package hsv-wedstrijden
 */

$type  = $args['type'];
$items = hsv_get_lists( $args['wedstrijd'], $type );
if ( ! $items ) {
	return;
}
$types = hsv_list_types();
$id    = 'hsv_uitslag' === $type ? 'uitslagen' : 'startlijsten';
?>
<section class="hsv-lists" id="<?php echo esc_attr( $id ); ?>">
	<?php if ( ! isset( $args['heading'] ) || $args['heading'] ) : ?>
		<h2><?php echo esc_html( $types[ $type ]['plural'] ); ?></h2>
	<?php endif; ?>
	<?php
	foreach ( $items as $item ) {
		hsv_template( 'parts/lijst-item.php', array( 'item' => hsv_list_item( $item ), 'single' => count( $items ) === 1 ) );
	}
	?>
</section>
