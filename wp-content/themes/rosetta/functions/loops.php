<?php

// Default Posts Loop
// Used in all Archives and index.php
function rosetta_default_loop($layout, $thumb_size) {
    global $wp_query;

    $current = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $max = $wp_query->max_num_pages;

    echo '<div class="'. esc_attr( rosetta_layout_settings()['loop_class'] ) .'">';
        echo '<div class="fl-loop-posts">';

            if ( have_posts() ) {
                while ( have_posts() ) {
                    the_post();
                    get_template_part( 'includes/loop-post', null, array( 'thumb_size' => $thumb_size, 'layout' => $layout ) );
                }
            } else {
                echo '<div class="fl-ajax-end">'. esc_html__('Sorry, No Posts Found!', 'rosetta') .'</div>';
            }

        echo '</div>';

        rosetta_pagination($current, $max);
        
    echo '</div>';
}


// Pagination
function rosetta_pagination($current, $max) {

    $style = get_theme_mod( 'pagination_style' , 'ajax');

    switch ($style) {
        case 'pagination':
            $big = 999999999; // need an unlikely integer
            if ($max > 1) {
                echo '<div class="fl-pagination">';
                    echo paginate_links( array(
                        'base'       => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                        'format'     => '?paged=%#%',
                        'current'    => max( 1, $current ),
                        'total'      => $max,
                        'prev_text'  => esc_html__( 'Previous', 'rosetta' ),
                        'next_text'  => esc_html__( 'Next', 'rosetta' ),
                    ) );
                echo '</div>';
            }
            break;

        case 'navigation':
            echo '<div class="fl-navigation">';
                posts_nav_link( '<span>—</span>', __( 'Newer Stories', 'rosetta' ),  __( 'Older Stories', 'rosetta' ) );
            echo '</div>';
            break;

        case 'infinite_scroll':
            if ( $current == 1 && $max > 1 ) {
                echo '<a href="#" class="fl-load-more infinite" current-page="'. esc_attr($current) .'" max-pages="'. esc_attr($max) .'">'. esc_html__('Load More Posts', 'rosetta') .'</a>';
            }
            break;
        
        default:
            if ( $current == 1 && $max > 1 ) {
                echo '<a href="#" class="fl-load-more" current-page="'. esc_attr($current) .'" max-pages="'. esc_attr($max) .'">'. esc_html__('Load More Posts', 'rosetta') .'</a>';
            }
            break;
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////

// Ajax Loop Call
function rosetta_posts_ajax() {
    $thumb_size = $_POST['thumb_size'];
    $layout = $_POST['layout'];
    $next = $_POST['next'];

    $query_vars = json_decode( stripslashes( $_POST['query_vars'] ), true );

    $query_vars['paged'] = $next;
    $query_vars['post_status'] = 'publish';

    $loop = new WP_Query( $query_vars );

    ob_start();

    if ( $loop->have_posts() ) {
        while ( $loop->have_posts() ) {
            $loop->the_post();
            get_template_part( 'includes/loop-post', null, array( 'thumb_size' => $thumb_size, 'layout' => $layout ) );
        }
    }

    $output = ob_get_clean();

    wp_die( $output );
}
add_action( 'wp_ajax_rosetta_posts_ajax', 'rosetta_posts_ajax' );
add_action( 'wp_ajax_nopriv_rosetta_posts_ajax', 'rosetta_posts_ajax' );