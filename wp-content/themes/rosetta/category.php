<?php
get_header();

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
?>
<div class="fl-container fl-box">
	<div class="head">		
	    <?php if ( $paged > 1) {	
			echo '<span class="number">'. esc_html__('Page', 'rosetta') . ' '. esc_html($paged) .'</span>';
		} ?>
	    <h1 class="title"><?php single_cat_title('',true); ?></h1>
	</div>
	<?php
	    $term = get_queried_object();
		$subCats = get_terms( $term->taxonomy, array(
			'parent' => $term->term_id,
		) );
		
		if ($subCats) {
			echo '<div class="fl-category info">';
				foreach ($subCats as $cat) {
                    echo '<a class="cat-link" href="'. get_category_link($cat->term_id) .'">'. $cat->name .'</a>';
                }
            echo '</div>';
		}

		if ( category_description() ) {
			echo '<div class="info">';
				echo category_description();
		    echo '</div>';
	    }
    ?>
</div>

<div id="fl-content" class="fl-container">
	<?php
	// Defualt Loop
    echo rosetta_default_loop(rosetta_layout_settings()['layout'], rosetta_layout_settings()['thumb_size']);
	?>
</div>

<?php get_footer(); ?>