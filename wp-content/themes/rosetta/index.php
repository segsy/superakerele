<?php

get_header();


$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Featured Posts
if ( get_theme_mod('featured_style') != 'none' && $paged == 1 ) {
	get_template_part('includes/loop-featured');
}

?>

<div id="fl-content" class="fl-container">
	<?php
		// Latest Title
		echo '<span class="fl-stories">';
			if ( $paged > 1) {	
				echo '<i class="number">'. esc_html__('Page', 'rosetta') . ' '. esc_html($paged) .'</i>';
			}
			echo esc_html__('Latest Stories', 'rosetta');			
		echo '</span>';		

		// Defualt Loop
	    echo rosetta_default_loop(rosetta_layout_settings()['layout'], rosetta_layout_settings()['thumb_size']);
	?>
</div>

<?php get_footer(); ?>