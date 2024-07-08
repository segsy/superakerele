<?php
get_header();

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
?>
<div class="fl-container fl-box">
	<div class="head">
		<?php if ( $paged > 1) {	
			echo '<span class="number">'. esc_html__('Page', 'rosetta') . ' '. esc_html($paged) .'</span>';
		} ?>
	    <span class="box-meta"><?php esc_html_e( 'Browsing Archive', 'rosetta' ); ?></span>
	    <?php the_archive_title( '<h1 class="title">', '</h1>' ); ?>
	</div>
	<?php if ( get_the_archive_description() ) {
		echo '<div class="info">';
			echo '<p>'. get_the_archive_description() .'</p>';
	    echo '</div>';
    } ?>
</div>

<div id="fl-content" class="fl-container">
	<?php
	// Defualt Loop
    echo rosetta_default_loop(rosetta_layout_settings()['layout'], rosetta_layout_settings()['thumb_size']);
	?>
</div>

<?php get_footer(); ?>