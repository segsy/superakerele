<form method="get" class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <input type="text" value placeholder="<?php esc_attr_e( 'Search...', 'rosetta' ); ?>" name="s">
    <button type="submit">
	    <span><?php esc_html_e( 'Search', 'rosetta' ); ?></span>
    </button>
</form>