    <!-- Footer -->
    <footer id="fl-footer" class="fl-container">
        <?php echo wp_kses_post( get_theme_mod( 'copyrights', esc_html('© ') .esc_html( date('Y') ). ' ' .esc_html( get_bloginfo( 'name', 'display' ) ) .'. Designed & Developed by <a href="http://flatlayers.com" target="_blank">FlatLayers</a>' ) ); ?>
    </footer>
    
    <!-- End Document -->
    <?php wp_footer(); ?>
</body>
</html>