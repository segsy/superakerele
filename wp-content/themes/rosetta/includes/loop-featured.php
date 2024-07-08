<?php

// Vars
$class = '';
$thumbnail = '';
$img_class = '';
$thumbs = true;
$thumbnail_size = 'rosetta_large';

// Hide Thumbnails
if ( get_theme_mod('hide_thumbs') ) { $thumbs = false; }

// Featured Style
$style = get_theme_mod('featured_style', 'slider');

// Return if 'none'
if ( $style == 'none') { return false; }
// Carousel Class
if ( $style == 'carousel') {
    $class = 'carousel';
    $thumbnail_size = 'rosetta_masonry';
}

// Posts Ids
$posts_ids = get_theme_mod('featured_posts');
$Ids = str_replace(' ', '', $posts_ids);
$array_ids = explode(',',$Ids);

// Cats Slugs
$featured_cats = get_theme_mod('featured_cats', 'lifestyle,travel');
$cats = str_replace(' ', '', $featured_cats);
$array_cats = explode(',',$cats);

// Featured number
$featured_num = get_theme_mod('featured_num', 5);

$cats_loop = new WP_Query(
    array (
        'post_type'           => 'post',  
        'post_status'         => 'publish',
        'tax_query'           => array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $array_cats //Cats
            )
        ),
        'posts_per_page'      => $featured_num, // Posts Number  
        'ignore_sticky_posts' => 1,
    )
);

$loop_ids = array_unique( array_merge($array_ids, wp_list_pluck($cats_loop->posts, 'ID')) );

$loop = new WP_Query(
    array(
        'post_type'           => 'post',  
        'post_status'         => 'publish',
        'post__in'            => $loop_ids, //Posts
        'ignore_sticky_posts' => 1,  
        'posts_per_page'      => $featured_num, // Posts Number  
        'orderby'             => 'post__in'  
    )
);

if ( $loop->have_posts() ) {
    while ( $loop->have_posts() ) {
        $loop->the_post();

        $title = get_the_title();
        $letter = mb_substr($title, 0, 1, "UTF-8");        
        
        if ( has_post_thumbnail() && $thumbs == true ) {
            $thumbnail =  wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $thumbnail_size);
            $thumbnail = $thumbnail[0];
            $img_class = 'img-bg';
        }

        if ( $loop->current_post == 0 ) {
            echo '<div id="fl-featured" class="owl-carousel fl-container '. esc_attr($class) .' '. esc_attr($img_class) .'">';
        }

        ?>    
        <article class="post" style="background-image: url(<?php echo esc_url($thumbnail); ?>);" data-dot="<span><?php echo esc_html($letter); ?></span>">
            <span class="letter"><?php echo esc_html($letter); ?></span>
            <div class="post-header">
                <h2 class="title"><a href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a></h2>
                <div class="meta-wrap">
                    <div class="fl-category"><?php echo the_category(' '); ?></div>
                    <div class="fl-meta">
                        <?php
                        if ( !get_theme_mod( 'hide_meta_author' ) ) { ?>
                        <span class="meta author">
                            <i><?php echo esc_html_e('By', 'rosetta'); ?></i>
                            <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" title="<?php echo esc_attr( get_the_author() ); ?>">
                                <?php the_author(); ?>                       
                            </a>
                        </span>
                        <?php }

                        if ( !get_theme_mod( 'hide_meta_date' ) ) { ?>
                        <span class="meta date"><?php echo get_the_date(); ?></span>
                        <?php } 

                        if ( !get_theme_mod( 'hide_meta_comments' ) ) {
                            if ( comments_open() || have_comments() ) { ?>
                                <span class="meta comment">
                                    <a href="<?php comments_link(); ?>" class="comments"><?php comments_number( esc_html__('No Comments', 'rosetta'), esc_html__('1 Comment', 'rosetta'), esc_html__( '% Comments', 'rosetta') ); ?></a>
                                </span>
                            <?php }
                        }
                        if ( !get_theme_mod( 'hide_meta_read' ) ) { ?>
                        <span class="meta time"><?php echo rosetta_reading_time(get_the_ID()); ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </article>
    <?php }
    echo '</div>';
    wp_reset_postdata();
}
?>