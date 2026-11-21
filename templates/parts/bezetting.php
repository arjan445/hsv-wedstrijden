<?php
/**
 * Bezetting per locatie, per week. $args uit hsv_agenda_state() (week = maandag, Y-m-d).
 * Interne items tonen we als "Bezet" (titel alleen zichtbaar voor beheerders).
 *
 * @package hsv-wedstrijden
 */

$monday = $args['week'];
$days   = array();
for ( $i = 0; $i < 7; $i++ ) {
	$days[] = gmdate( 'Y-m-d', strtotime( $monday . ' 12:00:00 +' . $i . ' days' ) );
}
$sunday  = $days[6];
$prev    = gmdate( 'Y-m-d', strtotime( $monday . ' 12:00:00 -7 days' ) );
$next    = gmdate( 'Y-m-d', strtotime( $monday . ' 12:00:00 +7 days' ) );
$today   = hsv_today();
$locs    = hsv_get_locaties();
$see_all = current_user_can( 'edit_posts' );

/* Items per locatie per dag. */
$grid = array();
foreach ( hsv_get_range( $monday, $sunday ) as $p ) {
	$e = hsv_wedstrijd( $p );
	foreach ( $e['locaties'] as $l ) {
		foreach ( $days as $d ) {
			if ( $d >= $e['start'] && $d <= $e['end'] ) {
				$grid[ $l->term_id ][ $d ][] = $e;
			}
		}
	}
}
foreach ( $grid as &$by_day ) {
	foreach ( $by_day as &$list ) {
		usort( $list, function ( $a, $b ) {
			return strcmp( $a['time_start'], $b['time_start'] );
		} );
	}
}
unset( $by_day, $list );

$label = function ( $e ) use ( $see_all ) {
	return ( $e['intern'] && ! $see_all ) ? 'Bezet' : $e['title'];
};
$week_label = hsv_date( $monday, 'j M' ) . ' – ' . hsv_date( $sunday, 'j M Y' );
?>
<div class="hsv-occ">
	<div class="hsv-month-nav">
		<a class="hsv-icon-btn" href="<?php echo hsv_agenda_url( array( 'weergave' => 'bezetting', 'week' => $prev ) ); // phpcs:ignore ?>" aria-label="Vorige week">‹</a>
		<h2>Week <?php echo esc_html( gmdate( 'W', strtotime( $monday . ' 12:00:00' ) ) ); ?> <small><?php echo esc_html( $week_label ); ?></small></h2>
		<a class="hsv-icon-btn" href="<?php echo hsv_agenda_url( array( 'weergave' => 'bezetting', 'week' => $next ) ); // phpcs:ignore ?>" aria-label="Volgende week">›</a>
	</div>

	<?php if ( ! $locs ) : ?>
		<p class="hsv-empty">Er zijn nog geen locaties ingesteld.</p>
	<?php else : ?>
		<div class="hsv-occ-wrap">
			<table class="hsv-occ-table">
				<thead>
					<tr>
						<th scope="col" class="hsv-occ-corner"><span class="screen-reader-text">Locatie</span></th>
						<?php foreach ( $days as $d ) : ?>
							<th scope="col" class="<?php echo $d === $today ? 'is-today' : ''; ?>">
								<span class="hsv-occ-wd"><?php echo esc_html( hsv_date( $d, 'D' ) ); ?></span>
								<span class="hsv-occ-d"><?php echo esc_html( hsv_date( $d, 'j M' ) ); ?></span>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $locs as $l ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $l->name ); ?></th>
							<?php foreach ( $days as $d ) : ?>
								<td class="<?php echo $d === $today ? 'is-today' : ''; ?><?php echo $d < $today ? ' is-past' : ''; ?>">
									<?php if ( empty( $grid[ $l->term_id ][ $d ] ) ) : ?>
										<span class="hsv-free">vrij</span>
									<?php else : ?>
										<?php foreach ( $grid[ $l->term_id ][ $d ] as $e ) : ?>
											<?php $tag = ( $e['intern'] && ! $see_all ) ? 'span' : 'a'; ?>
											<<?php echo $tag; // phpcs:ignore ?> class="hsv-busy"<?php echo 'a' === $tag ? ' href="' . esc_url( $e['url'] ) . '"' : ''; ?> style="border-left-color:<?php echo esc_attr( $e['color'] ); ?>">
												<span class="hsv-busy-time"><?php echo esc_html( $e['all_day'] ? 'hele dag' : $e['time_start'] . ( $e['time_end'] ? '–' . $e['time_end'] : '' ) ); ?></span>
												<span class="hsv-busy-title"><?php echo esc_html( $label( $e ) ); ?></span>
											</<?php echo $tag; // phpcs:ignore ?>>
										<?php endforeach; ?>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php /* Mobiel: per dag een lijstje */ ?>
		<div class="hsv-occ-days">
			<?php foreach ( $days as $d ) : ?>
				<section class="hsv-occ-day<?php echo $d === $today ? ' is-today' : ''; ?>">
					<h3><?php echo esc_html( ucfirst( hsv_date( $d, 'l j F' ) ) ); ?></h3>
					<ul>
						<?php foreach ( $locs as $l ) : ?>
							<li>
								<span class="hsv-occ-loc"><?php echo esc_html( $l->name ); ?></span>
								<?php if ( empty( $grid[ $l->term_id ][ $d ] ) ) : ?>
									<span class="hsv-free">vrij</span>
								<?php else : ?>
									<span class="hsv-occ-items">
										<?php foreach ( $grid[ $l->term_id ][ $d ] as $e ) : ?>
											<span class="hsv-busy" style="border-left-color:<?php echo esc_attr( $e['color'] ); ?>"><span class="hsv-busy-time"><?php echo esc_html( $e['all_day'] ? 'hele dag' : $e['time_start'] . ( $e['time_end'] ? '–' . $e['time_end'] : '' ) ); ?></span> <?php echo esc_html( $label( $e ) ); ?></span>
										<?php endforeach; ?>
									</span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
