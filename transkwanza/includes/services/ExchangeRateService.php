<?php
/**
 * TransKwanza - Exchange Rate Service
 *
 * Service responsible for fetching and caching exchange rates from Google Finance
 * Supports all currency pairs with automatic 3% fee calculation
 */

class TK_ExchangeRateService {

    private $wpdb;
    private $cache_duration = 1800; // 30 minutes cache
    private $platform_fee = 0.03; // 3% platform fee

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get exchange rate between two currencies
     *
     * @param string $from_currency Source currency code
     * @param string $to_currency Target currency code
     * @param bool $with_fee Include 3% platform fee
     * @return array|false Exchange rate data or false on error
     */
    public function getExchangeRate($from_currency, $to_currency, $with_fee = true) {
        // Same currency
        if ($from_currency === $to_currency) {
            return [
                'from' => $from_currency,
                'to' => $to_currency,
                'rate' => 1.0,
                'rate_with_fee' => 1.0,
                'fee_percentage' => 0,
                'source' => 'system',
                'cached' => false,
                'updated_at' => current_time('mysql')
            ];
        }

        // Check cache first
        $cached_rate = $this->getCachedRate($from_currency, $to_currency);
        if ($cached_rate) {
            return $cached_rate;
        }

        // Fetch new rate from Google Finance
        $rate = $this->fetchFromGoogleFinance($from_currency, $to_currency);

        if ($rate === false) {
            // Fallback: try inverse rate
            $inverse_rate = $this->fetchFromGoogleFinance($to_currency, $from_currency);
            if ($inverse_rate !== false) {
                $rate = 1 / $inverse_rate;
            } else {
                return false;
            }
        }

        // Calculate rate with fee
        $rate_with_fee = $rate * (1 - $this->platform_fee);

        // Cache the rate
        $this->cacheRate($from_currency, $to_currency, $rate, $rate_with_fee);

        return [
            'from' => $from_currency,
            'to' => $to_currency,
            'rate' => $rate,
            'rate_with_fee' => $with_fee ? $rate_with_fee : $rate,
            'fee_percentage' => $this->platform_fee * 100,
            'source' => 'Google Finance',
            'cached' => false,
            'updated_at' => current_time('mysql')
        ];
    }

    /**
     * Fetch exchange rate from multiple sources with fallback
     *
     * @param string $from_currency
     * @param string $to_currency
     * @return float|false Exchange rate or false on error
     */
    private function fetchFromGoogleFinance($from_currency, $to_currency) {
        // Try ExchangeRate-API first (free, reliable, no key needed for basic use)
        $rate = $this->fetchFromExchangeRateAPI($from_currency, $to_currency);
        if ($rate !== false) {
            return round($rate, 4); // Precisão de 4 casas decimais
        }

        // Fallback 1: Try Google Finance scraping
        $rate = $this->fetchFromGoogleFinanceScraping($from_currency, $to_currency);
        if ($rate !== false) {
            return round($rate, 4);
        }

        // Fallback 2: Try alternative API
        $rate = $this->fetchFromCurrencyAPI($from_currency, $to_currency);
        if ($rate !== false) {
            return round($rate, 4);
        }

        error_log("TransKwanza: All exchange rate sources failed for {$from_currency}-{$to_currency}");
        return false;
    }

    /**
     * Fetch from ExchangeRate-API.com (Free, no key required)
     *
     * @param string $from_currency
     * @param string $to_currency
     * @return float|false
     */
    private function fetchFromExchangeRateAPI($from_currency, $to_currency) {
        $url = sprintf(
            'https://api.exchangerate-api.com/v4/latest/%s',
            strtoupper($from_currency)
        );

        try {
            $response = wp_remote_get($url, ['timeout' => 10]);

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['rates'][$to_currency])) {
                return floatval($data['rates'][$to_currency]);
            }

