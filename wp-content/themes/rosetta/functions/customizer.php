<?php

/*	
*	Rosetta Customizer
*	---------------------
*/


function rosetta_customizer( $wp_customize ) {
// Settings, Sections, and Controls are defined here

	$layouts = array(
    	'grid'              => esc_html__('Grid 2 Columns', 'rosetta'),
    	'grid_col_3'        => esc_html__('Grid 3 Columns', 'rosetta'),
    	'box'               => esc_html__('Box 2 Columns', 'rosetta'), 
    	'box_col_3'         => esc_html__('Box 3 Columns', 'rosetta'),  
    	'masonry'           => esc_html__('Masonry 2 Columns', 'rosetta'),  	
    	'masonry_col_3'     => esc_html__('Masonry 3 Columns', 'rosetta'),
    	'box_masonry'       => esc_html__('Box Masonry 2 Columns', 'rosetta'),  	
    	'box_masonry_col_3' => esc_html__('Box Masonry 3 Columns', 'rosetta'),
    	'standard'          => esc_html__('Standard', 'rosetta'),
    );

    $featured = array(
    	'none'   => esc_html__( 'No Featured Posts', 'rosetta' ),
    	'slider'  => esc_html__( 'Slider', 'rosetta' ),
    	'carousel' => esc_html__( 'Carousel', 'rosetta' ),
    );

    $paginations = array(
    	'ajax'   => esc_html__( 'Ajax Load More', 'rosetta' ),
    	'infinite_scroll'  => esc_html__( 'Infinite Scroll', 'rosetta' ),
    	'pagination' => esc_html__( 'Pagination (Page Numbers)', 'rosetta' ),
    	'navigation' => esc_html__( 'Navigation (Next & Previous Buttons)', 'rosetta' ),
    );

	// General Settings Section
	$wp_customize->add_section( 'general_settings' , array(
	    'title'       => esc_html__( 'General Settings', 'rosetta' ),
	    'priority'    => 30,
	) );

        // Hide Thumbnails
		$wp_customize->add_setting( 'hide_thumbs', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_thumbs', array(
			'label'    => esc_html__( 'Hide All Thumbnails In Website', 'rosetta' ),
			'section'  => 'general_settings',
			'type'     => 'checkbox',
			'priority' => 1,
		) );

		// Pagination Style
		$wp_customize->add_setting( 'pagination_style', array(
			'default'           => 'ajax',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'pagination_style', array(
			'type'     => 'select',
			'label'    => esc_html__( 'Pagination Style', 'rosetta' ),
			'section'  => 'general_settings',			
			'priority' => 2,
			'choices'  => $paginations,
        ) );

        // Category Archive layout
		$wp_customize->add_setting( 'category_layout', array(
			'default'           => 'grid',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'category_layout', array(
			'type'     => 'select',
			'label'    => esc_html__( 'Categories Archive Layout', 'rosetta' ),
			'section'  => 'general_settings',			
			'priority' => 3,
			'choices'  => $layouts,
        ) );


        // Author layout
		$wp_customize->add_setting( 'author_layout', array(
			'default'           => 'grid',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'author_layout', array(
			'type'     => 'select',
			'label'    => esc_html__( 'Author Archive Layout', 'rosetta' ),
			'section'  => 'general_settings',
			'priority' => 4,
			'choices'  => $layouts,
        ) );


        // Search layout
		$wp_customize->add_setting( 'search_layout', array(
			'default'           => 'grid',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'search_layout', array(
			'type'     => 'select',
			'label'    => esc_html__( 'Search Results Layout', 'rosetta' ),
			'section'  => 'general_settings',
			'priority' => 5,
			'choices'  => $layouts,
        ) );

        // Other Archive layout
		$wp_customize->add_setting( 'archive_layout', array(
			'default'           => 'grid',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'archive_layout', array(
			'type'     => 'select',
			'label'    => esc_html__( 'Tags & Other Archives Layout', 'rosetta' ),
			'section'  => 'general_settings',
			'priority' => 6,
			'choices'  => $layouts,
        ) );

        // Copyrights
		$wp_customize->add_setting( 'copyrights', array(
			'default'   => esc_html('© ') .esc_html( date('Y') ). ' ' .esc_html( get_bloginfo( 'name', 'display' ) ) .'. Designed & Developed by <a href="http://flatlayers.com" target="_blank">FlatLayers</a>',
			'sanitize_callback' => 'rosetta_textarea_sanitize',
		) );

		$wp_customize->add_control( 'copyrights', array(
			'label'     => esc_html__( 'Copyrights', 'rosetta' ),
			'section'   => 'general_settings',
			'type'      => 'textarea',
			'priority'  => 7,
		) );

//.............................. End General Settings Section

// Header Settings Section
	$wp_customize->add_section( 'header_settings' , array(
	    'title'       => esc_html__( 'Header Settings', 'rosetta' ),
	    'priority'    => 35,
	) );

		// Logo
		$wp_customize->add_setting( 'logo', array(
			'default' => FLATLAYERS_THEME_PATH . '/img/logo.png',
			'sanitize_callback' => 'esc_url_raw',
		) );

		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'logo', array(
		    'label'    => esc_html__( 'Logo', 'rosetta' ),
		    'description' => esc_html__( 'Add logo to display logo and dark mode logo.', 'rosetta' ),
		    'section'  => 'header_settings',
		    'priority' => 1,
		) ) );

		// Logo Dark Mode
		$wp_customize->add_setting( 'logo_dark', array(
			'default' => FLATLAYERS_THEME_PATH . '/img/logo-dark.png',
			'sanitize_callback' => 'esc_url_raw',
		) );

		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'logo_dark', array(
		    'label'    => esc_html__( 'Dark Mode Logo', 'rosetta' ),
		    'section'  => 'header_settings',
		    'priority' => 2,
		) ) );

		// Disable Dark Mode
		$wp_customize->add_setting( 'dark_mode', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'dark_mode', array(
			'label'    => esc_html__( 'Disable Dark Mode', 'rosetta' ),
			'section'  => 'header_settings',
			'type'     => 'checkbox',
			'priority' => 3,
		) );

		// Set Dark Mode As Default Mode
		$wp_customize->add_setting( 'dark_mode_default', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'dark_mode_default', array(
			'label'    => esc_html__( 'Set Dark Mode As Default Mode', 'rosetta' ),
			'section'  => 'header_settings',
			'type'     => 'checkbox',
			'priority' => 4,
		) );

		// Disable Fixed header
		$wp_customize->add_setting( 'fixed_header', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'fixed_header', array(
			'label'    => esc_html__( 'Disable Fixed Header', 'rosetta' ),
			'section'  => 'header_settings',
			'type'     => 'checkbox',
			'priority' => 5,
		) );

		// Hide Social Icons
		$wp_customize->add_setting( 'header_drawer', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'header_drawer', array(
			'label'    => esc_html__( 'Hide Drawer on Desktop', 'rosetta' ),
			'section'  => 'header_settings',
			'type'     => 'checkbox',
			'priority' => 6,
		) );

		// Hide Social Icons
		$wp_customize->add_setting( 'header_social', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'header_social', array(
			'label'    => esc_html__( 'Hide Social Icons', 'rosetta' ),
			'section'  => 'header_settings',
			'type'     => 'checkbox',
			'priority' => 7,
		) );

		// Hide Search
		$wp_customize->add_setting( 'header_search', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'header_search', array(
			'label'    => esc_html__( 'Hide Search', 'rosetta' ),
			'section'  => 'header_settings',
			'type'     => 'checkbox',
			'priority' => 8,
		) );

		// WooCommerce
        if ( class_exists( 'WooCommerce' ) ) {
        	// Hide Cart Icon
			$wp_customize->add_setting( 'header_cart', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );

			$wp_customize->add_control( 'header_cart', array(
				'label'    => esc_html__( 'Hide WooCommerce Cart Icon', 'rosetta' ),
				'section'  => 'header_settings',
				'type'     => 'checkbox',
				'priority' => 9,
			) );

			// Hide Account Icon
			$wp_customize->add_setting( 'header_account', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );

			$wp_customize->add_control( 'header_account', array(
				'label'    => esc_html__( 'Hide WooCommerce Account Icon', 'rosetta' ),
				'section'  => 'header_settings',
				'type'     => 'checkbox',
				'priority' => 10,
			) );
        }


//.............................. End Header Settings Section


// Home Settings Section
	$wp_customize->add_section( 'home_settings' , array(
	    'title'       => esc_html__( 'Home Settings', 'rosetta' ),
	    'priority'    => 40,
	) );

		// Home layout
		$wp_customize->add_setting( 'home_layout', array(
			'default'           => 'standard',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'home_layout', array(
			'type'     => 'select',
			'label'    => esc_html__( 'Home Layout', 'rosetta' ),
			'section'  => 'home_settings',			
			'priority' => 1,
			'choices'  => $layouts,
        ) );

		// Featured Posts Layout
		$wp_customize->add_setting( 'featured_style', array(
			'default'           => 'slider',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'featured_style', array(			
			'type'     => 'select',
			'label'    => esc_html__( 'Featured Posts Layout', 'rosetta' ),
			'section'  => 'home_settings',
			'priority' => 2,
			'choices'  => $featured,
		) );

		// Featured Posts IDs
		$wp_customize->add_setting( 'featured_posts', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'featured_posts', array(
			'label'       => esc_html__( 'Featured Posts IDs', 'rosetta' ),
			'description' => esc_html__( 'Split Posts Ids slugs by ",". Example: 1432,4526,4112...etc.', 'rosetta' ),
			'section'     => 'home_settings',
			'type'        => 'text',
			'priority'    => 4,
		) );

		// Featured Categories Slugs
		$wp_customize->add_setting( 'featured_cats', array(
			'default' => 'lifestyle,travel',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'featured_cats', array(
			'label'       => esc_html__( 'Featured Categories Slugs', 'rosetta' ),
			'description' => esc_html__( 'Split category slugs by ",". Example: lifestyle,music,news...etc.', 'rosetta' ),
			'section'     => 'home_settings',
			'type'        => 'text',
			'priority'    => 5,
		) );

		// Featured Count
		$wp_customize->add_setting( 'featured_num', array(
			'default' => 5,
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'featured_num', array(
			'label'       => esc_html__( 'Featured Posts Count', 'rosetta' ),
			'description' => esc_html__( 'Default is 5, Minimum is 1, Maximum is 10.', 'rosetta' ),
			'section'     => 'home_settings',
			'type'        => 'range',
			'priority'    => 6,
			'input_attrs' => array(
				'min' => 1,
				'max' => 10,
				'step' => 1,
			),
		) );

// .............................. End Home Section


// Post Settings Section
	$wp_customize->add_section( 'post_settings' , array(
	    'title'       => esc_html__( 'Post Settings', 'rosetta' ),
	    'priority'    => 45,
	) );

		// Related Posts Type
		$wp_customize->add_setting( 'related_type', array(
	        'default'  => 'tags',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

		$wp_customize->add_control( 'related_type', array(
	    	'type'     => 'select',
	        'label'    => esc_html__( 'Related Posts Type', 'rosetta' ),
	        'section'  => 'post_settings',
	        'priority' => 1,
	        'choices'  => array(
	        	'tags' => esc_html__( 'Based On Tags', 'rosetta' ),
	        	'category' => esc_html__( 'From Same Category', 'rosetta' ),
	        ),
        ) );

		// Hide Author Section
		$wp_customize->add_setting( 'hide_author_box', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_author_box', array(
			'label'    => 'Hide Author Section',
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 2,
		));

		// Hide Related Posts
		$wp_customize->add_setting( 'hide_related_box', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_related_box', array(
			'label'    => esc_html__( 'Hide Related Posts', 'rosetta' ),
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 3,
		));

		// Hide Sharing Section
		$wp_customize->add_setting( 'hide_sharing', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_sharing', array(
			'label'    => 'Hide Sharing Section',
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 4,
		));

		// Hide Author
		$wp_customize->add_setting( 'hide_meta_author', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_meta_author', array(
			'label'    => esc_html__( 'Hide Author Meta', 'rosetta' ),
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 5,
		));

		// Hide Date
		$wp_customize->add_setting( 'hide_meta_date', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_meta_date', array(
			'label'    => esc_html__( 'Hide Date Meta', 'rosetta' ),
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 6,
		));

		// Hide Meta Reading Time
		$wp_customize->add_setting( 'hide_meta_read', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_meta_read', array(
			'label'    => esc_html__( 'Hide Reading Time Meta', 'rosetta' ),
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 7,
		) );

		// Hide Tags
		$wp_customize->add_setting( 'hide_meta_tags', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_meta_tags', array(
			'label'    => esc_html__( 'Hide Tags Meta', 'rosetta' ),
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 8,
		));

		// Hide Comments
		$wp_customize->add_setting( 'hide_meta_comments', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'hide_meta_comments', array(
			'label'    => esc_html__( 'Hide Comments Meta', 'rosetta' ),
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 9,
		));

		// Hide Next & Prev Posts
		$wp_customize->add_setting( 'prev_next_posts', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'prev_next_posts', array(
			'label'    => esc_html__( 'Hide Previous & Next Posts', 'rosetta' ),
			'section'  => 'post_settings',
			'type'     => 'checkbox',
			'priority' => 10,
		));
		
