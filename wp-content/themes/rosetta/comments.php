<?php if ( !comments_open() ) {

    if ( have_comments() ) { ?>
        <div class="fl-container">
            <a href="#" class="fl-comments-toggle">
                <?php comments_number(esc_html__('No Comments', 'rosetta'), esc_html__('1 Comment', 'rosetta'), esc_html__( '% Comments', 'rosetta') ); ?>                
            </a>

            <div id="comments" class="fl-comments">
                <ol class="fl-comment-list">
                    <?php
                    wp_list_comments(
                        array(
                            'avatar_size' => 60,
                            'style'       => 'ol',
                            'short_ping'  => true,
                        )
                    );
                    ?>
                </ol>

                <?php the_comments_pagination(); ?>

                <p class="fl-no-comments"><?php esc_html_e( 'Comments are closed.', 'rosetta' ); ?></p>

            </div>
        </div>
        
    <?php }

} else { ?>
    <div class="fl-container">
        <a href="#" class="fl-comments-toggle">
            <?php comments_number(esc_html__('Leave a Reply', 'rosetta'), esc_html__('1 Comment', 'rosetta'), esc_html__( '% Comments', 'rosetta') ); ?>
        </a>

        <div id="comments" class="fl-comments">
            <?php if ( have_comments() ) { ?>
                <ol class="fl-comment-list">
                    <?php
                    wp_list_comments(
                        array(
                            'avatar_size' => 60,
                            'style'       => 'ol',
                            'short_ping'  => true,
                        )
                    );
                    ?>
                </ol>

            <?php }

            the_comments_pagination();

            comment_form(
                array(
                    'logged_in_as'       => null,
                    'title_reply'        => esc_html__( 'Leave a Reply', 'rosetta' ),
                    'title_reply_before' => '<h4 id="fl-reply-title">',
                    'title_reply_after'  => '</h4>',
                )
            ); ?>

        </div>
    </div>

<?php } ?>