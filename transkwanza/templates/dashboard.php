<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php
// Get or create TK user
$current_user = wp_get_current_user();
$tk_user = tk_get_or_create_tk_user($current_user->ID);
?>

<!-- Header -->
<header class="tk-header tk-header-dashboard">
    <div class="tk-container">
        <nav class="tk-nav">
            <div class="tk-logo">
                <h1>TransKwanza</h1>
            </div>

            <div class="tk-nav-menu">
                <a href="<?php echo home_url(); ?>">Início</a>
                <a href="<?php echo home_url('/suporte'); ?>">Suporte</a>

                <div class="tk-notifications">
                    <button class="tk-notification-btn">
                        🔔
                        <span class="tk-notification-badge" id="tk-notification-badge">0</span>
                    </button>
                </div>

                <div class="tk-user-menu">
                    <span>Olá, <?php echo esc_html($tk_user->full_name); ?></span>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="tk-btn tk-btn-secondary">Sair</a>
                </div>
            </div>
        </nav>
    </div>
</header>

<!-- Dashboard -->
<main class="tk-dashboard" id="tk-dashboard">
    <div class="tk-container">
        <!-- Dashboard Header -->
        <div class="tk-dashboard-header">
            <div>
                <h1>Dashboard</h1>
                <p class="tk-user-welcome">Bem-vindo de volta, <?php echo esc_html($tk_user->full_name); ?>!</p>
            </div>

            <div class="tk-user-stats-summary">
                <div class="tk-stat-box">
                    <strong><?php echo $tk_user->total_transactions; ?></strong>
                    <span>Transações</span>
                </div>
                <div class="tk-stat-box">
                    <strong>⭐ <?php echo number_format($tk_user->rating, 2); ?></strong>
                    <span>Avaliação</span>
                </div>
                <div class="tk-stat-box">
                    <strong><?php echo ucfirst($tk_user->account_status); ?></strong>
                    <span>Status</span>
                </div>
            </div>
        </div>

        <!-- Mobile Tab Select -->
        <select id="tk-mobile-tab-select" class="tk-mobile-tab-select">
            <option value="proposals-available">Propostas Disponíveis</option>
            <option value="calculator">Calculadora</option>
            <option value="my-proposals">Minhas Propostas</option>
            <option value="my-transactions">Minhas Transações</option>
        </select>

        <!-- Desktop Tabs -->
        <div class="tk-tabs">
            <button class="tk-tab-btn active" data-tab="proposals-available">
                Propostas Disponíveis
            </button>
            <button class="tk-tab-btn" data-tab="calculator">
                Calculadora
            </button>
            <button class="tk-tab-btn" data-tab="my-proposals">
                Minhas Propostas
            </button>
            <button class="tk-tab-btn" data-tab="my-transactions">
                Minhas Transações
            </button>
        </div>

        <!-- Tab: Propostas Disponíveis -->
        <div id="tk-tab-proposals-available" class="tk-tab-content active">
            <div class="tk-tab-header">
                <h2>Propostas Disponíveis</h2>
                <p>Encontre propostas de outros usuários para fazer matching</p>
            </div>

            <!-- Filters -->
            <div class="tk-filters">
                <select id="tk-filter-from-currency" class="tk-filter-select">
                    <option value="">De (Todas Moedas)</option>
                    <?php
                    $countries = include get_template_directory() . '/config/countries.php';
                    $currencies = array_unique(array_column($countries, 'currency_code'));
                    foreach ($currencies as $currency):
                    ?>
                    <option value="<?php echo $currency; ?>"><?php echo $currency; ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="tk-filter-to-currency" class="tk-filter-select">
                    <option value="">Para (Todas Moedas)</option>
                    <?php foreach ($currencies as $currency): ?>
                    <option value="<?php echo $currency; ?>"><?php echo $currency; ?></option>
                    <?php endforeach; ?>
                </select>

                <button class="tk-btn tk-btn-primary" id="tk-apply-filters">Filtrar</button>
            </div>

            <div id="tk-available-proposals-list">
                <!-- Dynamic content loaded by JavaScript -->
            </div>
        </div>

        <!-- Tab: Calculadora -->
        <div id="tk-tab-calculator" class="tk-tab-content">
            <div class="tk-tab-header">
                <h2>Calculadora de Câmbio</h2>
                <p>Calcule quanto você vai enviar e receber</p>
            </div>

            <div id="tk-calculator-widget"></div>
        </div>

        <!-- Tab: Minhas Propostas -->
        <div id="tk-tab-my-proposals" class="tk-tab-content">
            <div class="tk-tab-header">
                <h2>Minhas Propostas</h2>
                <p>Gerencie suas propostas ativas</p>

                <button class="tk-btn tk-btn-primary" onclick="document.querySelector('[data-tab=calculator]').click()">
                    + Nova Proposta
                </button>
            </div>

            <div id="tk-my-proposals-list">
                <!-- Dynamic content loaded by JavaScript -->
            </div>
        </div>

        <!-- Tab: Minhas Transações -->
        <div id="tk-tab-my-transactions" class="tk-tab-content">
            <div class="tk-tab-header">
                <h2>Minhas Transações</h2>
                <p>Acompanhe o status das suas transações</p>
            </div>

            <div id="tk-my-transactions-list">
                <!-- Dynamic content loaded by JavaScript -->
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="tk-footer">
    <div class="tk-container">
        <div class="tk-footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> TransKwanza. Todos os direitos reservados.</p>
            <p>Suporte: <a href="https://wa.me/5511934363623">WhatsApp +55 11 93436-3623</a></p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
