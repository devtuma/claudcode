<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?> - <?php bloginfo('description'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<!-- Header -->
<header class="tk-header">
    <div class="tk-container">
        <nav class="tk-nav">
            <div class="tk-logo">
                <h1>TransKwanza</h1>
                <p class="tk-subtitle">Transferência Segura</p>
            </div>

            <div class="tk-nav-menu">
                <a href="<?php echo home_url(); ?>">Início</a>
                <a href="<?php echo home_url('/paises'); ?>">Países Suportados</a>
                <a href="<?php echo home_url('/suporte'); ?>">Suporte</a>

                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo home_url('/dashboard'); ?>" class="tk-btn tk-btn-primary">Dashboard</a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>">Sair</a>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(); ?>" class="tk-btn tk-btn-secondary">Login</a>
                    <a href="<?php echo wp_registration_url(); ?>" class="tk-btn tk-btn-primary">Cadastrar</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="tk-hero">
    <div class="tk-container">
        <div class="tk-hero-content">
            <h1>Plataforma de remessas cruzadas Segura</h1>
            <p class="tk-hero-subtitle">
                Envie dinheiro internacionalmente de forma segura e econômica.<br>
                Conectamos pessoas que querem enviar valores em direções opostas.
            </p>

            <div class="tk-hero-features">
                <div class="tk-feature">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <h3>Taxa de 3%</h3>
                    <p>Transparente e competitiva</p>
                </div>

                <div class="tk-feature">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <h3>9 Países</h3>
                    <p>Operação multinacional</p>
                </div>

                <div class="tk-feature">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2V12L17 7" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <h3>Rápido e Seguro</h3>
                    <p>Transações protegidas</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Calculator Section -->
<section class="tk-section">
    <div class="tk-container">
        <div id="tk-calculator-widget"></div>
    </div>
</section>

<!-- How It Works -->
<section class="tk-section tk-how-it-works">
    <div class="tk-container">
        <h2 class="tk-text-center">Como Funciona</h2>

        <div class="tk-steps">
            <div class="tk-step">
                <div class="tk-step-number">1</div>
                <h3>Calcule e Crie Proposta</h3>
                <p>Use nossa calculadora para ver quanto você vai enviar e receber. Crie sua proposta informando os dados.</p>
            </div>

            <div class="tk-step">
                <div class="tk-step-number">2</div>
                <h3>Sistema Encontra Parceria</h3>
                <p>Nosso algoritmo inteligente busca alguém querendo enviar na direção oposta (matching automático).</p>
            </div>

            <div class="tk-step">
                <div class="tk-step-number">3</div>
                <h3>Ambos Depositam Localmente</h3>
                <p>Você deposita na conta local da TransKwanza no seu país, e o parceiro faz o mesmo no país dele.</p>
            </div>

            <div class="tk-step">
                <div class="tk-step-number">4</div>
                <h3>Receba o Dinheiro</h3>
                <p>Após confirmação dos dois pagamentos, transferimos localmente para os destinatários.</p>
            </div>
        </div>
    </div>
</section>

<!-- Supported Countries -->
<section class="tk-section tk-countries-preview">
    <div class="tk-container">
        <h2 class="tk-text-center">Países Suportados</h2>
        <div class="tk-countries-grid">
            <?php
            $countries = include get_template_directory() . '/config/countries.php';
            foreach ($countries as $country):
            ?>
            <div class="tk-country-card">
                <span class="tk-country-flag"><?php echo $country['flag']; ?></span>
                <h4><?php echo $country['name']; ?></h4>
                <p class="tk-currency"><?php echo $country['currency_name']; ?> (<?php echo $country['currency_code']; ?>)</p>
                <p class="tk-payment"><?php echo $country['payment_method']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="tk-text-center tk-mt-lg">
            <a href="<?php echo home_url('/paises'); ?>" class="tk-btn tk-btn-primary">Ver Detalhes dos Países</a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="tk-section tk-cta">
    <div class="tk-container">
        <div class="tk-cta-content">
            <h2>Pronto para Começar?</h2>
            <p>Crie sua conta gratuitamente e comece a enviar dinheiro com segurança.</p>

            <?php if (!is_user_logged_in()): ?>
                <a href="<?php echo wp_registration_url(); ?>" class="tk-btn tk-btn-primary tk-btn-large">
                    Criar Conta Grátis
                </a>
            <?php else: ?>
                <a href="<?php echo home_url('/dashboard'); ?>" class="tk-btn tk-btn-primary tk-btn-large">
                    Ir para Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="tk-footer">
    <div class="tk-container">
        <div class="tk-footer-content">
            <div class="tk-footer-section">
                <h4>TransKwanza</h4>
                <p>Plataforma de remessas cruzadas P2P internacional</p>
            </div>

            <div class="tk-footer-section">
                <h4>Links Úteis</h4>
                <ul>
                    <li><a href="<?php echo home_url('/paises'); ?>">Países Suportados</a></li>
                    <li><a href="<?php echo home_url('/suporte'); ?>">Suporte</a></li>
                    <li><a href="<?php echo home_url('/termos'); ?>">Termos de Uso</a></li>
                    <li><a href="<?php echo home_url('/privacidade'); ?>">Privacidade</a></li>
                </ul>
            </div>

            <div class="tk-footer-section">
                <h4>Suporte</h4>
                <p>WhatsApp: <a href="https://wa.me/5511934363623">+55 11 93436-3623</a></p>
                <p>Email: suporte@transkwanza.com</p>
            </div>
        </div>

        <div class="tk-footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> TransKwanza. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
