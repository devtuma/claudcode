<?php
/**
 * TransKwanza - Proposal Class
 *
 * Handles all proposal-related operations
 */

class TK_Proposal {

    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'tk_proposals';
    }

    /**
     * Create a new proposal
     *
     * @param array $data Proposal data
     * @return int|false Proposal ID or false on error
     */
    public function create($data) {
        // Validate required fields
        $required = ['user_id', 'from_country', 'from_currency', 'to_country', 'to_currency', 'send_amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        // Get exchange rate and calculate receive amount
        $exchange_service = new TK_ExchangeRateService();
        $conversion = $exchange_service->convert(
            $data['send_amount'],
            $data['from_currency'],
            $data['to_currency'],
            true
        );

        if (!$conversion) {
            return false;
        }

        // Calculate expiration times
        $now = current_time('timestamp');
        $expires_at = date('Y-m-d H:i:s', $now + (24 * 3600)); // 24 hours
        $can_cancel_at = date('Y-m-d H:i:s', $now + (12 * 3600)); // 12 hours

        // Prepare proposal data
        $proposal_data = [
            'user_id' => $data['user_id'],
            'from_country' => $data['from_country'],
            'from_currency' => $data['from_currency'],
            'to_country' => $data['to_country'],
            'to_currency' => $data['to_currency'],
            'send_amount' => $data['send_amount'],
            'receive_amount' => $conversion['converted_amount'],
            'exchange_rate' => $conversion['exchange_rate'],
            'fee_amount' => $conversion['fee_amount'],
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'recipient_payment_details' => isset($data['recipient_payment_details']) ?
                json_encode($data['recipient_payment_details']) : null,
            'status' => 'open',
            'expires_at' => $expires_at,
            'can_cancel_at' => $can_cancel_at,
            'created_at' => current_time('mysql')
        ];

        $result = $this->wpdb->insert($this->table_name, $proposal_data);

        if ($result) {
            $proposal_id = $this->wpdb->insert_id;

            // Log activity
            $this->logActivity($data['user_id'], 'create_proposal', $proposal_id);

            // Try to find a match
            $this->findMatch($proposal_id);

            return $proposal_id;
        }

        return false;
    }

    /**
     * Get proposal by ID
     *
     * @param int $id Proposal ID
     * @return object|false Proposal object or false
     */
    public function get($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    /**
     * Update proposal
     *
     * @param int $id Proposal ID
     * @param array $data Data to update
     * @return bool Success
     */
    public function update($id, $data) {
        // Don't allow updating matched or completed proposals
        $proposal = $this->get($id);
        if (!$proposal || in_array($proposal->status, ['matched', 'completed'])) {
            return false;
        }

        // If send_amount changed, recalculate receive_amount
        if (isset($data['send_amount']) || isset($data['to_currency'])) {
            $exchange_service = new TK_ExchangeRateService();
            $conversion = $exchange_service->convert(
                $data['send_amount'] ?? $proposal->send_amount,
                $proposal->from_currency,
                $data['to_currency'] ?? $proposal->to_currency,
                true
            );

            if ($conversion) {
                $data['receive_amount'] = $conversion['converted_amount'];
                $data['exchange_rate'] = $conversion['exchange_rate'];
                $data['fee_amount'] = $conversion['fee_amount'];
            }
        }

        $data['updated_at'] = current_time('mysql');

        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id]
        );

        if ($result !== false) {
            $this->logActivity($proposal->user_id, 'update_proposal', $id);
            return true;
        }

        return false;
    }

    /**
     * Delete/Cancel proposal
     *
     * @param int $id Proposal ID
     * @param int $user_id User ID (for permission check)
     * @return bool Success
     */
    public function delete($id, $user_id) {
        $proposal = $this->get($id);

        if (!$proposal || $proposal->user_id != $user_id) {
            return false;
        }

        // Can't delete matched proposals
        if ($proposal->status === 'matched') {
            return false;
        }

        // Update status to cancelled instead of deleting
        $result = $this->wpdb->update(
            $this->table_name,
            [
                'status' => 'cancelled',
                'updated_at' => current_time('mysql')
            ],
            ['id' => $id]
        );

        if ($result !== false) {
            $this->logActivity($user_id, 'cancel_proposal', $id);
            return true;
        }

        return false;
    }

    /**
     * Get user's proposals
     *
     * @param int $user_id User ID
     * @param string $status Filter by status (optional)
     * @return array Array of proposals
     */
    public function getUserProposals($user_id, $status = null) {
        $sql = "SELECT p.*,
                cf.name AS from_country_name, cf.flag_emoji AS from_flag,
                ct.name AS to_country_name, ct.flag_emoji AS to_flag
                FROM {$this->table_name} p
                LEFT JOIN {$this->wpdb->prefix}tk_countries cf ON p.from_country = cf.country_code
                LEFT JOIN {$this->wpdb->prefix}tk_countries ct ON p.to_country = ct.country_code
                WHERE p.user_id = %d";

        $params = [$user_id];

        if ($status) {
            $sql .= " AND p.status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.created_at DESC";

        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }

    /**
     * Get available proposals (for matching)
     *
     * @param array $filters Filters (from_currency, to_currency, etc.)
     * @param int $exclude_user_id Exclude proposals from this user
     * @return array Array of proposals
     */
    public function getAvailableProposals($filters = [], $exclude_user_id = null) {
        $sql = "SELECT p.*,
                u.full_name, u.rating, u.total_transactions,
                cf.name AS from_country_name, cf.flag_emoji AS from_flag,
                ct.name AS to_country_name, ct.flag_emoji AS to_flag
                FROM {$this->table_name} p
                JOIN {$this->wpdb->prefix}tk_users u ON p.user_id = u.id
                LEFT JOIN {$this->wpdb->prefix}tk_countries cf ON p.from_country = cf.country_code
                LEFT JOIN {$this->wpdb->prefix}tk_countries ct ON p.to_country = ct.country_code
                WHERE p.status = 'open' AND p.expires_at > NOW()";

        $params = [];

        if ($exclude_user_id) {
            $sql .= " AND p.user_id != %d";
            $params[] = $exclude_user_id;
        }

        if (!empty($filters['from_currency'])) {
            $sql .= " AND p.from_currency = %s";
            $params[] = $filters['from_currency'];
        }

        if (!empty($filters['to_currency'])) {
            $sql .= " AND p.to_currency = %s";
            $params[] = $filters['to_currency'];
        }

        if (!empty($filters['from_country'])) {
            $sql .= " AND p.from_country = %s";
            $params[] = $filters['from_country'];
        }

        if (!empty($filters['to_country'])) {
            $sql .= " AND p.to_country = %s";
            $params[] = $filters['to_country'];
        }

        $sql .= " ORDER BY p.created_at DESC";

        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }

        return $this->wpdb->get_results($sql);
    }

    /**
     * Find matching proposal
     *
     * @param int $proposal_id Proposal ID to match
     * @return int|false Matched proposal ID or false
     */
    private function findMatch($proposal_id) {
        $proposal = $this->get($proposal_id);
        if (!$proposal) {
            return false;
        }

        // Look for reverse proposals (someone wanting to send what this user wants to receive)
        $matching_proposals = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE status = 'open'
            AND expires_at > NOW()
            AND user_id != %d
            AND from_currency = %s
            AND to_currency = %s
            ORDER BY ABS(send_amount - %f) ASC
            LIMIT 5",
            $proposal->user_id,
            $proposal->to_currency,
            $proposal->from_currency,
            $proposal->receive_amount
        ));

        if (!empty($matching_proposals)) {
            // Found potential matches, create notification
            $notification_service = new TK_NotificationService();
            foreach ($matching_proposals as $match) {
                $notification_service->create(
                    $proposal->user_id,
                    'match_found',
                    'Parceria Encontrada!',
                    sprintf(
                        'Encontramos uma proposta compatível: %s %.2f → %s %.2f',
                        $match->from_currency,
                        $match->send_amount,
                        $match->to_currency,
                        $match->receive_amount
                    ),
                    ['proposal_id' => $match->id]
                );
            }

            return $matching_proposals[0]->id;
        }

        return false;
    }

    /**
     * Accept a proposal (create transaction)
     *
     * @param int $proposal_id Proposal to accept
     * @param int $user_id User accepting the proposal
     * @param array $recipient_data Recipient information
     * @return int|false Transaction ID or false
     */
    public function accept($proposal_id, $user_id, $recipient_data) {
        $proposal = $this->get($proposal_id);

        if (!$proposal || $proposal->status !== 'open') {
            return false;
        }

        // Can't accept own proposal
        if ($proposal->user_id == $user_id) {
            return false;
        }

        // Get or create matching proposal for the accepter
        $user_proposals = $this->getUserProposals($user_id, 'open');
        $matching_user_proposal = null;

        foreach ($user_proposals as $up) {
            if ($up->from_currency == $proposal->to_currency &&
                $up->to_currency == $proposal->from_currency) {
                $matching_user_proposal = $up;
                break;
            }
        }

        // If no matching proposal exists, create one
        if (!$matching_user_proposal) {
            $new_proposal_id = $this->create([
                'user_id' => $user_id,
                'from_country' => $proposal->to_country,
                'from_currency' => $proposal->to_currency,
                'to_country' => $proposal->from_country,
                'to_currency' => $proposal->from_currency,
                'send_amount' => $proposal->receive_amount,
                'recipient_name' => $recipient_data['recipient_name'],
                'recipient_phone' => $recipient_data['recipient_phone'],
                'recipient_payment_details' => $recipient_data
            ]);

            if (!$new_proposal_id) {
                return false;
            }

            $matching_user_proposal = $this->get($new_proposal_id);
        }

        // Create transaction
        $transaction_service = new TK_TransactionService();
        $transaction_id = $transaction_service->create($proposal_id, $matching_user_proposal->id);

        if ($transaction_id) {
            // Update both proposals to matched status
            $this->wpdb->update(
                $this->table_name,
                [
                    'status' => 'matched',
                    'matched_proposal_id' => $matching_user_proposal->id,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $proposal_id]
            );

            $this->wpdb->update(
                $this->table_name,
                [
                    'status' => 'matched',
                    'matched_proposal_id' => $proposal_id,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $matching_user_proposal->id]
            );

            $this->logActivity($user_id, 'accept_proposal', $proposal_id, [
                'transaction_id' => $transaction_id
            ]);

            return $transaction_id;
        }

        return false;
    }

    /**
     * Auto-expire old proposals (cron job)
     */
    public function autoExpire() {
        $expired = $this->wpdb->update(
            $this->table_name,
            [
                'status' => 'expired',
                'updated_at' => current_time('mysql')
            ],
            [
                'status' => 'open'
            ],
            ['%s', '%s'],
            ['%s']
        );

        // Add WHERE clause manually (wpdb limitation)
        $this->wpdb->query(
            "UPDATE {$this->table_name}
            SET status = 'expired', updated_at = NOW()
            WHERE status = 'open' AND expires_at < NOW()"
        );

        return $expired;
    }

    /**
     * Log activity
     */
    private function logActivity($user_id, $action, $proposal_id, $data = []) {
        $log_table = $this->wpdb->prefix . 'tk_activity_logs';
        $this->wpdb->insert($log_table, [
            'user_id' => $user_id,
            'action' => $action,
            'entity_type' => 'proposal',
            'entity_id' => $proposal_id,
            'data' => json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => current_time('mysql')
        ]);
    }
}
