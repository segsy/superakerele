<?php

// Rosetta Child Theme Styles
function rosetta_child_enqueue_styles() {
    $parenthandle = 'rosetta-style';
    $theme = wp_get_theme();
    wp_enqueue_style( $parenthandle, get_template_directory_uri() . '/style.css', array(), $theme->parent()->get('Version') );
    wp_enqueue_style( 'rosetta-child-style', get_stylesheet_uri(), array( $parenthandle ), $theme->get('Version') );

    //RTL
    if ( is_rtl() ) {
        wp_enqueue_style( 'rosetta-rtl-style', get_template_directory_uri() . '/rtl.css', array(), $theme->parent()->get('Version') );
    }
}
add_action( 'wp_enqueue_scripts', 'rosetta_child_enqueue_styles' );

// Rosetta Child After Setup
function rosetta_child_theme_setup() {
    load_child_theme_textdomain( 'rosetta-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'rosetta_child_theme_setup' );