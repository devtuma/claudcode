<?php
/**
 * TransKwanza - Index Template
 *
 * Este é o template padrão usado quando nenhum template específico é encontrado.
 * Redireciona para a home se não houver conteúdo específico.
 */

get_header();
?>

<main class="tk-main">
    <div class="tk-container">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('tk-article'); ?>>
                    <header class="tk-article-header">
                        <h1 class="tk-article-title"><?php the_title(); ?></h1>
                        <div class="tk-article-meta">
                            <span class="tk-date"><?php echo get_the_date(); ?></span>
                            <span class="tk-author"><?php echo get_the_author(); ?></span>
                        </div>
                    </header>

                    <div class="tk-article-content">
                        <?php the_content(); ?>
                    </div>
                </article>
                <?php
            endwhile;
        else :
            ?>
            <div class="tk-no-content">
                <h2>Nenhum conteúdo encontrado</h2>
                <p>Desculpe, não encontramos o conteúdo que você está procurando.</p>
                <a href="<?php echo home_url(); ?>" class="tk-btn tk-btn-primary">Voltar para Home</a>
            </div>
            <?php
        endif;
        ?>
    </div>
</main>

<?php
get_footer();
