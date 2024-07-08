<?php

// FlatLayers Buzzmag WooCommerce Functions
function rosetta_enqueue_woocommerce_style() {
    wp_enqueue_style( 'rosetta-woocommerce-style', FLATLAYERS_THEME_PATH . '/css/woocommerce.css', array('woocommerce-general', 'rosetta-style'), FLATLAYERS_THEME_VERSION );

    if ( is_rtl() ) {
        wp_enqueue_style( 'rosetta-woocommerce-rtl-style', FLATLAYERS_THEME_PATH . '/css/woocommerce-rtl.css', array('woocommerce-general', 'rosetta-style', 'rosetta-woocommerce-style'), FLATLAYERS_THEME_VERSION );
    }
}
add_action( 'wp_enqueue_scripts', 'rosetta_enqueue_woocommerce_style' );


/* ====== Remove Content Wrappers ====== */
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper');
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end');


/* ====== Change Main Content Wrapper Start ====== */
function rosetta_woocommerce_before_wrapper() {

    // Remove Breadcrumbs brfore main content in Home and single product
    if ( is_home() || is_product() ) {    
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
    }

    // Open Archive Shop Wrap
    if ( !is_product() ) {
            echo '<div class="fl-box fl-container">';
                if ( is_product_category() ) {
                    $cat = get_queried_object();
                    $thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
                    $image = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
                    if ( !empty($image) ) {
                        echo '<div class="avatar">';
                            echo '<img loading="lazy" src="'. esc_url( $image[0] ) .'" alt="' . esc_attr( $cat->name ) . '">';
                        echo'</div>';
                    }
                }
                echo'<div class="info">';
    } else {
        echo '<div id="fl-content" class="fl-container">';
    }

}
add_action('woocommerce_before_main_content', 'rosetta_woocommerce_before_wrapper');


/* ====== Change Main Content Wrapper End ====== */
function rosetta_woocommerce_after_wrapper() {        
    echo '</div>'; // Close .fl-container
}
add_action('woocommerce_after_main_content', 'rosetta_woocommerce_after_wrapper');



/* ====== Close Archive Shop Wrap ====== */
function rosetta_woocommerce_after_archive() {
    // Close Archive Shop Wrap
    if ( !is_product() ) {
                echo '</div>'; // Close .info
            echo '</div>'; // Close .fl-box
        echo '<div id="fl-content" class="fl-container">';
    }
}
add_action('woocommerce_archive_description', 'rosetta_woocommerce_after_archive');


/* ====== Change Products Columns ====== */
function rosetta_woocommerce_loop_columns() {  
    return 3;
}
add_filter('loop_shop_columns', 'rosetta_woocommerce_loop_columns');

/* ====== Ajax update cart icon ====== */
function rosetta_woocommerce_header_add_to_cart_fragment( $fragments ) {
    ob_start();
    ?>
    <a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'rosetta' ); ?>">
        <i class="bi bi-basket"></i>                             
        <?php if ( WC()->cart->get_cart_contents_count() != 0 ) { ?>
            <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
        <?php } ?>
    </a>
    <?php
    
    $fragments['a.cart-contents'] = ob_get_clean();
    
    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'rosetta_woocommerce_header_add_to_cart_fragment' );


/* ====== Add Breadcurmb to Single Product Page ====== */
add_action('woocommerce_single_product_summary', 'woocommerce_breadcrumb', 0, 0);

/* ====== Remove Default Sidebar ====== */
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );