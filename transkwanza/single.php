<?php
/**
 * Template Name: Single Post
 *
 * Template para posts individuais
 */

get_header();
?>

<main class="tk-main tk-single">
    <div class="tk-container">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('tk-post-content'); ?>>
                    <header class="tk-post-header">
                        <h1><?php the_title(); ?></h1>
                        <div class="tk-post-meta">
                            <span class="tk-date">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                    <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <?php echo get_the_date(); ?>
                            </span>
                            <span class="tk-author">
                                Por <?php echo get_the_author(); ?>
                            </span>
                        </div>
                    </header>

                    <?php if (has_post_thumbnail()) : ?>
                        <div class="tk-post-thumbnail">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="tk-post-body">
                        <?php the_content(); ?>
                    </div>

                    <footer class="tk-post-footer">
                        <?php
                        // Navigation between posts
                        the_post_navigation([
                            'prev_text' => '← %title',
                            'next_text' => '%title →',
                        ]);
                        ?>
                    </footer>
                </article>

                <?php
                // Comments
                if (comments_open() || get_comments_number()) :
                    comments_template();
                endif;
                ?>
                <?php
            endwhile;
        endif;
        ?>
    </div>
</main>

<?php
get_footer();
