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
 * Get exchange rate from real APIs with fallback
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

    // Fetch real rate from APIs
    $rate = fetchRealExchangeRate($from, $to);

    if ($rate === false) {
        error_log("TransKwanza: Failed to fetch exchange rate for {$from}-{$to}");
        return 1.0; // Fallback
    }

    // Apply 3% fee
    $rateWithFee = round($rate * (1 - PLATFORM_FEE), 4);

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
 * Fetch real exchange rate from multiple sources
 */
function fetchRealExchangeRate($from, $to) {
    // Try ExchangeRate-API first (free, no key required)
    $rate = fetchFromExchangeRateAPI($from, $to);
    if ($rate !== false) {
        return round($rate, 4); // Precisão de 4 casas decimais
    }

    // Fallback 1: Try Google Finance scraping
    $rate = fetchFromGoogleFinance($from, $to);
    if ($rate !== false) {
        return round($rate, 4);
    }

    // Fallback 2: Try inverse rate
    $inverseRate = fetchFromExchangeRateAPI($to, $from);
    if ($inverseRate !== false && $inverseRate > 0) {
        return round(1 / $inverseRate, 4);
    }

    return false;
}

/**
 * Fetch from ExchangeRate-API.com (Free, reliable)
 */
function fetchFromExchangeRateAPI($from, $to) {
    $url = "https://api.exchangerate-api.com/v4/latest/" . strtoupper($from);

    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['rates'][$to])) {
            return (float)$data['rates'][$to];
        }

        return false;
    } catch (Exception $e) {
        error_log('TransKwanza: ExchangeRate-API error - ' . $e->getMessage());
        return false;
    }
}

/**
 * Fetch from Google Finance via scraping (Fallback)
 */
function fetchFromGoogleFinance($from, $to) {
    $url = "https://www.google.com/finance/quote/{$from}-{$to}";

    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) {
            return false;
        }

        // Multiple parsing patterns for reliability
        $patterns = [
            '/<div class="YMlKec fxKbKc">([0-9,.]+)<\/div>/',
            '/data-last-price="([0-9.]+)"/',
            '/"price":"([0-9.]+)"/',
            '/class=".*?YMlKec.*?">([0-9,.]+)</'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $rate = str_replace(',', '', $matches[1]);
                return (float)$rate;
            }
        }

        return false;
    } catch (Exception $e) {
        error_log('TransKwanza: Google Finance error - ' . $e->getMessage());
        return false;
    }
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
