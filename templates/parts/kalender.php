<?php
/**
 * Maandweergave met FullCalendar. $args uit hsv_agenda_state().
 * Zonder JavaScript valt hij terug op de HTML-maandweergave (parts/maand.php).
 *
 * @package hsv-wedstrijden
 */

$config = array(
	'events'      => rest_url( 'hsv/v1/agenda' ),
	'initialDate' => $args['ym'] . '-01',
	'filters'     => array_filter( array( 'discipline' => $args['disc'], 'soort' => $args['soort'], 'locatie' => $args['locatie'] ) ),
	'agendaUrl'   => get_post_type_archive_link( 'hsv_wedstrijd' ),
);
?>
<div class="hsv-fc" data-hsv-fc="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
	<div class="hsv-fc-calendar" aria-label="Maandkalender"></div>
	<section class="hsv-fc-day" hidden aria-live="polite">
		<div class="hsv-day-head">
			<h3 class="hsv-day-title"></h3>
			<button type="button" class="hsv-day-reset">sluiten</button>
		</div>
		<div class="hsv-fc-day-list"></div>
	</section>
</div>
<noscript>
	<?php hsv_template( 'parts/maand.php', $args ); ?>
</noscript>