//.............................. End Post Settings Section

	// Colors: General
	$wp_customize->add_section( 'colors_general' , array(
	    'title'       => esc_html__( 'Colors: General', 'rosetta' ),
	    'priority'    => 50,
	) );

		// Accent Color
		$wp_customize->add_setting( 'accent_color', array(
			'default'  => '#ed0e28',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'accent_color', array(
			'label'    => esc_html__( 'Accent Color', 'rosetta' ),
			'section'  => 'colors_general',
			'priority' => 1,
		) ) );

		// Body Background Color
		$wp_customize->add_setting( 'body_bg', array(
			'default'  => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'body_bg', array(
			'label'    => esc_html__( 'Body Background Color', 'rosetta' ),
			'section'  => 'colors_general',
			'priority' => 2,
		) ) );

		// Content Text Color
		$wp_customize->add_setting( 'body_color', array(
			'default' => '#444444',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'body_color', array(
			'label'    => esc_html__( 'Body Text Color', 'rosetta' ),
			'section'  => 'colors_general',
			'priority' => 3,
		) ) );


		// Headings Text Color
		$wp_customize->add_setting( 'headings_color', array(
			'default' => '#111111',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'headings_color', array(
			'label'    => esc_html__( 'Headings Text Color', 'rosetta' ),
			'section'  => 'colors_general',
			'priority' => 4,
		) ) );


		// Buttons Background Color
		$wp_customize->add_setting( 'buttons_bg', array(
			'default' => '#111111',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'buttons_bg', array(
			'label'    => esc_html__( 'Buttons Background Color', 'rosetta' ),
			'section'  => 'colors_general',
			'priority' => 5,
		) ) );

		// Box Background
		$wp_customize->add_setting( 'box_bg', array(
			'default' => '#f5f5f5',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'box_bg', array(
			'label'    => esc_html__( 'Featured Posts, Archive, Page & Post Header Background', 'rosetta' ),
			'section'  => 'colors_general',
			'priority' => 6,
		) ) );

		// Box Color
		$wp_customize->add_setting( 'box_color', array(
			'default' => '#111111',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'box_color', array(
			'label'    => esc_html__( 'Featured Posts, Archive, Page & Post Header Color', 'rosetta' ),
			'section'  => 'colors_general',
			'priority' => 7,
		) ) );

