<?php

// Constants
	define('FLATLAYERS_THEME_NAME','Rosetta');
	define('FLATLAYERS_THEME_PATH', get_template_directory_uri());
    define('FLATLAYERS_THEME_VERSION', '1.5');

// Content Width
if ( ! isset( $content_width ) ) {
    $content_width = 720;
}

// Setup Theme
function rosetta_theme_setup() {

	// Register Main menu
	register_nav_menus ( array(
        'main-menu' => esc_html__( 'Main Menu', 'rosetta' ),
	) );
			
	// Feed Links
	add_theme_support( 'automatic-feed-links' );
	
	// Post thumbnails
	add_theme_support( 'post-thumbnails' );
    add_image_size( 'rosetta_large', 1140, 640, true );
    add_image_size( 'rosetta_medium', 550, 310, true );
    add_image_size( 'rosetta_masonry', 550, 0, true );

    // Theme localization
    load_theme_textdomain( 'rosetta', get_template_directory() . '/languages' );

    // Title Tag
    add_theme_support( 'title-tag' );
    // Widgets
    add_theme_support( 'widgets' );

    // Add support for editor styles.
    add_theme_support( 'editor-styles' );
    add_editor_style( 'css/editor-style.css' );
    // Google Fonts
    add_editor_style( rosetta_google_fonts() );
    // Bootstrap Icons
    add_editor_style( 'css/bootstrap-icons.css' );

    // RTL Style editor
    if ( is_rtl() ) {
        add_editor_style( 'css/editor-style-rtl.css' );
    }

    add_theme_support( 'widgets-block-editor' );
    add_theme_support( 'customize-selective-refresh-widgets' );

    // Responsive Embeds
    add_theme_support( 'responsive-embeds' );
    // Align Wide & Full
    add_theme_support( 'align-wide' );

    // Woocommerce Support
    add_theme_support('woocommerce');

    // WooCommerce Gallery
    if ( class_exists( 'WooCommerce' ) ) {
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );
    }

}
add_action( 'after_setup_theme', 'rosetta_theme_setup' );

// Block Dynamic Style
function rosetta_block_style() {
    wp_add_inline_style( 'wp-block-editor', rosetta_customizer_output() );
}
add_action( 'enqueue_block_editor_assets', 'rosetta_block_style' );

// Addons
function rosetta_addons_scripts() {
    // Bootstrap Icons
    wp_enqueue_style('bootstrap-icons', FLATLAYERS_THEME_PATH . '/css/bootstrap-icons.css', array(), '1.8.1', 'all' );

    // OWL Carousel
    wp_enqueue_style('owl-carousel', FLATLAYERS_THEME_PATH . '/css/owl.carousel.min.css', array(), '2.3.4', 'all' );
    
    // Fonts    
    wp_enqueue_style( 'rosetta-google-fonts', rosetta_google_fonts(), array(), null );

    // Rosetta Style
	wp_enqueue_style( 'rosetta-style', get_stylesheet_uri() );

    // OWL Carousel
    wp_enqueue_script( 'owl-carousel', FLATLAYERS_THEME_PATH . '/js/owl.carousel.min.js', array('jquery'), '2.3.4', false );

    // Rosetta Script
    wp_enqueue_script( 'rosetta-script', FLATLAYERS_THEME_PATH . '/js/rosetta.js', array('jquery'), FLATLAYERS_THEME_VERSION, false );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

    //Customizer output
    wp_add_inline_style( 'rosetta-style', rosetta_customizer_output() );

    // Register Rosetta Script For Ajax
    global $wp_query;
    wp_localize_script( 'rosetta-script', 'rosetta', array(
        'ajaxurl'    => admin_url( 'admin-ajax.php' ),
        'query_vars' => json_encode( $wp_query->query ),
        'layout'     => rosetta_layout_settings()['layout'],
        'thumb_size' => rosetta_layout_settings()['thumb_size'],
        'ajax_end'   => '<div class="fl-ajax-end">'. esc_html__('Sorry, No More Posts to Load!', 'rosetta') .'</div>',
    ) );
}
add_action( 'wp_enqueue_scripts', 'rosetta_addons_scripts' );

// Sidebars
function rosetta_sidebars() {
    // Drawer Sidebar
    register_sidebar( array(
        'name'         => esc_html__( 'Drawer Sidebar', 'rosetta' ),
        'id'           => 'rosetta_drawer',
        'description'  => esc_html__( 'Drawer Sidebar', 'rosetta' ),
        'before_widget'=> '<div id="%1$s" class="fl-widget clearfix %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="fl-widget-title">',
        'after_title'  => '</h3>',
    ) );
}
add_action( 'widgets_init', 'rosetta_sidebars' );


// Category Options
include_once( get_template_directory() . '/functions/category-options.php' );

// FlatLayers Customizer
include_once( get_template_directory() . '/functions/customizer.php' );


// WooCommerce
if ( class_exists( 'WooCommerce' ) ) {
    include_once ( get_template_directory() . '/functions/woocommerce.php' );
}

// Calculate Reading Time
function rosetta_reading_time($postID) {
    $content = get_the_content($postID);
    $count = str_word_count($content);
    $speed = 250; // Words/Minute
    
    $time = round($count/$speed);

    return esc_html($time) .' '. esc_html__('Min Read', 'rosetta');
}

