<?php
/**
 * Agenda: lijst, maand of bezetting; filter op discipline, soort en locatie.
 *
 * @package hsv-wedstrijden
 */

get_header();
$s      = hsv_agenda_state();
$keep   = array(
	'weergave' => $s['view'],
	'maand'    => 'maand' === $s['view'] ? $s['ym'] : '',
);
$filter = array( 'discipline' => $s['disc'], 'soort' => $s['soort'], 'locatie' => $s['locatie'] );
$disc_t = get_terms( array( 'taxonomy' => 'hsv_discipline', 'hide_empty' => true ) );
$sort_t = get_terms( array( 'taxonomy' => 'hsv_soort', 'hide_empty' => true ) );
$loc_t  = hsv_get_locaties( true );

/* Link die één filter aan/uit zet en de rest bewaart. */
$toggle = function ( $key, $value ) use ( $keep, $filter ) {
	$f         = $filter;
	$f[ $key ] = ( $filter[ $key ] === $value ) ? '' : $value;
	return hsv_agenda_url( array_merge( $keep, $f ) );
};
?>
<div class="hsv hsv-agenda">
	<div class="hsv-wrap">
		<header class="hsv-page-head">
			<div>
				<h1>Agenda</h1>
				<p class="hsv-lead"><?php echo esc_html( apply_filters( 'hsv_agenda_intro', 'Wedstrijden, trainingen en activiteiten op onze accommodatie.' ) ); ?></p>
			</div>
			<a class="hsv-link-strong" href="<?php echo esc_url( hsv_ical_url() ); ?>">+ zet de agenda in je telefoon</a>
		</header>

		<div class="hsv-toolbar">
			<?php if ( 'bezetting' !== $s['view'] ) : ?>
				<nav class="hsv-filters" aria-label="Filter">
					<a class="hsv-chip<?php echo ! array_filter( $filter ) ? ' is-active' : ''; ?>" href="<?php echo hsv_agenda_url( $keep ); // phpcs:ignore ?>">Alles</a>
					<?php foreach ( is_wp_error( $disc_t ) ? array() : $disc_t as $t ) : ?>
						<a class="hsv-chip<?php echo $s['disc'] === $t->slug ? ' is-active' : ''; ?>" href="<?php echo $toggle( 'discipline', $t->slug ); // phpcs:ignore ?>"><?php echo esc_html( $t->name ); ?></a>
					<?php endforeach; ?>
					<?php if ( ! is_wp_error( $sort_t ) && count( $sort_t ) > 1 ) : ?>
						<span class="hsv-filter-sep" aria-hidden="true"></span>
						<?php foreach ( $sort_t as $t ) : ?>
							<a class="hsv-chip<?php echo $s['soort'] === $t->slug ? ' is-active' : ''; ?>" href="<?php echo $toggle( 'soort', $t->slug ); // phpcs:ignore ?>"><?php echo esc_html( $t->name ); ?></a>
						<?php endforeach; ?>
					<?php endif; ?>
					<?php if ( count( $loc_t ) > 1 ) : ?>
						<span class="hsv-filter-sep" aria-hidden="true"></span>
						<?php foreach ( $loc_t as $t ) : ?>
							<a class="hsv-chip is-loc<?php echo $s['locatie'] === $t->slug ? ' is-active' : ''; ?>" href="<?php echo $toggle( 'locatie', $t->slug ); // phpcs:ignore ?>"><?php echo esc_html( $t->name ); ?></a>
						<?php endforeach; ?>
					<?php endif; ?>
				</nav>
			<?php else : ?>
				<p class="hsv-lead">Wanneer zijn de hal, het buitenparcours en de kantine bezet?</p>
			<?php endif; ?>
			<div class="hsv-switch" role="group" aria-label="Weergave">
				<a class="<?php echo 'maand' === $s['view'] ? 'is-active' : ''; ?>" href="<?php echo hsv_agenda_url( array_merge( array( 'weergave' => 'maand' ), $filter ) ); // phpcs:ignore ?>">Maand</a>
				<a class="<?php echo 'lijst' === $s['view'] ? 'is-active' : ''; ?>" href="<?php echo hsv_agenda_url( array_merge( array( 'weergave' => 'lijst' ), $filter ) ); // phpcs:ignore ?>">Lijst</a>
				<?php if ( hsv_get_locaties() ) : ?>
					<a class="<?php echo 'bezetting' === $s['view'] ? 'is-active' : ''; ?>" href="<?php echo hsv_agenda_url( array( 'weergave' => 'bezetting' ) ); // phpcs:ignore ?>">Bezetting</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( 'bezetting' === $s['view'] ) : ?>
			<?php hsv_template( 'parts/bezetting.php', $s ); ?>
		<?php elseif ( 'maand' === $s['view'] ) : ?>
			<?php hsv_template( apply_filters( 'hsv_use_fullcalendar', true ) ? 'parts/kalender.php' : 'parts/maand.php', $s ); ?>
		<?php else : ?>
			<?php $hsv_items = hsv_get_wedstrijden( $filter ); ?>
			<?php if ( $hsv_items ) : ?>
				<div class="hsv-list">
					<?php
					$month = '';
					foreach ( $hsv_items as $p ) {
						$m = substr( (string) get_post_meta( $p->ID, '_hsv_start', true ), 0, 7 );
						if ( $m !== $month ) {
							$month = $m;
							echo '<h2 class="hsv-month-label">' . esc_html( ucfirst( hsv_date( $m . '-01', 'F Y' ) ) ) . '</h2>';
						}
						hsv_template( 'parts/wedstrijd-rij.php', array( 'post' => $p ) );
					}
					?>
				</div>
			<?php else : ?>
				<p class="hsv-empty">Er staat niets gepland<?php echo array_filter( $filter ) ? ' voor dit filter' : ''; ?>.</p>
			<?php endif; ?>
		<?php endif; ?>

		<p class="hsv-more"><a class="hsv-link-strong" href="<?php echo esc_url( get_post_type_archive_link( 'hsv_uitslag' ) ); ?>">Afgelopen wedstrijden en uitslagen →</a></p>
	</div>
</div>
<?php
get_footer();
