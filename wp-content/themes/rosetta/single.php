<?php
// Vars
$thumbs = true;
$thumbnail = '';
$class = '';

// Hide Thumbnails
if ( get_theme_mod('hide_thumbs') ) { $thumbs = false; }

get_header();

while (have_posts()) :
	the_post();

	if ( has_post_thumbnail() && $thumbs == true ) {
	    $thumbnail =  wp_get_attachment_image_src( get_post_thumbnail_id(),'rosetta_large');
	    $thumbnail = $thumbnail[0];
	    $class = 'img-bg';
	}
?>

<div class="fl-container fl-box <?php echo esc_attr($class); ?>" style="background-image: url(<?php echo esc_url($thumbnail); ?>);">
	<div class="head">
	    <h1 class="title"><?php the_title(); ?></h1> 
	</div>
	<div class="meta-wrap">
        <div class="fl-category"><?php echo the_category(' '); ?></div>
        <div class="fl-meta">
            <?php
            if ( !get_theme_mod( 'hide_meta_author' ) ) { ?>
            <span class="meta">
                <i><?php echo esc_html_e('By', 'rosetta'); ?></i>
                <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" title="<?php echo esc_attr( get_the_author() ); ?>">
                    <?php the_author(); ?>                       
                </a>
            </span>
            <?php }

            if ( !get_theme_mod( 'hide_meta_date' ) ) { ?>
            <span class="meta"><?php echo get_the_date(); ?></span>
            <?php } 

            if ( !get_theme_mod( 'hide_meta_comments' ) ) {
                if ( comments_open() || have_comments() ) { ?>
                    <span class="meta">
                        <a href="<?php comments_link(); ?>" class="comments"><?php comments_number( esc_html__('No Comments', 'rosetta'), esc_html__('1 Comment', 'rosetta'), esc_html__( '% Comments', 'rosetta') ); ?></a>
                    </span>
                <?php }
            }
            if ( !get_theme_mod( 'hide_meta_read' ) ) { ?>
            <span class="meta"><?php echo rosetta_reading_time(get_the_ID()); ?></span>
            <?php } ?>
        </div>
    </div>
</div>

<div class="fl-container">
	<article id="post-<?php the_ID(); ?>" <?php post_class('fl-post-content'); ?>>

		<div class="clearfix">
			<?php
				the_content();
				$link_pages_args = array (
				    'before'            => '<div class="fl-page-links">',
				    'after'             => '</div>',
				    'separator'         => '<span class="page-link-sep"><span>'. esc_html__( 'Page', 'rosetta' ) .'</span>'. $page . ' / ' . count($pages) .'</span>',
				    'next_or_number'    => 'next',
				    'nextpagelink'      => '<span class="page-link">'. esc_html__( 'Next', 'rosetta' ) . '</span><i class="bi bi-arrow-right"></i>',
			        'previouspagelink'  => '<i class="bi bi-arrow-left"></i><span class="page-link">' . esc_html__( 'Previous', 'rosetta' ) . '</span>',
				);
				wp_link_pages( $link_pages_args );
		    ?>
		</div>
		<?php if( has_tag() && !get_theme_mod( 'hide_meta_tags' ) ) { ?>
        	<div class="fl-tags">
        		<span class="tag"><?php esc_html_e( 'Tags', 'rosetta' ); ?></span>
				<?php echo the_tags( '', '<span class="seprator">/</span>' ); ?>
			</div>
		<?php }

		// Sharing
		if ( !get_theme_mod( 'hide_sharing' ) && function_exists('fl_rosetta_sharing') ) {
			fl_rosetta_sharing();
		}

		?>
	</article>
</div>
	<?php

	// Author Section
	get_template_part('includes/author-box');

	// Previous & Next Posts Section
	get_template_part('includes/pre-next-posts');

	// Related Posts
	get_template_part('includes/related-posts');

	// Comments
	comments_template();

	endwhile; ?>


<?php get_footer(); ?>