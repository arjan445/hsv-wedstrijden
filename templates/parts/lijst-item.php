<?php
/**
 * Eén startlijst of uitslag: titel, knoppen, tekst en afbeeldingen. $args['item'] uit hsv_list_item().
 *
 * @package hsv-wedstrijden
 */

$i      = $args['item'];
$labels = array( 'pdf' => 'PDF', 'xlsx' => 'Excel', 'xls' => 'Excel', 'csv' => 'CSV', 'docx' => 'Word', 'doc' => 'Word' );
$host   = $i['link'] ? preg_replace( '/^www\./', '', (string) wp_parse_url( $i['link'], PHP_URL_HOST ) ) : '';
?>
<article class="hsv-doc">
	<div class="hsv-doc-head">
		<?php if ( $i['title'] ) : ?><h3 class="hsv-doc-title"><?php echo esc_html( $i['title'] ); ?></h3><?php endif; ?>
		<div class="hsv-doc-actions">
			<?php if ( $i['file_url'] ) : ?>
				<a class="hsv-btn" href="<?php echo esc_url( $i['file_url'] ); ?>" target="_blank" rel="noopener">Download<?php echo isset( $labels[ $i['file_ext'] ] ) ? ' ' . esc_html( $labels[ $i['file_ext'] ] ) : ''; ?></a>
			<?php endif; ?>
			<?php if ( $i['link'] ) : ?>
				<a class="hsv-btn is-ghost" href="<?php echo esc_url( $i['link'] ); ?>" target="_blank" rel="noopener">Bekijk op <?php echo esc_html( $host ? $host : 'externe site' ); ?> ↗</a>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( $i['content'] ) : ?>
		<div class="hsv-doc-content"><?php echo apply_filters( 'the_content', $i['content'] ); // phpcs:ignore ?></div>
	<?php endif; ?>
	<?php if ( $i['images'] ) : ?>
		<div class="hsv-gallery">
			<?php foreach ( $i['images'] as $img ) : ?>
				<a href="<?php echo esc_url( wp_get_attachment_image_url( $img, 'full' ) ); ?>" target="_blank" rel="noopener">
					<?php echo wp_get_attachment_image( $img, 'medium_large', false, array( 'loading' => 'lazy' ) ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</article>
