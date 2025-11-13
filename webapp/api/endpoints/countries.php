<?php
/**
 * Countries Endpoint
 * GET /api/countries
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$db = getDB();

// Get all active countries
$stmt = $db->query("
    SELECT
        id, country_code, name, currency_code, currency_name,
        currency_symbol, flag_emoji, payment_method, status
    FROM countries
    WHERE status = 'active'
    ORDER BY name ASC
");

$countries = $stmt->fetchAll();

// Group by currency for easier frontend use
$by_currency = [];
foreach ($countries as $country) {
    $currency = $country['currency_code'];
    if (!isset($by_currency[$currency])) {
        $by_currency[$currency] = [
            'code' => $currency,
            'name' => $country['currency_name'],
            'symbol' => $country['currency_symbol'],
            'countries' => []
        ];
    }
    $by_currency[$currency]['countries'][] = $country;
}

jsonResponse([
    'success' => true,
    'countries' => $countries,
    'by_currency' => array_values($by_currency),
    'total' => count($countries)
]);