            return false;
        } catch (Exception $e) {
            error_log('TransKwanza: ExchangeRate-API error - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch from Google Finance via HTML scraping (Fallback)
     *
     * @param string $from_currency
     * @param string $to_currency
     * @return float|false
     */
    private function fetchFromGoogleFinanceScraping($from_currency, $to_currency) {
        $url = sprintf(
            'https://www.google.com/finance/quote/%s-%s',
            strtoupper($from_currency),
            strtoupper($to_currency)
        );

        try {
            $response = wp_remote_get($url, [
                'timeout' => 10,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]);

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);

            // Parse HTML - múltiplos métodos de parsing
            $patterns = [
                '/<div class="YMlKec fxKbKc">([0-9,.]+)<\/div>/',
                '/data-last-price="([0-9.]+)"/',
                '/"price":"([0-9.]+)"/',
                '/class=".*?YMlKec.*?">([0-9,.]+)</'
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $body, $matches)) {
                    $rate = str_replace(',', '', $matches[1]);
                    return floatval($rate);
                }
            }

            return false;
        } catch (Exception $e) {
            error_log('TransKwanza: Google Finance scraping error - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch from CurrencyAPI.com (Alternative fallback)
     *
     * @param string $from_currency
     * @param string $to_currency
     * @return float|false
     */
    private function fetchFromCurrencyAPI($from_currency, $to_currency) {
        // Free tier: https://freecurrencyapi.com/
        $url = sprintf(
            'https://api.freecurrencyapi.com/v1/latest?apikey=fca_live_example&base_currency=%s&currencies=%s',
            strtoupper($from_currency),
            strtoupper($to_currency)
        );

        try {
            $response = wp_remote_get($url, ['timeout' => 10]);

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['data'][$to_currency])) {
                return floatval($data['data'][$to_currency]);
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get cached exchange rate
     *
     * @param string $from_currency
     * @param string $to_currency
     * @return array|false Cached rate or false if not found/expired
     */
    private function getCachedRate($from_currency, $to_currency) {
        $table = $this->wpdb->prefix . 'tk_exchange_rates';

        $cached = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table
            WHERE from_currency = %s
            AND to_currency = %s
            AND expires_at > NOW()
            AND is_valid = 1
            ORDER BY fetched_at DESC
            LIMIT 1",
            $from_currency,
            $to_currency
        ));

        if ($cached) {
            return [
                'from' => $cached->from_currency,
                'to' => $cached->to_currency,
                'rate' => floatval($cached->rate),
                'rate_with_fee' => floatval($cached->rate_with_fee),
                'fee_percentage' => $this->platform_fee * 100,
                'source' => $cached->source,
                'cached' => true,
                'updated_at' => $cached->fetched_at
            ];
        }

        return false;
    }

    /**
     * Cache exchange rate in database
     *
     * @param string $from_currency
     * @param string $to_currency
     * @param float $rate
     * @param float $rate_with_fee
     */
    private function cacheRate($from_currency, $to_currency, $rate, $rate_with_fee) {
        $table = $this->wpdb->prefix . 'tk_exchange_rates';

        $this->wpdb->insert($table, [
            'from_currency' => $from_currency,
            'to_currency' => $to_currency,
            'rate' => $rate,
            'rate_with_fee' => $rate_with_fee,
            'source' => 'Google Finance',
            'fetched_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->cache_duration),
            'is_valid' => 1
        ]);
    }

    /**
     * Convert amount between currencies
     *
     * @param float $amount
     * @param string $from_currency
     * @param string $to_currency
     * @param bool $with_fee Include platform fee
     * @return array|false Conversion result or false on error
     */
    public function convert($amount, $from_currency, $to_currency, $with_fee = true) {
        $rate_data = $this->getExchangeRate($from_currency, $to_currency, $with_fee);

        if (!$rate_data) {
            return false;
        }

        $rate = $with_fee ? $rate_data['rate_with_fee'] : $rate_data['rate'];
        $converted_amount = $amount * $rate;
        $fee_amount = $with_fee ? ($amount * $rate * $this->platform_fee) : 0;

        return [
            'original_amount' => $amount,
            'converted_amount' => $converted_amount,
            'from_currency' => $from_currency,
            'to_currency' => $to_currency,
            'exchange_rate' => $rate,
            'fee_percentage' => $with_fee ? ($this->platform_fee * 100) : 0,
            'fee_amount' => $fee_amount,
            'rate_info' => $rate_data
        ];
    }

    /**
     * Refresh expired rates
     * Should be called via cron job
     */
    public function refreshExpiredRates() {
        $table = $this->wpdb->prefix . 'tk_exchange_rates';

        // Mark expired rates as invalid
        $this->wpdb->query(
            "UPDATE $table SET is_valid = 0 WHERE expires_at < NOW()"
        );

        // Get unique currency pairs from active proposals
        $proposals_table = $this->wpdb->prefix . 'tk_proposals';
        $pairs = $this->wpdb->get_results(
            "SELECT DISTINCT from_currency, to_currency
            FROM $proposals_table
            WHERE status = 'open' AND expires_at > NOW()"
        );

        // Refresh rates for active pairs
        foreach ($pairs as $pair) {
            $this->getExchangeRate($pair->from_currency, $pair->to_currency, true);
        }
    }

    /**
     * Get all supported currency pairs
     *
     * @return array Array of currency pairs
     */
    public function getSupportedPairs() {
        $countries = include(dirname(dirname(__DIR__)) . '/config/countries.php');
        $currencies = array_unique(array_column($countries, 'currency_code'));

        $pairs = [];
        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                if ($from !== $to) {
                    $pairs[] = ['from' => $from, 'to' => $to];
                }
            }
        }

        return $pairs;
    }
}
