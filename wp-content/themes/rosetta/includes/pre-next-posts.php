<?php
// Prev/Next Posts
$thumbs = true;
$prevThumb = '';
$nextThumb = '';

// Hide Thumbnails
if ( get_theme_mod('hide_thumbs') ) { $thumbs = false; }

if ( !get_theme_mod( 'prev_next_posts' ) ) {
	$prev = get_previous_post();
	$next = get_next_post();
	?>

	<div id="fl-prev-next" class="fl-container">
		<?php if ( !empty($prev) ) {
			if ( has_post_thumbnail($prev->ID) && $thumbs == true ) {
	            $prevThumbnail =  wp_get_attachment_image_src( get_post_thumbnail_id( $prev->ID ), 'thumbnail');
	            $prevThumb = $prevThumbnail[0];
	            $img_class = 'img-bg';
	        } else {
	        	$img_class = '';
	        }
			$prevTitle = apply_filters( 'the_title', $prev->post_title );
			$prevLetter = mb_substr($prevTitle, 0, 1, "UTF-8");
			$prevURL = get_permalink( $prev->ID );
			?>
			<article class="prev item">
				<a class="fl-picture <?php echo esc_attr($img_class); ?>" href="<?php echo esc_url($prevURL); ?>" style="background-image: url(<?php echo esc_url($prevThumb); ?>);">
			        <span class="letter"><?php echo esc_html( $prevLetter ); ?></span>
				</a>
				<div class="content">
					<span class="meta"><?php esc_html_e( 'Previous Post', 'rosetta' ); ?></span>
					<h4 class="title">
						<a href="<?php echo esc_url($prevURL); ?>">
							<?php echo esc_html($prevTitle); ?>
						</a>
					</h4>
				</div>
			</article>
		<?php }
		if ( !empty($next) ) {
			if ( has_post_thumbnail($next->ID) && $thumbs == true ) {
	            $nextThumbnail =  wp_get_attachment_image_src( get_post_thumbnail_id( $next->ID ), 'thumbnail');
	            $nextThumb = $nextThumbnail[0];
	            $img_class = 'img-bg';
	        } else {
	        	$img_class = '';
	        }
			$nextTitle = apply_filters( 'the_title', $next->post_title );
			$nextLetter = mb_substr($nextTitle, 0, 1, "UTF-8");
			$nextURL = get_permalink( $next->ID );
			?>
			<article class="next item">
				<a class="fl-picture <?php echo esc_attr($img_class); ?>" href="<?php echo esc_url($nextURL); ?>" style="background-image: url(<?php echo esc_url($nextThumb); ?>);">
			        <span class="letter"><?php echo esc_html( $nextLetter ); ?></span>
				</a>
				<div class="content">
					<span class="meta"><?php esc_html_e( 'Next Post', 'rosetta' ); ?></span>
					<h4 class="title">
						<a href="<?php echo esc_url($nextURL); ?>">
							<?php echo esc_html($nextTitle); ?>
						</a>
					</h4>
				</div>
			</article>
		<?php } ?>
	</div>
<?php
}