<?php
/**
 * Agenda: lijst of maand, filter op discipline en soort.
 *
 * @package hsv-wedstrijden
 */

get_header();
$s      = hsv_agenda_state();
$keep   = array( 'weergave' => 'maand' === $s['view'] ? 'maand' : '', 'maand' => 'maand' === $s['view'] ? $s['ym'] : '' );
$disc_t = get_terms( array( 'taxonomy' => 'hsv_discipline', 'hide_empty' => true ) );
$sort_t = get_terms( array( 'taxonomy' => 'hsv_soort', 'hide_empty' => true ) );
?>
<div class="hsv hsv-agenda">
	<div class="hsv-wrap">
		<header class="hsv-page-head">
			<div>
				<h1>Agenda</h1>
				<p class="hsv-lead"><?php echo esc_html( apply_filters( 'hsv_agenda_intro', 'Oefen- en KNHS-wedstrijden springen en dressuur, voor paarden en pony\'s.' ) ); ?></p>
			</div>
			<a class="hsv-link-strong" href="<?php echo esc_url( hsv_ical_url() ); ?>">+ zet de agenda in je telefoon</a>
		</header>

		<div class="hsv-toolbar">
			<nav class="hsv-filters" aria-label="Filter">
				<a class="hsv-chip<?php echo ! $s['disc'] && ! $s['soort'] ? ' is-active' : ''; ?>" href="<?php echo hsv_agenda_url( $keep ); // phpcs:ignore ?>">Alles</a>
				<?php foreach ( is_wp_error( $disc_t ) ? array() : $disc_t as $t ) : ?>
					<a class="hsv-chip<?php echo $s['disc'] === $t->slug ? ' is-active' : ''; ?>" href="<?php echo hsv_agenda_url( array_merge( $keep, array( 'discipline' => $t->slug, 'soort' => $s['soort'] ) ) ); // phpcs:ignore ?>"><?php echo esc_html( $t->name ); ?></a>
				<?php endforeach; ?>
				<?php if ( ! is_wp_error( $sort_t ) && count( $sort_t ) > 1 ) : ?>
					<span class="hsv-filter-sep" aria-hidden="true"></span>
					<?php foreach ( $sort_t as $t ) : ?>
						<a class="hsv-chip<?php echo $s['soort'] === $t->slug ? ' is-active' : ''; ?>" href="<?php echo hsv_agenda_url( array_merge( $keep, array( 'soort' => $s['soort'] === $t->slug ? '' : $t->slug, 'discipline' => $s['disc'] ) ) ); // phpcs:ignore ?>"><?php echo esc_html( $t->name ); ?></a>
					<?php endforeach; ?>
				<?php endif; ?>
			</nav>
			<div class="hsv-switch" role="group" aria-label="Weergave">
				<a class="<?php echo 'lijst' === $s['view'] ? 'is-active' : ''; ?>" href="<?php echo hsv_agenda_url( array( 'discipline' => $s['disc'], 'soort' => $s['soort'] ) ); // phpcs:ignore ?>">Lijst</a>
				<a class="<?php echo 'maand' === $s['view'] ? 'is-active' : ''; ?>" href="<?php echo hsv_agenda_url( array( 'weergave' => 'maand', 'discipline' => $s['disc'], 'soort' => $s['soort'] ) ); // phpcs:ignore ?>">Maand</a>
			</div>
		</div>

		<?php if ( 'maand' === $s['view'] ) : ?>
			<?php hsv_template( 'parts/maand.php', $s ); ?>
		<?php else : ?>
			<?php $hsv_items = hsv_get_wedstrijden( array( 'discipline' => $s['disc'], 'soort' => $s['soort'] ) ); ?>
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
				<p class="hsv-empty">Er staan geen wedstrijden gepland<?php echo $s['disc'] || $s['soort'] ? ' voor dit filter' : ''; ?>.</p>
			<?php endif; ?>
		<?php endif; ?>

		<p class="hsv-more"><a class="hsv-link-strong" href="<?php echo esc_url( get_post_type_archive_link( 'hsv_uitslag' ) ); ?>">Afgelopen wedstrijden en uitslagen →</a></p>
	</div>
</div>
<?php
get_footer();
