<?php
get_header();

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
?>

<div class="fl-container fl-box">
	<div class="avatar">
		<?php echo get_avatar( get_the_author_meta( 'user_email' ), 108 ); ?>
	</div>
	<div class="head">
        <?php if ( $paged > 1) {    
            echo '<span class="number">'. esc_html__('Page', 'rosetta') . ' '. esc_html($paged) .'</span>';
        } ?>
	    <span class="box-meta"><?php esc_html_e( 'Articles Written By', 'rosetta' ); ?></span>
	    <h1 class="title"><?php the_author(); ?></h1>
        <?php if ( get_the_author_meta( 'description' ) ) {
            echo '<div class="info">';
                echo '<p>'. get_the_author_meta( 'description' ) .'</p>';
            echo '</div>';
        } ?>
	    <div class="fl-social-icons">

            <?php if ( get_the_author_meta( 'url' ) ) { ?>
                <a href="<?php the_author_meta( 'url' ); ?>" target="_blank" rel="nofollow">
                    <i class="bi bi-globe"></i>
                </a>
            <?php }

            if ( get_the_author_meta( 'twitter' ) ) { ?>
                <a href="<?php the_author_meta( 'twitter' ); ?>" target="_blank" rel="nofollow">
                    <i class="bi bi-twitter"></i>
                </a>
            <?php }

            if ( get_the_author_meta( 'facebook' ) ) { ?>
                <a href="<?php the_author_meta( 'facebook' ); ?>" target="_blank" rel="nofollow">
                    <i class="bi bi-facebook"></i>
                </a>
            <?php }

            if ( get_the_author_meta( 'instagram' ) ) { ?>
                <a href="<?php the_author_meta( 'instagram' ); ?>" target="_blank" rel="nofollow">
                    <i class="bi bi-instagram"></i>
                </a>
            <?php }

            if ( get_the_author_meta( 'youtube' ) ) { ?>
                <a href="<?php the_author_meta( 'youtube' ); ?>" target="_blank" rel="nofollow">
                    <i class="bi bi-youtube"></i>
                </a>
            <?php }

            if ( get_the_author_meta( 'linkedin' ) ) { ?>
                <a href="<?php the_author_meta( 'linkedin' ); ?>" target="_blank" rel="nofollow">
                    <i class="bi bi-linkedin"></i>
                </a>
            <?php }

            if ( get_the_author_meta( 'pinterest' ) ) { ?>
                <a href="<?php the_author_meta( 'pinterest' ); ?>" target="_blank" rel="nofollow">
                    <i class="bi bi-pinterest"></i>
                </a>
            <?php }

            if ( get_the_author_meta( 'reddit' ) ) { ?>
                <a href="<?php the_author_meta( 'reddit' ); ?>" target="_blank" rel="nofollow">
                    <i class="bi bi-reddit"></i>
                </a>
            <?php }

            ?>

        </div>
	</div>
</div>
	
<div id="fl-content" class="fl-container">
    <?php
    // Defualt Loop
    echo rosetta_default_loop(rosetta_layout_settings()['layout'], rosetta_layout_settings()['thumb_size']);
    ?>
</div>

<?php get_footer(); ?>