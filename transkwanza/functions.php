<?php
/**
 * TransKwanza - WordPress Theme Functions
 *
 * Main theme initialization and integration file
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('TK_VERSION', '1.0.0');
define('TK_PATH', get_template_directory());
define('TK_URL', get_template_directory_uri());

// Autoload classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'TK_') === 0) {
        $file = TK_PATH . '/includes/classes/' . str_replace('TK_', '', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Load services
require_once TK_PATH . '/includes/services/ExchangeRateService.php';
require_once TK_PATH . '/includes/services/NotificationService.php';

// Load classes
require_once TK_PATH . '/includes/classes/Proposal.php';
require_once TK_PATH . '/includes/classes/Transaction.php';

/**
 * Theme Setup
 */
function tk_setup_theme() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

    // Register nav menus
    register_nav_menus([
        'primary' => __('Primary Menu', 'transkwanza'),
        'footer' => __('Footer Menu', 'transkwanza'),
    ]);
}
add_action('after_setup_theme', 'tk_setup_theme');

/**
 * Enqueue scripts and styles
 */
function tk_enqueue_assets() {
    // Styles
    wp_enqueue_style('tk-style', TK_URL . '/assets/css/style.css', [], TK_VERSION);

    // Scripts
    wp_enqueue_script('tk-calculator', TK_URL . '/assets/js/calculator.js', [], TK_VERSION, true);

    if (is_user_logged_in()) {
        wp_enqueue_script('tk-dashboard', TK_URL . '/assets/js/dashboard.js', [], TK_VERSION, true);
    }

    // Localize script
    wp_localize_script('tk-calculator', 'tkAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tk_nonce'),
        'isLoggedIn' => is_user_logged_in(),
        'loginUrl' => wp_login_url(get_permalink()),
        'dashboardUrl' => home_url('/dashboard')
    ]);
}
add_action('wp_enqueue_scripts', 'tk_enqueue_assets');

/**
 * Database Installation
 */
function tk_install_database() {
    global $wpdb;

    $sql_file = TK_PATH . '/database/schema.sql';
    if (!file_exists($sql_file)) {
        return;
    }

    $sql = file_get_contents($sql_file);

    // Replace wp_ prefix with actual prefix
    $sql = str_replace('wp_', $wpdb->prefix, $sql);

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Split queries and execute
    $queries = array_filter(explode(';', $sql));
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $wpdb->query($query);
        }
    }

    // Populate countries
    tk_populate_countries();

    update_option('tk_db_version', TK_VERSION);
}
register_activation_hook(__FILE__, 'tk_install_database');

/**
 * Populate countries table
 */
function tk_populate_countries() {
    global $wpdb;

    $countries = include TK_PATH . '/config/countries.php';
    $table = $wpdb->prefix . 'tk_countries';

    foreach ($countries as $code => $country) {
        $wpdb->replace($table, [
            'country_code' => $code,
            'name' => $country['name'],
            'name_en' => $country['name_en'],
            'name_es' => $country['name_es'],
            'currency_code' => $country['currency_code'],
            'currency_name' => $country['currency_name'],
            'currency_symbol' => $country['currency_symbol'],
            'flag_emoji' => $country['flag'],
            'payment_method' => $country['payment_method'],
            'payment_method_details' => json_encode($country['payment_method_details']),
            'status' => $country['status'],
            'regulations' => $country['regulations'],
            'locale' => $country['locale']
        ]);
    }
}

/**
 * AJAX: Get Countries
 */
function tk_ajax_get_countries() {
    $countries = include TK_PATH . '/config/countries.php';
    wp_send_json_success($countries);
}
add_action('wp_ajax_tk_get_countries', 'tk_ajax_get_countries');
add_action('wp_ajax_nopriv_tk_get_countries', 'tk_ajax_get_countries');

/**
 * AJAX: Convert Currency
 */
function tk_ajax_convert_currency() {
    $amount = floatval($_POST['amount'] ?? 0);
    $from = sanitize_text_field($_POST['from'] ?? '');
    $to = sanitize_text_field($_POST['to'] ?? '');

    if ($amount <= 0 || empty($from) || empty($to)) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    $exchange_service = new TK_ExchangeRateService();
    $conversion = $exchange_service->convert($amount, $from, $to, true);

    if ($conversion) {
        wp_send_json_success($conversion);
    } else {
        wp_send_json_error(['message' => 'Failed to get exchange rate']);
    }
}
add_action('wp_ajax_tk_convert_currency', 'tk_ajax_convert_currency');
add_action('wp_ajax_nopriv_tk_convert_currency', 'tk_ajax_convert_currency');

/**
 * AJAX: Get Available Proposals
 */
