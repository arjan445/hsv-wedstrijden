<?php
/**
 * Wedstrijdpagina: gegevens, inschrijven, tekst, startlijsten, uitslagen en flyer.
 *
 * @package hsv-wedstrijden
 */

get_header();
while ( have_posts() ) :
	the_post();
	$e        = hsv_wedstrijd();
	$location = $e['location'] ? $e['location'] : apply_filters( 'hsv_default_location', '' );
	$has_any  = hsv_get_lists( $e['id'], 'hsv_startlijst' ) || hsv_get_lists( $e['id'], 'hsv_uitslag' );
	?>
	<div class="hsv hsv-single">
		<div class="hsv-wrap">
			<a class="hsv-back" href="<?php echo esc_url( get_post_type_archive_link( 'hsv_wedstrijd' ) ); ?>">‹ agenda</a>
			<header class="hsv-single-head">
				<div class="hsv-date is-large" style="background:<?php echo esc_attr( $e['color'] ); ?>;color:<?php echo esc_attr( hsv_contrast( $e['color'] ) ); ?>">
					<span class="hsv-date-d<?php echo strlen( hsv_day_label( $e ) ) > 3 ? ' is-range' : ''; ?>"><?php echo esc_html( hsv_day_label( $e ) ); ?></span>
					<span class="hsv-date-m"><?php echo esc_html( hsv_date( $e['start'], 'M' ) ); ?></span>
				</div>
				<div>
					<?php if ( $e['kind'] ) : ?><div class="hsv-kind"><?php echo esc_html( $e['kind'] ); ?></div><?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<?php if ( $e['now'] ) : ?><span class="hsv-status is-now">Nu bezig</span><?php elseif ( $e['past'] ) : ?><span class="hsv-status">Afgelopen</span><?php endif; ?>
				</div>
			</header>

			<div class="hsv-single-grid">
				<div class="hsv-single-main">
					<ul class="hsv-facts">
						<li><span class="hsv-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg></span><?php echo esc_html( ucfirst( hsv_date_label( $e ) ) ); ?><?php echo $e['time'] ? ' · ' . esc_html( $e['time'] ) : ''; ?></li>
						<?php if ( $location ) : ?>
							<li><span class="hsv-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span><a href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo rawurlencode( $location ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $location ); ?></a></li>
						<?php endif; ?>
						<?php if ( $e['voor'] ) : ?>
							<li><span class="hsv-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 11a3 3 0 1 0 0-6M21 20c0-2.6-1.7-4.8-4-5.6"/></svg></span><?php echo esc_html( $e['voor'] ); ?></li>
						<?php endif; ?>
						<?php if ( $e['deadline'] && ! $e['past'] ) : ?>
							<li><span class="hsv-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>Inschrijven tot en met <?php echo esc_html( hsv_date( $e['deadline'], 'j F' ) ); ?></li>
						<?php endif; ?>
					</ul>

					<?php if ( get_the_content() ) : ?>
						<div class="hsv-content"><?php the_content(); ?></div>
					<?php endif; ?>

					<?php
					hsv_template( 'parts/lijsten.php', array( 'wedstrijd' => $e['id'], 'type' => 'hsv_startlijst' ) );
					hsv_template( 'parts/lijsten.php', array( 'wedstrijd' => $e['id'], 'type' => 'hsv_uitslag' ) );
					if ( ! $has_any && ! $e['past'] ) {
						echo '<p class="hsv-note">Startlijsten en uitslagen verschijnen hier zodra ze bekend zijn.</p>';
					}
					?>
				</div>

				<aside class="hsv-single-side">
					<?php ob_start(); ?>
						<?php if ( $e['open'] && $e['signup_url'] ) : ?>
							<a class="hsv-btn is-block" href="<?php echo esc_url( $e['signup_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $e['signup_label'] ? $e['signup_label'] : 'Inschrijven' ); ?> ↗</a>
						<?php elseif ( $e['open'] && $e['signup_email'] ) : ?>
							<a class="hsv-btn is-block" href="mailto:<?php echo esc_attr( antispambot( $e['signup_email'] ) ); ?>?subject=<?php echo rawurlencode( 'Inschrijving ' . $e['title'] ); ?>"><?php echo esc_html( $e['signup_label'] ? $e['signup_label'] : 'Inschrijven per e-mail' ); ?></a>
						<?php elseif ( ! $e['past'] && ! $e['now'] && $e['closed'] ) : ?>
							<span class="hsv-btn is-block is-disabled">Inschrijving gesloten</span>
						<?php endif; ?>
						<?php if ( $e['now'] && $e['live'] ) : ?>
							<a class="hsv-btn is-block is-live" href="<?php echo esc_url( $e['live'] ); ?>" target="_blank" rel="noopener">Live uitslagen ↗</a>
						<?php endif; ?>
						<?php if ( ! $e['past'] ) : ?>
							<a class="hsv-link-strong" href="<?php echo esc_url( hsv_ical_url( $e['id'] ) ); ?>">+ toevoegen aan mijn agenda</a>
						<?php endif; ?>
						<?php do_action( 'hsv_wedstrijd_sidebar', $e ); ?>
					<?php $hsv_box = trim( ob_get_clean() ); ?>
					<?php if ( $hsv_box ) : ?>
						<div class="hsv-box" id="inschrijven"><?php echo $hsv_box; // phpcs:ignore ?></div>
					<?php endif; ?>
					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="hsv-flyer">
							<a href="<?php echo esc_url( get_the_post_thumbnail_url( null, 'full' ) ); ?>" target="_blank" rel="noopener"><?php the_post_thumbnail( 'large' ); ?></a>
							<figcaption>Flyer – klik om te vergroten</figcaption>
						</figure>
					<?php endif; ?>
				</aside>
			</div>
		</div>

		<?php if ( $e['open'] && ( $e['signup_url'] || $e['signup_email'] ) ) : ?>
			<div class="hsv-sticky-cta">
				<div><strong>Inschrijving open</strong><?php echo $e['deadline'] ? '<span>tot ' . esc_html( hsv_date( $e['deadline'], 'j F' ) ) . '</span>' : ''; ?></div>
				<a class="hsv-btn" href="#inschrijven">Inschrijven</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
