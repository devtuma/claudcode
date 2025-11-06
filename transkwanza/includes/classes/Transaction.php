<?php
/**
 * TransKwanza - Transaction Service
 *
 * Handles all transaction-related operations
 */

class TK_TransactionService {

    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'tk_transactions';
    }

    /**
     * Create a new transaction from matched proposals
     *
     * @param int $proposal_a_id First proposal ID
     * @param int $proposal_b_id Second proposal ID
     * @return int|false Transaction ID or false on error
     */
    public function create($proposal_a_id, $proposal_b_id) {
        $proposal_table = $this->wpdb->prefix . 'tk_proposals';

        $proposal_a = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $proposal_table WHERE id = %d",
            $proposal_a_id
        ));

        $proposal_b = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $proposal_table WHERE id = %d",
            $proposal_b_id
        ));

        if (!$proposal_a || !$proposal_b) {
            return false;
        }

        // Validate that proposals are complementary
        if ($proposal_a->from_currency !== $proposal_b->to_currency ||
            $proposal_a->to_currency !== $proposal_b->from_currency) {
            return false;
        }

        // Generate unique transaction code
        $transaction_code = $this->generateTransactionCode();

        // Prepare transaction data
        $transaction_data = [
            'transaction_code' => $transaction_code,
            'proposal_a_id' => $proposal_a_id,
            'proposal_b_id' => $proposal_b_id,
            'user_a_id' => $proposal_a->user_id,
            'user_b_id' => $proposal_b->user_id,
            'amount_a' => $proposal_a->send_amount,
            'currency_a' => $proposal_a->from_currency,
            'amount_b' => $proposal_b->send_amount,
            'currency_b' => $proposal_b->from_currency,
            'exchange_rate' => $proposal_a->exchange_rate,
            'platform_fee_a' => $proposal_a->fee_amount,
            'platform_fee_b' => $proposal_b->fee_amount,
            'status' => 'awaiting_payment_a',
            'created_at' => current_time('mysql')
        ];

        $result = $this->wpdb->insert($this->table_name, $transaction_data);

        if ($result) {
            $transaction_id = $this->wpdb->insert_id;

            // Send notifications to both users
            $notification_service = new TK_NotificationService();

            $notification_service->create(
                $proposal_a->user_id,
                'transaction_created',
                'Nova Transação Criada',
                sprintf(
                    'Sua proposta foi aceita! Código da transação: %s. Por favor, efetue o pagamento.',
                    $transaction_code
                ),
                ['transaction_id' => $transaction_id]
            );

            $notification_service->create(
                $proposal_b->user_id,
                'transaction_created',
                'Nova Transação Criada',
                sprintf(
                    'Sua proposta foi aceita! Código da transação: %s. Por favor, efetue o pagamento.',
                    $transaction_code
                ),
                ['transaction_id' => $transaction_id]
            );

            // Log activity
            $this->logActivity($proposal_a->user_id, 'transaction_created', $transaction_id);
            $this->logActivity($proposal_b->user_id, 'transaction_created', $transaction_id);

            return $transaction_id;
        }

        return false;
    }

    /**
     * Get transaction by ID
     *
     * @param int $id Transaction ID
     * @return object|false Transaction object or false
     */
    public function get($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT t.*,
            ua.full_name AS user_a_name, ua.phone AS user_a_phone,
            ub.full_name AS user_b_name, ub.phone AS user_b_phone
            FROM {$this->table_name} t
            LEFT JOIN {$this->wpdb->prefix}tk_users ua ON t.user_a_id = ua.id
            LEFT JOIN {$this->wpdb->prefix}tk_users ub ON t.user_b_id = ub.id
            WHERE t.id = %d",
            $id
        ));
    }

    /**
     * Get transaction by code
     *
     * @param string $transaction_code Transaction code
     * @return object|false Transaction object or false
     */
    public function getByCode($transaction_code) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT t.*,
            ua.full_name AS user_a_name,
            ub.full_name AS user_b_name
            FROM {$this->table_name} t
            LEFT JOIN {$this->wpdb->prefix}tk_users ua ON t.user_a_id = ua.id
            LEFT JOIN {$this->wpdb->prefix}tk_users ub ON t.user_b_id = ub.id
            WHERE t.transaction_code = %s",
            $transaction_code
        ));
    }

    /**
     * Get user's transactions
     *
     * @param int $user_id User ID
     * @param string $status Filter by status (optional)
     * @return array Array of transactions
     */
    public function getUserTransactions($user_id, $status = null) {
        $sql = "SELECT t.*,
                ua.full_name AS user_a_name,
                ub.full_name AS user_b_name
                FROM {$this->table_name} t
                LEFT JOIN {$this->wpdb->prefix}tk_users ua ON t.user_a_id = ua.id
                LEFT JOIN {$this->wpdb->prefix}tk_users ub ON t.user_b_id = ub.id
                WHERE (t.user_a_id = %d OR t.user_b_id = %d)";

        $params = [$user_id, $user_id];

        if ($status) {
            $sql .= " AND t.status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY t.created_at DESC";

        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }

    /**
     * Upload payment proof
     *
     * @param int $transaction_id Transaction ID
     * @param int $user_id User ID
     * @param string $file_path Path to uploaded file
     * @return bool Success
     */
    public function uploadPaymentProof($transaction_id, $user_id, $file_path) {
        $transaction = $this->get($transaction_id);

        if (!$transaction) {
            return false;
        }

        $update_data = [
            'updated_at' => current_time('mysql')
        ];

        // Determine which user is uploading
        if ($transaction->user_a_id == $user_id) {
            $update_data['user_a_payment_proof'] = $file_path;
            $update_data['user_a_paid_at'] = current_time('mysql');

            // Update status
            if ($transaction->status === 'awaiting_payment_a') {
                $update_data['status'] = 'awaiting_payment_b';
            } elseif ($transaction->status === 'awaiting_payment_b' && $transaction->user_b_paid_at) {
                $update_data['status'] = 'both_paid';
            }

        } elseif ($transaction->user_b_id == $user_id) {
            $update_data['user_b_payment_proof'] = $file_path;
            $update_data['user_b_paid_at'] = current_time('mysql');

            // Update status
            if ($transaction->status === 'awaiting_payment_b') {
                $update_data['status'] = 'both_paid';
            } elseif ($transaction->status === 'awaiting_payment_a' && $transaction->user_a_paid_at) {
                $update_data['status'] = 'both_paid';
            }

        } else {
            return false; // User not part of transaction
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $transaction_id]
        );

        if ($result !== false) {
            // Notify other user
            $other_user_id = ($user_id == $transaction->user_a_id) ?
                $transaction->user_b_id : $transaction->user_a_id;

            $notification_service = new TK_NotificationService();
            $notification_service->create(
                $other_user_id,
                'payment_received',
                'Pagamento Recebido',
                sprintf(
                    'O outro usuário enviou o comprovante de pagamento para a transação %s',
                    $transaction->transaction_code
                ),
                ['transaction_id' => $transaction_id]
            );

            // If both paid, notify admin for processing
            if ($update_data['status'] === 'both_paid') {
                $this->notifyAdminBothPaid($transaction_id);
            }

            $this->logActivity($user_id, 'upload_payment_proof', $transaction_id);
            return true;
        }

        return false;
    }

    /**
     * Complete transaction (admin action)
     *
     * @param int $transaction_id Transaction ID
     * @param int $admin_id Admin user ID
     * @return bool Success
     */
    public function complete($transaction_id, $admin_id) {
        $transaction = $this->get($transaction_id);

        if (!$transaction || $transaction->status !== 'both_paid') {
            return false;
        }

        $result = $this->wpdb->update(
            $this->table_name,
            [
                'status' => 'completed',
                'completed_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $transaction_id]
        );

        if ($result !== false) {
            // Update user statistics
            $this->updateUserStats($transaction->user_a_id, true);
            $this->updateUserStats($transaction->user_b_id, true);

            // Send completion notifications
            $notification_service = new TK_NotificationService();

            $notification_service->create(
                $transaction->user_a_id,
                'transaction_completed',
                'Transação Concluída!',
                sprintf(
                    'Sua transação %s foi concluída com sucesso!',
                    $transaction->transaction_code
                ),
                ['transaction_id' => $transaction_id]
            );

            $notification_service->create(
                $transaction->user_b_id,
                'transaction_completed',
                'Transação Concluída!',
                sprintf(
                    'Sua transação %s foi concluída com sucesso!',
                    $transaction->transaction_code
                ),
                ['transaction_id' => $transaction_id]
            );

            $this->logActivity($admin_id, 'complete_transaction', $transaction_id);
            return true;
        }

        return false;
    }

    /**
     * Cancel transaction
     *
     * @param int $transaction_id Transaction ID
     * @param int $user_id User requesting cancellation
     * @param string $reason Cancellation reason
     * @return bool Success
     */
    public function cancel($transaction_id, $user_id, $reason = '') {
        $transaction = $this->get($transaction_id);

        if (!$transaction) {
            return false;
        }

        // Only allow cancellation if not both paid
        if ($transaction->status === 'both_paid' || $transaction->status === 'completed') {
            return false;
        }

        $result = $this->wpdb->update(
            $this->table_name,
            [
                'status' => 'cancelled',
                'cancelled_at' => current_time('mysql'),
                'cancellation_reason' => $reason,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $transaction_id]
        );

        if ($result !== false) {
            // Reopen proposals
            $proposal_table = $this->wpdb->prefix . 'tk_proposals';
            $this->wpdb->update(
                $proposal_table,
                ['status' => 'open', 'matched_proposal_id' => null],
                ['id' => $transaction->proposal_a_id]
            );
            $this->wpdb->update(
                $proposal_table,
                ['status' => 'open', 'matched_proposal_id' => null],
                ['id' => $transaction->proposal_b_id]
            );

            // Notify users
            $notification_service = new TK_NotificationService();
            $other_user_id = ($user_id == $transaction->user_a_id) ?
                $transaction->user_b_id : $transaction->user_a_id;

            $notification_service->create(
                $other_user_id,
                'transaction_cancelled',
                'Transação Cancelada',
                sprintf(
                    'A transação %s foi cancelada. Motivo: %s',
                    $transaction->transaction_code,
                    $reason ?: 'Não especificado'
                ),
                ['transaction_id' => $transaction_id]
            );

            $this->logActivity($user_id, 'cancel_transaction', $transaction_id, ['reason' => $reason]);
            return true;
        }

        return false;
    }

    /**
     * Generate unique transaction code
     *
     * @return string Transaction code
     */
    private function generateTransactionCode() {
        do {
            $code = 'TK' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE transaction_code = %s",
                $code
            ));
        } while ($exists > 0);

        return $code;
    }

    /**
     * Update user statistics
     *
     * @param int $user_id User ID
     * @param bool $success Transaction successful
     */
    private function updateUserStats($user_id, $success = true) {
        $user_table = $this->wpdb->prefix . 'tk_users';

        if ($success) {
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE $user_table
                SET total_transactions = total_transactions + 1,
                    successful_transactions = successful_transactions + 1
                WHERE id = %d",
                $user_id
            ));
        } else {
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE $user_table
                SET total_transactions = total_transactions + 1,
                    failed_transactions = failed_transactions + 1
                WHERE id = %d",
                $user_id
            ));
        }
    }

    /**
     * Notify admin when both users have paid
     */
    private function notifyAdminBothPaid($transaction_id) {
        // Send email to admin
        $transaction = $this->get($transaction_id);
        $admin_email = get_option('admin_email');

        $subject = 'TransKwanza - Pagamentos Confirmados: ' . $transaction->transaction_code;
        $message = sprintf(
            "Ambos os usuários confirmaram pagamento para a transação %s.\n\n" .
            "Usuário A: %s (%.2f %s)\n" .
            "Usuário B: %s (%.2f %s)\n\n" .
            "Por favor, verifique os comprovantes e processe a transação.",
            $transaction->transaction_code,
            $transaction->user_a_name,
            $transaction->amount_a,
            $transaction->currency_a,
            $transaction->user_b_name,
            $transaction->amount_b,
            $transaction->currency_b
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Log activity
     */
    private function logActivity($user_id, $action, $transaction_id, $data = []) {
        $log_table = $this->wpdb->prefix . 'tk_activity_logs';
        $this->wpdb->insert($log_table, [
            'user_id' => $user_id,
            'action' => $action,
            'entity_type' => 'transaction',
            'entity_id' => $transaction_id,
            'data' => json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => current_time('mysql')
        ]);
    }
}
