<?php if ( !get_theme_mod( 'hide_author_box' ) && get_the_author_meta( 'description' ) ) {  ?>
    <div id="fl-author" class="fl-box fl-container">
        <div class="avatar">
            <?php echo get_avatar( get_the_author_meta( 'user_email' ), 108 ); ?>
        </div>
        <div class="info">
            <span class="box-meta"><?php esc_html_e( 'Written By', 'rosetta' ); ?></span>
            <h3 class="title"><?php the_author_posts_link(); ?></h3>
            <p><?php the_author_meta( 'description' ); ?></p>
            <div class="fl-social-icons">

                <?php if ( get_the_author_meta( 'url' ) ) { ?>
                    <a href="<?php the_author_meta( 'url' ); ?>" target="_blank" rel="nofollow">
                        <i class="bi bi-globe"></i>
                    </a>
                <?php }

                if ( get_the_author_meta( 'twitter' ) ) { ?>
                    <a href="<?php the_author_meta( 'twitter' ); ?>" target="_blank" rel="nofollow">
                        <i class="bi bi-twitter"></i>
                    </a>
                <?php }

                if ( get_the_author_meta( 'facebook' ) ) { ?>
                    <a href="<?php the_author_meta( 'facebook' ); ?>" target="_blank" rel="nofollow">
                        <i class="bi bi-facebook"></i>
                    </a>
                <?php }

                if ( get_the_author_meta( 'instagram' ) ) { ?>
                    <a href="<?php the_author_meta( 'instagram' ); ?>" target="_blank" rel="nofollow">
                        <i class="bi bi-instagram"></i>
                    </a>
                <?php }

                if ( get_the_author_meta( 'youtube' ) ) { ?>
                    <a href="<?php the_author_meta( 'youtube' ); ?>" target="_blank" rel="nofollow">
                        <i class="bi bi-youtube"></i>
                    </a>
                <?php }

                if ( get_the_author_meta( 'linkedin' ) ) { ?>
                    <a href="<?php the_author_meta( 'linkedin' ); ?>" target="_blank" rel="nofollow">
                        <i class="bi bi-linkedin"></i>
                    </a>
                <?php }

                if ( get_the_author_meta( 'pinterest' ) ) { ?>
                    <a href="<?php the_author_meta( 'pinterest' ); ?>" target="_blank" rel="nofollow">
                        <i class="bi bi-pinterest"></i>
                    </a>
                <?php }

                if ( get_the_author_meta( 'reddit' ) ) { ?>
                    <a href="<?php the_author_meta( 'reddit' ); ?>" target="_blank" rel="nofollow">
                        <i class="bi bi-reddit"></i>
                    </a>
                <?php }

                ?>

            </div>
        </div>
        <div class="info">
        <a class="fl-read-more" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php echo esc_html_e('View All Articles', 'rosetta'); ?></a> 
        </div>
    </div>
<?php } ?>