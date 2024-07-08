<?php if ( !get_theme_mod( 'hide_related_box' ) ) {

	if ( get_theme_mod( 'related_type' ) == 'category' ) {
		$relates = get_the_category($post->ID);
	} else {
		$relates = wp_get_post_tags($post->ID);
	}

	if ( !empty($relates) ) {

		$IDs = array();

		foreach ( $relates as $relate ) {
			$IDs[] = $relate->term_id;
		}

		$args = array(
			'post__not_in'        => array($post->ID),
			'posts_per_page'      => 6,
			'ignore_sticky_posts' => 1,
		);

		if ( get_theme_mod( 'related_type' ) == 'category' ) {
			$args = array_merge( $args, array('category__in' => $IDs) );
		} else {
			$args = array_merge($args, array('tag__in' => $IDs) );
		}
	
		$loop = new wp_query( $args );

		if ( $loop->have_posts() ) { ?>
			<div id="fl-related" class="fl-container">
				<div class="fl-grid col-3 fl-letterbox">
					<span class="fl-stories"><?php esc_html_e( 'You might also like', 'rosetta' ); ?></span>
					<div class="fl-loop-posts">
						<?php while ( $loop->have_posts() ) {
				            $loop->the_post();
				           get_template_part( 'includes/loop-post', null, array( 'thumb_size' => 'rosetta_medium', 'layout' => 'box_col_3' ) );
				        } ?>
					</div>
				</div>
			</div>
		<?php }
		wp_reset_postdata();
	}
} ?>