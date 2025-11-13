<?php
/**
 * Exchange Rate Endpoint
 * GET /api/exchange-rate?from=BRL&to=EUR
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Validate parameters
if (empty($_GET['from']) || empty($_GET['to'])) {
    jsonResponse(['error' => 'Parameters "from" and "to" are required'], 400);
}

$from = strtoupper(sanitize($_GET['from']));
$to = strtoupper(sanitize($_GET['to']));

// Validate currency codes
$db = getDB();
$stmt = $db->prepare("SELECT COUNT(*) as count FROM countries WHERE currency_code IN (:from, :to) AND status = 'active'");
$stmt->execute(['from' => $from, 'to' => $to]);
$result = $stmt->fetch();

if ($result['count'] < 2 && $from !== $to) {
    jsonResponse(['error' => 'Invalid currency codes'], 400);
}

// Get exchange rate
$rate = getExchangeRate($from, $to);

jsonResponse([
    'success' => true,
    'from_currency' => $from,
    'to_currency' => $to,
    'rate' => $rate,
    'platform_fee_percentage' => PLATFORM_FEE * 100,
    'timestamp' => date('c')
]);
