<?php
/**
 * Helper Functions
 */

/**
 * Send JSON response
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get exchange rate (mock - replace with real API)
 */
function getExchangeRate($from, $to) {
    if ($from === $to) {
        return 1.0;
    }

    // Check cache
    $db = getDB();
    $stmt = $db->prepare("
        SELECT rate_with_fee
        FROM exchange_rates
        WHERE from_currency = :from
        AND to_currency = :to
        AND expires_at > datetime('now')
        ORDER BY fetched_at DESC
        LIMIT 1
    ");

    $stmt->execute(['from' => $from, 'to' => $to]);
    $cached = $stmt->fetch();

    if ($cached) {
        return (float)$cached['rate_with_fee'];
    }

    // Mock rates (replace with Google Finance API)
    $mockRates = [
        'BRL-EUR' => 0.1811,
        'BRL-USD' => 0.1950,
        'EUR-BRL' => 5.52,
        'USD-BRL' => 5.13,
        'EUR-USD' => 1.08,
        'USD-EUR' => 0.93,
    ];

    $key = "$from-$to";
    $rate = $mockRates[$key] ?? 1.0;

    // Apply 3% fee
    $rateWithFee = $rate * (1 - PLATFORM_FEE);

    // Cache it
    $stmt = $db->prepare("
        INSERT INTO exchange_rates (from_currency, to_currency, rate, rate_with_fee, expires_at)
        VALUES (:from, :to, :rate, :rate_with_fee, datetime('now', '+' || :minutes || ' minutes'))
    ");

    $stmt->execute([
        'from' => $from,
        'to' => $to,
        'rate' => $rate,
        'rate_with_fee' => $rateWithFee,
        'minutes' => EXCHANGE_CACHE_MINUTES
    ]);

    return $rateWithFee;
}

/**
 * Send notification
 */
function sendNotification($userId, $type, $title, $message) {
    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, type, title, message)
        VALUES (:user_id, :type, :title, :message)
    ");

    return $stmt->execute([
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'message' => $message
    ]);
}

/**
 * Generate transaction code
 */
function generateTransactionCode() {
    return 'TK' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}
