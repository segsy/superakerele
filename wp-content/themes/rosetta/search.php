<?php
get_header();

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
?>

<div class="fl-container fl-box">
	<div class="head">
		<?php 
		// Search Count
		$allsearch = new WP_Query("s=$s&showposts=-1");
		$count = $allsearch->found_posts;			
		?>
		
		<?php if ( $paged > 1) {    
            echo '<span class="number">'. esc_html__('Page', 'rosetta') . ' '. esc_html($paged) .'</span>';
        } ?>

	    <span class="box-meta"><?php echo esc_html($count).' '.esc_html__( 'Search Results for', 'rosetta' ); ?></span>
		<h1 class="title"><?php echo esc_html($s); ?></h1>
	</div>
</div>

<div id="fl-content" class="fl-container">
    <?php
    // Defualt Loop
    echo rosetta_default_loop(rosetta_layout_settings()['layout'], rosetta_layout_settings()['thumb_size']);
    ?>
</div>

<?php get_footer(); ?>