// Fonts Function
function rosetta_google_fonts() {
    $body_font = get_theme_mod('body_font', 'Open Sans');
    $headings_font = get_theme_mod('headings_font', 'Poppins');
     
    $font_families = array(
        'family='. str_replace(' ', '+', $headings_font) .':ital,wght@0,400;0,700;1,400;1,700',
        'family='. str_replace(' ', '+', $body_font) .':ital,wght@0,400;0,700;1,400;1,700'
    );

    $fonts_url = 'https://fonts.googleapis.com/css2?'. implode( '&', array_unique($font_families) ). '&display=swap';

    return esc_url_raw($fonts_url);
}

// Excerpt More
function rosetta_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'rosetta_excerpt_more' );

// Excerpt Length
function rosetta_excerpt_length( $length ) {
    return '70';
}
add_filter( 'excerpt_length', 'rosetta_excerpt_length' );

// Loops & Pagination & Ajax
require_once ( get_template_directory() . '/functions/loops.php');

// Layout Settings -> used in all Archive pages
function rosetta_layout_settings() {

    if ( is_home() ) {
        $layout = get_theme_mod('home_layout', 'standard'); 
    } elseif ( is_category() ) {   
        $term = get_queried_object();
        $layout = get_term_meta($term->term_id, 'rosetta_cat_layout', true);
        if ( $layout == 'default' || empty($layout) ) {
            $layout = get_theme_mod('category_layout', 'grid');
        }
    } elseif ( is_author() ) {
        $layout = get_theme_mod('author_layout', 'grid');
    } elseif ( is_search() ) {
        $layout = get_theme_mod('search_layout', 'grid');
    } else {
        $layout = get_theme_mod('archive_layout', 'grid');
    }
    
    switch ($layout) {

        case 'standard':
            $loop_class = 'fl-standard';
            $thumb_size = 'rosetta_large';
            break;

        case 'grid':
            $loop_class = 'fl-grid';
            $thumb_size = 'rosetta_medium';
            break;

        case 'grid_col_3':
            $loop_class = 'fl-grid col-3';
            $thumb_size = 'rosetta_medium';
            break;        

        case 'box':
            $loop_class = 'fl-grid fl-letterbox';
            $thumb_size = 'rosetta_medium';
            break;

        case 'box_col_3':
            $loop_class = 'fl-grid col-3 fl-letterbox';
            $thumb_size = 'rosetta_medium';
            break;

        case 'masonry':
            $loop_class = 'fl-grid fl-masonry';
            $thumb_size = 'rosetta_masonry';
            break;

        case 'masonry_col_3':
            $loop_class = 'fl-grid col-3 fl-masonry';
            $thumb_size = 'rosetta_masonry';
            break;

        case 'box_masonry':
            $loop_class = 'fl-grid fl-letterbox fl-masonry';
            $thumb_size = 'rosetta_masonry';
            break;

        case 'box_masonry_col_3':
            $loop_class = 'fl-grid col-3 fl-letterbox fl-masonry';
            $thumb_size = 'rosetta_masonry';
            break;
    }

    return array(
        'layout'      => $layout,
        'loop_class'  => $loop_class,
        'thumb_size'  => $thumb_size,
    );
}


// Color Mode
function rosetta_color_mode() {
    // Theme Mode in Javascript
    if ( !get_theme_mod( 'dark_mode' ) ) {
        if ( get_theme_mod( 'dark_mode_default' ) ) { ?>
            <script type="text/javascript">
                if ( document.cookie.indexOf('rosetta_color_theme=light') === -1 ) {
                    document.cookie = 'rosetta_color_theme=dark; path=/; SameSite=None; Secure';
                }
            </script>
        <?php } ?>
        <script type="text/javascript">
            const userPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if ( document.cookie.indexOf('rosetta_color_theme=dark') > -1 || (userPrefersDark && document.cookie.indexOf('rosetta_color_theme=light') === -1) ) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.addEventListener("DOMContentLoaded", function() {
                    document.getElementById('fl-darkmode').checked = true;
                });
            }
        </script>
        <?php
    }
}
add_action('wp_head', 'rosetta_color_mode');


// Category FrontEnd
function rosetta_walker_nav_menu_start_el( $item_output, $item, $depth, $args ) {
    if ( 'footer-menu' !== $args->theme_location ) {
        // Add arrow to menu has child
        if ( in_array('menu-item-has-children', $item->classes) ) {
            $item_output .='<span class="arrow"><i class="bi bi-chevron-down"></i></span>';
        }
    }
    return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'rosetta_walker_nav_menu_start_el', 10, 4 );

// TGM Class
require_once ( get_template_directory() . '/functions/class-tgm-plugin-activation.php');

// Required Plugins
function rosetta_required_plugins() {
    $plugins = array(

        array(
            'name'                  => esc_html__( 'Rosetta Theme Addons', 'rosetta' ),
            'slug'                  => 'flatlayers-rosetta-addons',
            'source'                => get_template_directory() . '/functions/flatlayers-rosetta-addons.zip',
            'required'              => true,
            'version'               => '1.0',
            'force_activation'      => false,
            'force_deactivation'    => false,
            'external_url'          => '',
        ),

    );
    $config = array(
        'id'           => 'tgmpa',
        'default_path' => '',
        'menu'         => 'tgmpa-install-plugins',
        'parent_slug'  => 'themes.php',
        'capability'   => 'edit_theme_options',
        'has_notices'  => true,
        'dismissable'  => true,
        'dismiss_msg'  => '',
        'is_automatic' => false,
        'message'      => '',
    );

    tgmpa( $plugins, $config );
}

add_action( 'tgmpa_register', 'rosetta_required_plugins' );