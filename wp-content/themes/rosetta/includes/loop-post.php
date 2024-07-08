<?php
// Loop Post Content
// Standard & Grid Loop Styles

$thumbs = true;
$layout = $args['layout'];
$thumb_size = $args['thumb_size'];

$letter = false;
$post_content = true;
$class = '';

// Hide Thumbnails
if ( get_theme_mod('hide_thumbs') ) { $thumbs = false; }

if ( strpos($layout, 'box') !== false ) {
    $letter = true;
    $post_content = false;
}

if ( has_post_thumbnail() && $thumbs == true ) {
    $class = 'img-bg';
}

?>
<article <?php post_class('fl-post '.$class); ?>>

    <?php if ( $letter == true ) { ?>   
        <span class="letter"><?php echo esc_html( mb_substr(get_the_title(), 0, 1, "UTF-8") ); ?></span>
    <?php } ?>   

    <div class="post-header">
        <h2 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2> 
        <div class="meta-wrap">
            <div class="fl-category"><?php echo the_category(' '); ?></div>
            <div class="fl-meta">
                <?php
                if ( !get_theme_mod( 'hide_meta_author' ) ) { ?>
                <span class="meta author">
                    <i><?php echo esc_html_e('By', 'rosetta'); ?></i>
                    <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" title="<?php echo esc_attr( get_the_author() ); ?>">
                        <?php the_author(); ?>                       
                    </a>
                </span>
                <?php }

                if ( !get_theme_mod( 'hide_meta_date' ) ) { ?>
                <span class="meta date"><?php echo get_the_date(); ?></span>
                <?php } 

                if ( !get_theme_mod( 'hide_meta_comments' ) ) {
                    if ( comments_open() || have_comments() ) { ?>
                        <span class="meta comment">
                            <a href="<?php comments_link(); ?>" class="comments"><?php comments_number( esc_html__('No Comments', 'rosetta'), esc_html__('1 Comment', 'rosetta'), esc_html__( '% Comments', 'rosetta') ); ?></a>
                        </span>
                    <?php }
                }
                if ( !get_theme_mod( 'hide_meta_read' ) ) { ?>
                <span class="meta time"><?php echo rosetta_reading_time(get_the_ID()); ?></span>
                <?php } ?>
            </div>
        </div>
    </div>

    <?php if ( has_post_thumbnail() && $thumbs == true ) { ?>
        <div class="fl-picture">
            <a href="<?php the_permalink(); ?>">
                <?php
                the_post_thumbnail( $thumb_size );
                echo esc_html( get_the_title() );
                ?>
            </a>
        </div>
    <?php }

    if ( $post_content == true ) { ?>
        <div class="fl-excerpt">
            <?php
            the_excerpt();

            // Read More
            echo '<a class="fl-read-more" href="' . get_permalink() . '"><span>'. ( ( has_excerpt() ) ? esc_html__( 'Read Article', 'rosetta' ) : esc_html__( 'Continue Reading', 'rosetta' ) ) .'</span></a>';
            ?>
        </div>
    <?php } ?>   

</article>