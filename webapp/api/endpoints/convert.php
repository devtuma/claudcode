<?php
/**
 * Currency Conversion Endpoint
 * POST /api/convert
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['from']) || empty($data['to']) || !isset($data['amount'])) {
    jsonResponse(['error' => 'Fields "from", "to", and "amount" are required'], 400);
}

$from = strtoupper(sanitize($data['from']));
$to = strtoupper(sanitize($data['to']));
$amount = floatval($data['amount']);

if ($amount <= 0) {
    jsonResponse(['error' => 'Amount must be greater than 0'], 400);
}

// Get exchange rate
$rate = getExchangeRate($from, $to);

// Calculate conversion
$converted_amount = $amount * $rate;
$fee_amount = $amount * PLATFORM_FEE;

// Get country info
$db = getDB();
$stmt = $db->prepare("
    SELECT c1.name as from_country, c1.currency_name as from_currency_name, c1.currency_symbol as from_symbol,
           c2.name as to_country, c2.currency_name as to_currency_name, c2.currency_symbol as to_symbol
    FROM countries c1, countries c2
    WHERE c1.currency_code = :from AND c2.currency_code = :to
    LIMIT 1
");
$stmt->execute(['from' => $from, 'to' => $to]);
$info = $stmt->fetch();

jsonResponse([
    'success' => true,
    'from_currency' => $from,
    'to_currency' => $to,
    'amount' => $amount,
    'converted_amount' => round($converted_amount, 2),
    'exchange_rate' => $rate,
    'fee_amount' => round($fee_amount, 2),
    'fee_percentage' => PLATFORM_FEE * 100,
    'from_symbol' => $info['from_symbol'] ?? $from,
    'to_symbol' => $info['to_symbol'] ?? $to,
    'from_currency_name' => $info['from_currency_name'] ?? $from,
    'to_currency_name' => $info['to_currency_name'] ?? $to,
    'timestamp' => date('c')
]);
