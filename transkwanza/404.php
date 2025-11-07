<?php
/**
 * Template Name: 404 - Página Não Encontrada
 *
 * Template para páginas não encontradas
 */

get_header();
?>

<main class="tk-main tk-404">
    <div class="tk-container">
        <div class="tk-404-content">
            <div class="tk-404-icon">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 8V12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="12" cy="16" r="1" fill="currentColor"/>
                </svg>
            </div>

            <h1>404 - Página Não Encontrada</h1>
            <p>Desculpe, a página que você está procurando não existe.</p>

            <div class="tk-404-actions">
                <a href="<?php echo home_url(); ?>" class="tk-btn tk-btn-primary">
                    Voltar para Home
                </a>
                <a href="<?php echo home_url('/dashboard'); ?>" class="tk-btn tk-btn-secondary">
                    Ir para Dashboard
                </a>
            </div>

            <div class="tk-404-search">
                <h3>Tente buscar:</h3>
                <?php get_search_form(); ?>
            </div>
        </div>
    </div>
</main>

<style>
.tk-404 {
    padding: 80px 0;
    text-align: center;
}

.tk-404-content {
    max-width: 600px;
    margin: 0 auto;
}

.tk-404-icon {
    color: var(--tk-primary);
    margin-bottom: 30px;
}

.tk-404 h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
}

.tk-404 p {
    font-size: 1.2rem;
    color: var(--tk-text-secondary);
    margin-bottom: 40px;
}

.tk-404-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-bottom: 60px;
}

.tk-404-search {
    background: var(--tk-bg-card);
    padding: 30px;
    border-radius: var(--tk-radius-lg);
}

@media (max-width: 768px) {
    .tk-404-actions {
        flex-direction: column;
    }
}
</style>

<?php
get_footer();