//.............................. End Colors: General Section


	// Colors: Header
	$wp_customize->add_section( 'colors_header' , array(
	    'title'       => esc_html__( 'Colors: Header & Drawer', 'rosetta' ),
	    'priority'    => 55,
	) );

		// Header Background Color
		$wp_customize->add_setting( 'header_bg', array(
			'default'   => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'header_bg', array(
			'label'    => esc_html__( 'Header Background Color', 'rosetta' ),
			'section'  => 'colors_header',
			'priority' => 1,
		) ) );

		// Header Text Color
		$wp_customize->add_setting( 'header_color', array(
			'default'   => '#111111',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'header_color', array(
			'label'    => esc_html__( 'Header Text Color', 'rosetta' ),
			'section'  => 'colors_header',
			'priority' => 2,
		) ) );

		// Submenu & Drawer Background Color
		$wp_customize->add_setting( 'submenu_bg', array(
			'default'   => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'submenu_bg', array(
			'label'    => esc_html__( 'Submenu & Drawer Background', 'rosetta' ),
			'section'  => 'colors_header',
			'priority' => 3,
		) ) );

		// Submenu & Drawer Text Color
		$wp_customize->add_setting( 'submenu_color', array(
			'default'   => '#111111',
			'sanitize_callback' => 'sanitize_hex_color',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'submenu_color', array(
			'label'    => esc_html__( 'Submenu & Drawer Text Color', 'rosetta' ),
			'section'  => 'colors_header',
			'priority' => 4,
		) ) );

