<?php
/**
 * Maandweergave. $args uit hsv_agenda_state().
 * Activiteiten staan als blokjes met titel in de dagvakken (ook op mobiel).
 * Tik op een dag: de lijst onder de kalender toont alleen die dag.
 *
 * @package hsv-wedstrijden
 */

$ym     = $args['ym'];
$first  = strtotime( $ym . '-01 12:00:00' );
$days   = (int) gmdate( 't', $first );
$offset = (int) gmdate( 'N', $first ) - 1;
$prev   = gmdate( 'Y-m', strtotime( '-1 month', $first ) );
$next   = gmdate( 'Y-m', strtotime( '+1 month', $first ) );
$today  = hsv_today();
$keep   = array( 'weergave' => 'maand', 'discipline' => $args['disc'], 'soort' => $args['soort'], 'locatie' => $args['locatie'] );

$by_day = array();
$all    = array();
foreach ( hsv_get_month_wedstrijden( $ym, $args['disc'], $args['soort'], $args['locatie'] ) as $p ) {
	$e = hsv_wedstrijd( $p );
	if ( ! $e['start'] ) {
		continue;
	}
	$e['days'] = array();
	for ( $d = strtotime( $e['start'] . ' 12:00:00' ); $d <= strtotime( $e['end'] . ' 12:00:00' ); $d += DAY_IN_SECONDS ) {
		$key = gmdate( 'Y-m-d', $d );
		if ( substr( $key, 0, 7 ) === $ym ) {
			$e['days'][]      = $key;
			$by_day[ $key ][] = $e;
		}
	}
	$all[] = array( 'post' => $p, 'days' => $e['days'] );
}
foreach ( $by_day as &$list ) {
	usort( $list, function ( $a, $b ) {
		return strcmp( $a['time_start'], $b['time_start'] );
	} );
}
unset( $list );
$cells = (int) ceil( ( $offset + $days ) / 7 ) * 7;
?>
<div class="hsv-month" data-hsv-month>
	<div class="hsv-month-nav">
		<a class="hsv-icon-btn" href="<?php echo hsv_agenda_url( array_merge( $keep, array( 'maand' => $prev ) ) ); // phpcs:ignore ?>" aria-label="Vorige maand">‹</a>
		<h2><?php echo esc_html( ucfirst( wp_date( 'F Y', $first, wp_timezone() ) ) ); ?></h2>
		<a class="hsv-icon-btn" href="<?php echo hsv_agenda_url( array_merge( $keep, array( 'maand' => $next ) ) ); // phpcs:ignore ?>" aria-label="Volgende maand">›</a>
	</div>
	<?php if ( substr( $today, 0, 7 ) !== $ym ) : ?>
		<p class="hsv-today-link"><a href="<?php echo hsv_agenda_url( array_merge( $keep, array( 'maand' => '' ) ) ); // phpcs:ignore ?>">naar vandaag</a></p>
	<?php endif; ?>

	<div class="hsv-grid">
		<?php foreach ( array( 'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo' ) as $wd ) : ?>
			<div class="hsv-grid-head"><?php echo esc_html( $wd ); ?></div>
		<?php endforeach; ?>
		<?php
		for ( $i = 0; $i < $cells; $i++ ) :
			$day = $i - $offset + 1;
			if ( $day < 1 || $day > $days ) {
				echo '<div class="hsv-cell is-empty"></div>';
				continue;
			}
			$key   = $ym . '-' . str_pad( (string) $day, 2, '0', STR_PAD_LEFT );
			$items = isset( $by_day[ $key ] ) ? $by_day[ $key ] : array();
			$cls   = 'hsv-cell' . ( $key === $today ? ' is-today' : '' ) . ( $key < $today ? ' is-past' : '' ) . ( $items ? ' has-events' : '' );
			$label = ucfirst( hsv_date( $key, 'l j F' ) ) . ( $items ? ': ' . count( $items ) . ( 1 === count( $items ) ? ' activiteit' : ' activiteiten' ) : '' );
			?>
			<div class="<?php echo esc_attr( $cls ); ?>" data-day="<?php echo esc_attr( $key ); ?>">
				<button type="button" class="hsv-cell-day" data-day="<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"<?php echo $items ? '' : ' disabled'; ?>><?php echo (int) $day; ?></button>
				<?php foreach ( $items as $e ) : ?>
					<a class="hsv-cell-event" href="<?php echo esc_url( $e['url'] ); ?>" style="background:<?php echo esc_attr( $e['color'] ); ?>;color:<?php echo esc_attr( hsv_contrast( $e['color'] ) ); ?>" title="<?php echo esc_attr( $e['title'] . ( $e['time_label'] ? ' · ' . $e['time_label'] : '' ) ); ?>">
						<?php if ( ! $e['all_day'] ) : ?><span class="hsv-cell-time"><?php echo esc_html( $e['time_start'] ); ?></span><?php endif; ?>
						<span class="hsv-cell-title"><?php echo esc_html( $e['title'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endfor; ?>
	</div>

	<div class="hsv-month-list">
		<div class="hsv-day-head" hidden>
			<h3 class="hsv-day-title"></h3>
			<button type="button" class="hsv-day-reset">hele maand</button>
		</div>
		<?php if ( $all ) : ?>
			<div class="hsv-list">
				<?php foreach ( $all as $x ) : ?>
					<div class="hsv-day-item" data-days="<?php echo esc_attr( implode( ' ', $x['days'] ) ); ?>">
						<?php hsv_template( 'parts/wedstrijd-rij.php', array( 'post' => $x['post'] ) ); ?>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="hsv-empty hsv-day-empty" hidden>Niets gepland op deze dag.</p>
		<?php else : ?>
			<p class="hsv-empty">Er staat niets gepland in deze maand.</p>
		<?php endif; ?>
	</div>
</div>
