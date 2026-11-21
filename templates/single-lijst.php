<?php
/**
 * Losse startlijst of uitslag (deelbare link).
 *
 * @package hsv-wedstrijden
 */

get_header();
while ( have_posts() ) :
	the_post();
	$i     = hsv_list_item( get_post() );
	$e     = $i['wedstrijd'] ? hsv_wedstrijd( $i['wedstrijd'] ) : null;
	$types = hsv_list_types();
	?>
	<div class="hsv hsv-single">
		<div class="hsv-wrap is-narrow">
			<?php if ( $e ) : ?>
				<a class="hsv-back" href="<?php echo esc_url( $e['url'] ); ?>">‹ <?php echo esc_html( $e['title'] ); ?></a>
			<?php endif; ?>
			<header class="hsv-single-head is-simple">
				<div>
					<div class="hsv-kind"><?php echo esc_html( $types[ get_post_type() ]['single'] ); ?><?php echo $e ? ' · ' . esc_html( hsv_date_label( $e, false ) ) : ''; ?></div>
					<h1><?php the_title(); ?></h1>
				</div>
			</header>
			<?php
			$i['title'] = '';
			hsv_template( 'parts/lijst-item.php', array( 'item' => $i ) );
			if ( $e ) {
				$others = array_filter( hsv_get_lists( $e['id'], get_post_type() ), function ( $p ) {
					return get_the_ID() !== $p->ID;
				} );
				if ( $others ) {
					echo '<h2 class="hsv-subhead">Meer ' . esc_html( strtolower( $types[ get_post_type() ]['plural'] ) ) . ' van deze wedstrijd</h2><ul class="hsv-result-items">';
					foreach ( $others as $o ) {
						printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $o ) ), esc_html( get_the_title( $o ) ) );
					}
					echo '</ul>';
				}
			}
			?>
		</div>
	</div>
	<?php
endwhile;
get_footer();