//.............................. End Colors: Header

	// Typography Section
	$wp_customize->add_section( 'typography' , array(
	    'title'       => esc_html__( 'Typography', 'rosetta' ),
	    'priority'    => 70,
	) );

        // Headings Font Family
        $wp_customize->add_setting( 'headings_font', array(
	        'default'  => 'Poppins',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'headings_font', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Headings Font Family', 'rosetta' ),
	        'description' => esc_html__( 'Got to Google Fonts (https://fonts.google.com). Copy selected font name and paste it here.', 'rosetta' ),
	        'section'  => 'typography',
	        'priority' => 1,
        ) );


        // Headings Font Weight
        $wp_customize->add_setting( 'headings_font_weight', array(
	        'default'  => '700',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'headings_font_weight', array(
	    	'type'     => 'select',
	        'label'    => esc_html__( 'Headings Font Weight', 'rosetta' ),
	        'section'  => 'typography',
	        'priority' => 2,
	        'choices'  => array(
	        	'400' => 'Normal',
	        	'700' => 'Bold',
	        ),
        ) );

    
		// Content Font Family
        $wp_customize->add_setting( 'body_font', array(
	        'default'  => 'Open Sans',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'body_font', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Body Font Family', 'rosetta' ),
	        'description' => esc_html__( 'Got to Google Fonts (https://fonts.google.com). Copy selected font name and paste it here.', 'rosetta' ),
	        'section'  => 'typography',
	        'priority' => 3,
        ) );
        

        // Body Text Font Size
        $wp_customize->add_setting( 'body_font_size', array(
	        'default'  => '16',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'body_font_size', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Body Text Font Size', 'rosetta' ),
	        'description' => esc_html__( 'Headings and all other typography elements are based on body font. When you increase body font all other typography elements increase with the same ratio.', 'rosetta' ),
	        'section'  => 'typography',
	        'priority' => 4,
        ) );
//.............................. End Typography Section

    // Social Media
	$wp_customize->add_section( 'social' , array(
	    'title'       => esc_html__( 'Social Media (Header)', 'rosetta' ),
	    'priority'    => 75,
		'description' => esc_html__( 'Add social media URLs. Icon will not display if left blank.', 'rosetta' ),
	) );

		// Twitter
        $wp_customize->add_setting( 'twitter', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'twitter', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Twitter', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 1,
        ) );

        // Facebook
        $wp_customize->add_setting( 'facebook', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'facebook', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Facebook', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 2,
        ) );

        // Instagram
        $wp_customize->add_setting( 'instagram', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'instagram', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Instagram', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 3,
        ) );

        // Vimeo
        $wp_customize->add_setting( 'vimeo', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'vimeo', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Vimeo', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 4,
        ) );

        // Youtube
        $wp_customize->add_setting( 'youtube', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'youtube', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Youtube', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 5,
        ) );

        // Dribbble
        $wp_customize->add_setting( 'dribbble', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'dribbble', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Dribbble', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 6,
        ) );

        // Pinterest
        $wp_customize->add_setting( 'pinterest', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'pinterest', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Pinterest', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 7,
        ) );

        // Linkedin
        $wp_customize->add_setting( 'linkedin', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'linkedin', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Linkedin', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 8,
        ) );

        // Reddit
        $wp_customize->add_setting( 'reddit', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'reddit', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Reddit', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 9,
        ) );

        // Behance
        $wp_customize->add_setting( 'behance', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'behance', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'Behance', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 10,
        ) );

        // Mail
        $wp_customize->add_setting( 'mail', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'mail', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'E-mail', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 11,
        ) );

        // RSS
        $wp_customize->add_setting( 'rss', array(
	        'default'  => '',
	        'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( 'rss', array(
	    	'type'     => 'text',
	        'label'    => esc_html__( 'RSS', 'rosetta' ),
	        'section'  => 'social',
	        'priority' => 12,
        ) );
