<?php
/**
 * TransKwanza API - Router
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/webapp/api/', '', $path);
$path = trim($path, '/');

// Get request body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Route the request
try {
    switch ($path) {
        // Auth endpoints
        case 'auth/register':
            require __DIR__ . '/endpoints/register.php';
            break;

        case 'auth/login':
            require __DIR__ . '/endpoints/login.php';
            break;

        case 'auth/logout':
            require __DIR__ . '/endpoints/logout.php';
            break;

        case 'auth/me':
            require __DIR__ . '/endpoints/me.php';
            break;

        // Countries
        case 'countries':
            require __DIR__ . '/endpoints/countries.php';
            break;

        // Exchange rates
        case 'exchange/rate':
            require __DIR__ . '/endpoints/exchange_rate.php';
            break;

        case 'exchange/convert':
            require __DIR__ . '/endpoints/convert.php';
            break;

        // Proposals
        case 'proposals':
            require __DIR__ . '/endpoints/proposals.php';
            break;

        case (preg_match('/^proposals\/(\d+)$/', $path, $matches) ? true : false):
            $_GET['id'] = $matches[1];
            require __DIR__ . '/endpoints/proposal.php';
            break;

        // Transactions
        case 'transactions':
            require __DIR__ . '/endpoints/transactions.php';
            break;

        // Notifications
        case 'notifications':
            require __DIR__ . '/endpoints/notifications.php';
            break;

        default:
            jsonResponse(['error' => 'Endpoint not found'], 404);
    }

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
