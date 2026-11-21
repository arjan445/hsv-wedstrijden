<?php
/**
 * Maandweergave. $args uit hsv_agenda_state().
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
$keep   = array( 'weergave' => 'maand', 'discipline' => $args['disc'], 'soort' => $args['soort'] );

$by_day = array();
$all    = array();
foreach ( hsv_get_month_wedstrijden( $ym, $args['disc'], $args['soort'] ) as $p ) {
	$e = hsv_wedstrijd( $p );
	if ( ! $e['start'] ) {
		continue;
	}
	$all[] = $p;
	for ( $d = strtotime( $e['start'] . ' 12:00:00' ); $d <= strtotime( $e['end'] . ' 12:00:00' ); $d += DAY_IN_SECONDS ) {
		$key = gmdate( 'Y-m-d', $d );
		if ( substr( $key, 0, 7 ) === $ym ) {
			$by_day[ $key ][] = $e;
		}
	}
}
$cells = (int) ceil( ( $offset + $days ) / 7 ) * 7;
?>
<div class="hsv-month">
	<div class="hsv-month-nav">
		<a class="hsv-icon-btn" href="<?php echo hsv_agenda_url( array_merge( $keep, array( 'maand' => $prev ) ) ); // phpcs:ignore ?>" aria-label="Vorige maand">‹</a>
		<h2><?php echo esc_html( ucfirst( wp_date( 'F Y', $first, wp_timezone() ) ) ); ?></h2>
		<a class="hsv-icon-btn" href="<?php echo hsv_agenda_url( array_merge( $keep, array( 'maand' => $next ) ) ); // phpcs:ignore ?>" aria-label="Volgende maand">›</a>
	</div>
	<div class="hsv-grid-wrap">
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
				$key = $ym . '-' . str_pad( (string) $day, 2, '0', STR_PAD_LEFT );
				$cls = 'hsv-cell' . ( $key === $today ? ' is-today' : '' ) . ( $key < $today ? ' is-past' : '' ) . ( ! empty( $by_day[ $key ] ) ? ' has-events' : '' );
				?>
				<div class="<?php echo esc_attr( $cls ); ?>">
					<span class="hsv-cell-day"><?php echo (int) $day; ?></span>
					<?php foreach ( isset( $by_day[ $key ] ) ? $by_day[ $key ] : array() as $e ) : ?>
						<a class="hsv-cell-event" href="<?php echo esc_url( $e['url'] ); ?>" style="background:<?php echo esc_attr( $e['color'] ); ?>;color:<?php echo esc_attr( hsv_contrast( $e['color'] ) ); ?>"><?php echo esc_html( $e['title'] ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endfor; ?>
		</div>
	</div>

	<div class="hsv-month-list">
		<?php if ( $all ) : ?>
			<div class="hsv-list">
				<?php foreach ( $all as $p ) { hsv_template( 'parts/wedstrijd-rij.php', array( 'post' => $p ) ); } ?>
			</div>
		<?php else : ?>
			<p class="hsv-empty">Geen wedstrijden in deze maand.</p>
		<?php endif; ?>
	</div>
</div>