function tk_ajax_get_available_proposals() {
    check_ajax_referer('tk_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }

    $current_user_id = get_current_user_id();
    $tk_user = tk_get_or_create_tk_user($current_user_id);

    $filters = json_decode(stripslashes($_POST['filters'] ?? '{}'), true);

    $proposal = new TK_Proposal();
    $proposals = $proposal->getAvailableProposals($filters, $tk_user->id);

    wp_send_json_success($proposals);
}
add_action('wp_ajax_tk_get_available_proposals', 'tk_ajax_get_available_proposals');

/**
 * AJAX: Get My Proposals
 */
function tk_ajax_get_my_proposals() {
    check_ajax_referer('tk_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }

    $current_user_id = get_current_user_id();
    $tk_user = tk_get_or_create_tk_user($current_user_id);

    $proposal = new TK_Proposal();
    $proposals = $proposal->getUserProposals($tk_user->id);

    wp_send_json_success($proposals);
}
add_action('wp_ajax_tk_get_my_proposals', 'tk_ajax_get_my_proposals');

/**
 * AJAX: Get My Transactions
 */
function tk_ajax_get_my_transactions() {
    check_ajax_referer('tk_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }

    $current_user_id = get_current_user_id();
    $tk_user = tk_get_or_create_tk_user($current_user_id);

    $transaction_service = new TK_TransactionService();
    $transactions = $transaction_service->getUserTransactions($tk_user->id);

    wp_send_json_success($transactions);
}
add_action('wp_ajax_tk_get_my_transactions', 'tk_ajax_get_my_transactions');

/**
 * AJAX: Accept Proposal
 */
function tk_ajax_accept_proposal() {
    check_ajax_referer('tk_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }

    $proposal_id = intval($_POST['proposal_id'] ?? 0);
    $recipient_name = sanitize_text_field($_POST['recipient_name'] ?? '');
    $recipient_phone = sanitize_text_field($_POST['recipient_phone'] ?? '');
    $recipient_payment_details = sanitize_textarea_field($_POST['recipient_payment_details'] ?? '');

    if (!$proposal_id || !$recipient_name || !$recipient_phone) {
        wp_send_json_error(['message' => 'Missing required fields']);
    }

    $current_user_id = get_current_user_id();
    $tk_user = tk_get_or_create_tk_user($current_user_id);

    $recipient_data = [
        'recipient_name' => $recipient_name,
        'recipient_phone' => $recipient_phone,
        'recipient_payment_details' => $recipient_payment_details
    ];

    $proposal = new TK_Proposal();
    $transaction_id = $proposal->accept($proposal_id, $tk_user->id, $recipient_data);

    if ($transaction_id) {
        wp_send_json_success(['transaction_id' => $transaction_id]);
    } else {
        wp_send_json_error(['message' => 'Failed to accept proposal']);
    }
}
add_action('wp_ajax_tk_accept_proposal', 'tk_ajax_accept_proposal');

/**
 * AJAX: Cancel Proposal
 */
function tk_ajax_cancel_proposal() {
    check_ajax_referer('tk_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }

    $proposal_id = intval($_POST['proposal_id'] ?? 0);

    $current_user_id = get_current_user_id();
    $tk_user = tk_get_or_create_tk_user($current_user_id);

    $proposal = new TK_Proposal();
    $result = $proposal->delete($proposal_id, $tk_user->id);

    if ($result) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Failed to cancel proposal']);
    }
}
add_action('wp_ajax_tk_cancel_proposal', 'tk_ajax_cancel_proposal');

/**
 * AJAX: Get Notifications
 */
function tk_ajax_get_notifications() {
    check_ajax_referer('tk_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }

    $current_user_id = get_current_user_id();
    $tk_user = tk_get_or_create_tk_user($current_user_id);

    $notification_service = new TK_NotificationService();
    $notifications = $notification_service->getUserNotifications($tk_user->id, false, 20);
    $unread_count = $notification_service->getUnreadCount($tk_user->id);

    wp_send_json_success([
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
}
add_action('wp_ajax_tk_get_notifications', 'tk_ajax_get_notifications');

/**
 * Get or create TransKwanza user
 */
function tk_get_or_create_tk_user($wp_user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'tk_users';

    $tk_user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE wp_user_id = %d",
        $wp_user_id
    ));

    if (!$tk_user) {
        $wp_user = get_userdata($wp_user_id);

        $wpdb->insert($table, [
            'wp_user_id' => $wp_user_id,
            'full_name' => $wp_user->display_name,
            'account_status' => 'active',
            'created_at' => current_time('mysql')
        ]);

        $tk_user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE wp_user_id = %d",
            $wp_user_id
        ));
    }

    return $tk_user;
}

/**
 * Cron Jobs
 */
function tk_setup_cron() {
    if (!wp_next_scheduled('tk_refresh_exchange_rates')) {
        wp_schedule_event(time(), 'hourly', 'tk_refresh_exchange_rates');
    }

    if (!wp_next_scheduled('tk_expire_proposals')) {
        wp_schedule_event(time(), 'hourly', 'tk_expire_proposals');
    }

    if (!wp_next_scheduled('tk_clean_notifications')) {
        wp_schedule_event(time(), 'daily', 'tk_clean_notifications');
    }
}
add_action('wp', 'tk_setup_cron');

// Cron callbacks
add_action('tk_refresh_exchange_rates', function() {
    $exchange_service = new TK_ExchangeRateService();
    $exchange_service->refreshExpiredRates();
});

add_action('tk_expire_proposals', function() {
    $proposal = new TK_Proposal();
    $proposal->autoExpire();
});

add_action('tk_clean_notifications', function() {
    $notification_service = new TK_NotificationService();
    $notification_service->cleanOldNotifications();
});

/**
 * Custom login URL
 */
function tk_custom_login_url() {
    return home_url('/login');
}

/**
 * Redirect after login
 */
function tk_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        return home_url('/dashboard');
    }
    return $redirect_to;
}
add_filter('login_redirect', 'tk_login_redirect', 10, 3);