//.............................. End Social Media Section

}
add_action( 'customize_register', 'rosetta_customizer' );

// Textarea Escape
function rosetta_textarea_sanitize( $input ) {
    return wp_kses_post( $input );
}

// Add Changes To Head
function rosetta_customizer_output(){
	// Colors: General
	$accent_color = get_theme_mod('accent_color', '#ed0e28');
	$body_bg = get_theme_mod('body_bg', '#ffffff');
	$body_color = get_theme_mod('body_color', '#444444');
	$headings_color = get_theme_mod('headings_color', '#111111');
	$buttons_bg = get_theme_mod('buttons_bg', '#111111');

	// Colors: Header
	$header_bg = get_theme_mod('header_bg', '#ffffff');
	$header_color = get_theme_mod('header_color', '#111111');
	$submenu_bg = get_theme_mod('submenu_bg', '#ffffff');
	$submenu_color = get_theme_mod('submenu_color', '#111111');

	// Colors: Box
	$box_bg = get_theme_mod('box_bg', '#f5f5f5');
	$box_color = get_theme_mod('box_color', '#111111');

	// Typography
	$headings_font = get_theme_mod('headings_font', 'Poppins');
	$headings_font_weight = get_theme_mod('headings_font_weight', '700');
	$body_font = get_theme_mod('body_font', 'Open Sans');
	$body_font_size = get_theme_mod('body_font_size', '16');



	//Output
	ob_start();

	if ( get_theme_mod('fixed_header') ) {
		echo '#fl-header { position: absolute; }';
	}

	if ( get_theme_mod('header_drawer') || !is_active_sidebar( 'rosetta_drawer' ) ) {
		echo '#fl-drawer-icon { display: none; }';
	}


	?>

	:root {
		--fl-body-font-size: <?php echo esc_html($body_font_size).'px'; ?>;
		--fl-body-font: <?php echo '"'.esc_html($body_font) .'", "Helvetica Neue",Helvetica,sans-serif'; ?>;
		--fl-headings-font: <?php echo '"'.esc_html($headings_font) .'", "Helvetica Neue",Helvetica,sans-serif;'; ?>;
		--fl-headings-font-weight: <?php echo esc_html($headings_font_weight); ?>;

	    --fl-accent-color: <?php echo esc_html($accent_color); ?>;

	    --fl-body-background: <?php echo esc_html($body_bg); ?>;
	    --fl-body-color: <?php echo esc_html($body_color); ?>;
	    --fl-headings-color: <?php echo esc_html($headings_color); ?>;

	    --fl-box-color: <?php echo esc_html($box_color); ?>;
	    --fl-box-background: <?php echo esc_html($box_bg); ?>;

	    --fl-button-background: <?php echo esc_html($buttons_bg); ?>;

	    --fl-header-background: <?php echo esc_html($header_bg); ?>;
	    --fl-header-color: <?php echo esc_html($header_color); ?>;
	    --fl-submenu-background: <?php echo esc_html($submenu_bg); ?>;
	    --fl-submenu-color: <?php echo esc_html($submenu_color); ?>;

	    --fl-input-background: #f5f5f5;
	}

	<?php if ( !get_theme_mod('dark_mode') ) { ?>

		[data-theme="dark"] {
			--fl-headings-font-weight: 400;

		    --fl-body-background: #181818;
		    --fl-body-color: #bbbbbb;
		    --fl-headings-color: #ffffff;
		    
		    --fl-box-color: #ffffff;
		    --fl-box-background: #222222;

		    --fl-button-background: #373737;

		    --fl-header-background: #181818;
		    --fl-header-color: #ffffff;
		    --fl-submenu-background: #181818;
		    --fl-submenu-color: #ffffff;

		    --fl-input-background: #000000;
		}
	<?php }
	 
	$style = ob_get_clean();
    return $style;
}