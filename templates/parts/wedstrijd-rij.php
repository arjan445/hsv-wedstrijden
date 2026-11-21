<?php
/**
 * Eén wedstrijd in een lijst. $args['post'].
 *
 * @package hsv-wedstrijden
 */

$e = hsv_wedstrijd( $args['post'] );
if ( ! $e ) {
	return;
}
$has_results = (bool) hsv_get_lists( $e['id'], 'hsv_uitslag' );
$has_start   = (bool) hsv_get_lists( $e['id'], 'hsv_startlijst' );
$meta        = array_filter( array( $e['voor'], $e['time'], $e['location'] ) );
?>
<article class="hsv-row<?php echo $e['past'] ? ' is-past' : ''; ?>">
	<a class="hsv-date" href="<?php echo esc_url( $e['url'] ); ?>" style="background:<?php echo esc_attr( $e['color'] ); ?>;color:<?php echo esc_attr( hsv_contrast( $e['color'] ) ); ?>" tabindex="-1" aria-hidden="true">
		<span class="hsv-date-d<?php echo strlen( hsv_day_label( $e ) ) > 3 ? ' is-range' : ''; ?>"><?php echo esc_html( hsv_day_label( $e ) ); ?></span>
		<span class="hsv-date-m"><?php echo esc_html( hsv_date( $e['start'], 'M' ) ); ?></span>
	</a>
	<div class="hsv-row-body">
		<?php if ( $e['kind'] ) : ?><div class="hsv-kind"><?php echo esc_html( $e['kind'] ); ?></div><?php endif; ?>
		<h3 class="hsv-row-title"><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
		<div class="hsv-row-meta">
			<?php echo esc_html( hsv_date_label( $e ) ); ?>
			<?php if ( $meta ) : ?> · <?php echo esc_html( implode( ' · ', $meta ) ); ?><?php endif; ?>
		</div>
		<?php if ( $e['now'] ) : ?><div class="hsv-status is-now">Nu bezig</div><?php elseif ( $e['open'] ) : ?><div class="hsv-status is-open">Inschrijving open<?php echo $e['deadline'] ? ' tot ' . esc_html( hsv_date( $e['deadline'], 'j F' ) ) : ''; ?></div><?php elseif ( ! $e['past'] && $e['closed'] ) : ?><div class="hsv-status">Inschrijving gesloten</div><?php endif; ?>
	</div>
	<div class="hsv-row-actions">
		<?php if ( $e['now'] && $e['live'] ) : ?>
			<a class="hsv-btn is-live" href="<?php echo esc_url( $e['live'] ); ?>" target="_blank" rel="noopener">Live uitslagen</a>
		<?php endif; ?>
		<?php if ( $has_results ) : ?>
			<a class="hsv-btn is-ghost" href="<?php echo esc_url( $e['url'] ); ?>#uitslagen">Uitslagen</a>
		<?php elseif ( $has_start ) : ?>
			<a class="hsv-btn is-ghost" href="<?php echo esc_url( $e['url'] ); ?>#startlijsten">Startlijsten</a>
		<?php endif; ?>
		<?php if ( $e['open'] ) : ?>
			<a class="hsv-btn" href="<?php echo esc_url( $e['url'] ); ?>#inschrijven">Inschrijven</a>
		<?php endif; ?>
	</div>
</article>
