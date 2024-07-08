<?php get_header(); ?>

<div class="fl-container fl-box">
	<span class="letter">Oops!</span>
	<div class="head">
	    <h1 class="title">
	        <?php esc_html_e( 'Error 404', 'rosetta' ); ?>
	    </h1>
	</div>
	<div class="info">
    </div>
</div>

<div id="fl-content" class="fl-container">
	<span class="fl-stories"><?php echo esc_html_e('Search', 'rosetta'); ?></span>
	<div class="fl-excerpt">
		<p><?php esc_html_e( 'It seems we can not find what you are looking for. Perhaps searching can help.', 'rosetta' ); ?></p>		
		<?php get_template_part( 'searchform' ); ?>
	</div>
</div>

<?php get_footer(); ?>