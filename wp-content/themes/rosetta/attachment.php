<?php get_header(); ?>

<div id="fl-content" class="fl-container">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="fl-picture">
			<?php echo wp_get_attachment_image( get_the_ID(), 'full' ); ?>
		</div>
		<div class="fl-post-content clearfix">
			<h1 class="title"><?php the_title(); ?></h1>
		    <?php

		    if ( has_excerpt() ) {
		    	echo '<h2>'. esc_html__('Caption:', 'rosetta') .'</h2>';
		    	the_excerpt();
		    }

		    echo '<h2>'. esc_html__('Description:', 'rosetta') .'</h2>';
		    the_content();

		    ?>
		</div>
	</article>
</div>

<?php get_footer(); ?>