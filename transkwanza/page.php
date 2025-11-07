<?php
/**
 * Template Name: Página Padrão
 *
 * Template genérico para páginas simples
 */

get_header();
?>

<main class="tk-main tk-page">
    <div class="tk-container">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('tk-page-content'); ?>>
                    <header class="tk-page-header">
                        <h1><?php the_title(); ?></h1>
                    </header>

                    <div class="tk-page-body">
                        <?php the_content(); ?>
                    </div>
                </article>
                <?php
            endwhile;
        endif;
        ?>
    </div>
</main>

<?php
get_footer();
