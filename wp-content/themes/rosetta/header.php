<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>

	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<!-- Mobile Specific Metas -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

	<?php wp_head(); ?>

</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>

	<header id="fl-header">
		<div class="fl-container">
			<div id="fl-drawer-icon" title="<?php esc_attr_e('More', 'rosetta'); ?>">
				<button class="icon">
					<span><?php esc_html_e( 'More', 'rosetta' ); ?></span>
				</button>
				<span class="text"><?php esc_html_e( 'More', 'rosetta' ); ?></span>
			</div>
			<div id="fl-logo">			    
			    <?php if ( get_theme_mod( 'logo', FLATLAYERS_THEME_PATH . '/img/logo.png' ) ) { ?>
			        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"  title="<?php echo esc_attr( get_bloginfo('name') ); ?>" rel="home">

			        	<img class="light-logo" src="<?php echo esc_url( get_theme_mod( 'logo', FLATLAYERS_THEME_PATH . '/img/logo.png' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?>">

			        	<?php if ( !get_theme_mod('dark_mode') && get_theme_mod( 'logo_dark', FLATLAYERS_THEME_PATH . '/img/logo-dark.png' ) ) { ?>
			        		<img class="dark-logo" src="<?php echo esc_url( get_theme_mod('logo_dark', FLATLAYERS_THEME_PATH . '/img/logo-dark.png') ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?>">
			        	<?php } ?>

			        </a>
				<?php } else {
			        if ( is_home() ) {
						echo '<h1><a href="'. esc_url(home_url('/')).'" title="'. esc_attr( get_bloginfo('name') ) .'" rel="Home">'. esc_html( get_bloginfo('name') ) .'</a></h1>';
					} else {
						echo '<p><a href="'. esc_url(home_url('/')).'" title="'. esc_attr( get_bloginfo('name') ) .'" rel="Home">'. esc_html( get_bloginfo('name') ) .'</a></p>';
					}
				}
				echo '<span>'. esc_html( get_bloginfo('description') ) .'</span>';
				?>
			</div>

			<div class="header-menu">

				<?php if ( !get_theme_mod('header_social') ) { 
					get_template_part( 'includes/social-icons' );
				}

				if ( class_exists( 'WooCommerce' ) ) {

					if ( !get_theme_mod( 'header_account' ) ) { ?>
				        <a class="my-account" href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="<?php esc_attr_e('My Account','rosetta'); ?>">
				        	<i class="bi bi-person-circle"></i>
				        </a>
				    <?php }

					if ( !get_theme_mod( 'header_cart' ) ) { ?>
			            <a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'rosetta' ); ?>">
				            <i class="bi bi-basket"></i>
				            <?php if ( WC()->cart->get_cart_contents_count() != 0 ) { ?>
					            <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
				            <?php } ?>
				        </a>
				    <?php }
				}

			    if ( !get_theme_mod( 'dark_mode' ) ) { ?>
				    <label class="fl-theme-switch" for="fl-darkmode" title="<?php esc_attr_e('Change Color Mode', 'rosetta'); ?>">
				        <input type="checkbox" id="fl-darkmode">
				        <i class="bi bi-moon"></i>
				        <i class="bi bi-sun"></i>
					</label>
				<?php }

				if ( !get_theme_mod('header_search') ) { ?>
					<div id="fl-topsearch" title="<?php esc_attr_e('Search', 'rosetta'); ?>">
						<i class="bi bi-search"></i>
						<i class="bi bi-x-lg"></i>
					</div>
				<?php }

				if ( has_nav_menu( 'main-menu' ) ) { ?>
					<div id="fl-topmenu">
						<?php  wp_nav_menu( array( 'theme_location' => 'main-menu' ) ); ?>
					</div>
				<?php } ?>

			</div>
		</div>
	</header>

	<div class="fl-header-replace"></div>

	<div id="fl-drawer">
		<div class="mobile-content">
			<?php if ( has_nav_menu( 'main-menu' ) ) { ?>
				<div class="fl-widget mobile-menu">
					<?php  wp_nav_menu( array( 'theme_location' => 'main-menu' ) ); ?>
				</div>
			<?php } 

			if ( !get_theme_mod('header_social') ) { 
				echo '<div class="fl-widget mobile-socials">';
				if ( get_theme_mod('twitter') || get_theme_mod('facebook') || get_theme_mod('instagram') || get_theme_mod('youtube') || get_theme_mod('linkedin') || get_theme_mod('reddit') || get_theme_mod('vimeo') ) {		
					echo '<h3 class="fl-widget-title">'. esc_html__('Follow', 'rosetta') .'</h3>';
				}
				get_template_part( 'includes/social-icons' );
				echo '</div>';
			} ?>
		</div>

		<?php if ( is_active_sidebar( 'rosetta_drawer' ) ) {
			dynamic_sidebar('rosetta_drawer');
		} ?>

	</div>

	<div id="fl-overlay">
		<?php if ( !get_theme_mod('header_search') ) { ?>
		<div class="fl-container">
			<div class="overlay-search">
				<?php get_template_part( 'searchform' ); ?>
				<div class="cats">
					<?php
					$cats = get_categories( array(
						'order'   => 'DESC',
						'orderby' => 'count',
						'number' => 5,
						'hide_empty'   => true,
					));
					foreach ($cats as $cat) {
                        echo '<a class="cat-link" href="'. get_category_link($cat->term_id) .'">'. $cat->name .'</a>';
                    }
					?>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>