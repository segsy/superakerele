<?php
// Vars
$header = true;
$thumbs = true;
$thumbnail = '';
$class = '';

// Hide Thumbnails
if ( get_theme_mod('hide_thumbs') ) { $thumbs = false; }

if ( class_exists( 'WooCommerce' ) ) {
    if ( is_account_page() ) {
    	$header = false;
    	$thumbs = false;
    }
}

get_header();

while (have_posts()) :
	the_post();

	if ( has_post_thumbnail() && $thumbs == true ) {
	    $thumbnail =  wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ),'rosetta_large');
	    $thumbnail = $thumbnail[0];
	    $class = 'img-bg';
	}
?>

<?php if ( $header == true ) { ?>
	<div class="fl-container fl-box <?php echo esc_attr($class); ?>" style="background-image: url(<?php echo esc_url($thumbnail); ?>);">
		<div class="head">
		    <h1 class="title"><?php the_title(); ?></h1> 
		</div>
	</div>
<?php } ?>

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
	</article>
</div>

<?php

// Comments
comments_template();

endwhile;

get_footer();

?>