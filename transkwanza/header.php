<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Header -->
<header class="tk-header">
    <div class="tk-container">
        <nav class="tk-nav">
            <div class="tk-logo">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo home_url(); ?>">
                        <h1><?php bloginfo('name'); ?></h1>
                        <p class="tk-subtitle">Transferência Segura</p>
                    </a>
                <?php endif; ?>
            </div>

            <div class="tk-nav-menu">
                <a href="<?php echo home_url(); ?>">Início</a>
                <a href="<?php echo home_url('/paises'); ?>">Países Suportados</a>
                <a href="<?php echo home_url('/suporte'); ?>">Suporte</a>

                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo home_url('/dashboard'); ?>" class="tk-btn tk-btn-primary">Dashboard</a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>">Sair</a>
                <?php else : ?>
                    <a href="<?php echo wp_login_url(); ?>" class="tk-btn tk-btn-secondary">Login</a>
                    <a href="<?php echo wp_registration_url(); ?>" class="tk-btn tk-btn-primary">Cadastrar</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
