<?php
/**
 * Transactions Endpoint
 * GET /api/transactions - List user's transactions
 * GET /api/transactions/:id - Get single transaction
 * POST /api/transactions/:id/confirm-payment - Confirm payment made
 */

$method = $_SERVER['REQUEST_METHOD'];
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$transaction_id = isset($path_parts[3]) ? intval($path_parts[3]) : null;
$action = isset($path_parts[4]) ? $path_parts[4] : null;

$user = requireAuth();
$db = getDB();

// GET /api/transactions - List user's transactions
if ($method === 'GET' && !$transaction_id) {
    $stmt = $db->prepare("
        SELECT
            t.*,
            ua.full_name as user_a_name,
            ua.rating as user_a_rating,
            ub.full_name as user_b_name,
            ub.rating as user_b_rating,
            pa.from_country as prop_a_from_country,
            pa.to_country as prop_a_to_country,
            pa.recipient_name as prop_a_recipient_name,
            pa.recipient_phone as prop_a_recipient_phone,
            pb.from_country as prop_b_from_country,
            pb.to_country as prop_b_to_country,
            pb.recipient_name as prop_b_recipient_name,
            pb.recipient_phone as prop_b_recipient_phone
        FROM transactions t
        JOIN users ua ON t.user_a_id = ua.id
        JOIN users ub ON t.user_b_id = ub.id
        LEFT JOIN proposals pa ON t.proposal_a_id = pa.id
        LEFT JOIN proposals pb ON t.proposal_b_id = pb.id
        WHERE t.user_a_id = :user_id OR t.user_b_id = :user_id
        ORDER BY t.created_at DESC
        LIMIT 100
    ");

    $stmt->execute(['user_id' => $user['id']]);
    $transactions = $stmt->fetchAll();

    // Add user perspective to each transaction
    foreach ($transactions as &$transaction) {
        $is_user_a = $transaction['user_a_id'] == $user['id'];
        $transaction['is_user_a'] = $is_user_a;
        $transaction['my_amount'] = $is_user_a ? $transaction['amount_a'] : $transaction['amount_b'];
        $transaction['my_currency'] = $is_user_a ? $transaction['currency_a'] : $transaction['currency_b'];
        $transaction['partner_name'] = $is_user_a ? $transaction['user_b_name'] : $transaction['user_a_name'];
        $transaction['partner_rating'] = $is_user_a ? $transaction['user_b_rating'] : $transaction['user_a_rating'];
        $transaction['i_paid'] = $is_user_a ? !empty($transaction['user_a_paid_at']) : !empty($transaction['user_b_paid_at']);
        $transaction['partner_paid'] = $is_user_a ? !empty($transaction['user_b_paid_at']) : !empty($transaction['user_a_paid_at']);
    }

    jsonResponse([
        'success' => true,
        'transactions' => $transactions,
        'total' => count($transactions)
    ]);
}

// GET /api/transactions/:id - Get single transaction
if ($method === 'GET' && $transaction_id && !$action) {
    $stmt = $db->prepare("
        SELECT
            t.*,
            ua.full_name as user_a_name,
            ua.email as user_a_email,
            ua.phone as user_a_phone,
            ua.rating as user_a_rating,
            ub.full_name as user_b_name,
            ub.email as user_b_email,
            ub.phone as user_b_phone,
            ub.rating as user_b_rating,
            pa.*,
            pb.*,
            ca1.name as prop_a_from_country_name,
            ca1.payment_method as prop_a_from_payment_method,
            ca2.name as prop_a_to_country_name,
            ca2.payment_method as prop_a_to_payment_method,
            cb1.name as prop_b_from_country_name,
            cb1.payment_method as prop_b_from_payment_method,
            cb2.name as prop_b_to_country_name,
            cb2.payment_method as prop_b_to_payment_method
        FROM transactions t
        JOIN users ua ON t.user_a_id = ua.id
        JOIN users ub ON t.user_b_id = ub.id
        LEFT JOIN proposals pa ON t.proposal_a_id = pa.id
        LEFT JOIN proposals pb ON t.proposal_b_id = pb.id
        LEFT JOIN countries ca1 ON pa.from_country = ca1.country_code
        LEFT JOIN countries ca2 ON pa.to_country = ca2.country_code
        LEFT JOIN countries cb1 ON pb.from_country = cb1.country_code
        LEFT JOIN countries cb2 ON pb.to_country = cb2.country_code
        WHERE t.id = :id AND (t.user_a_id = :user_id OR t.user_b_id = :user_id)
    ");

    $stmt->execute(['id' => $transaction_id, 'user_id' => $user['id']]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        jsonResponse(['error' => 'Transaction not found'], 404);
    }

    // Add user perspective
    $is_user_a = $transaction['user_a_id'] == $user['id'];
    $transaction['is_user_a'] = $is_user_a;
    $transaction['i_paid'] = $is_user_a ? !empty($transaction['user_a_paid_at']) : !empty($transaction['user_b_paid_at']);
    $transaction['partner_paid'] = $is_user_a ? !empty($transaction['user_b_paid_at']) : !empty($transaction['user_a_paid_at']);

    jsonResponse([
        'success' => true,
        'transaction' => $transaction
    ]);
}

// POST /api/transactions/:id/confirm-payment - Confirm payment made
if ($method === 'POST' && $transaction_id && $action === 'confirm-payment') {
    // Get transaction
    $stmt = $db->prepare("
        SELECT * FROM transactions
        WHERE id = :id AND (user_a_id = :user_id OR user_b_id = :user_id)
    ");

    $stmt->execute(['id' => $transaction_id, 'user_id' => $user['id']]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        jsonResponse(['error' => 'Transaction not found'], 404);
    }

    $is_user_a = $transaction['user_a_id'] == $user['id'];
    $payment_field = $is_user_a ? 'user_a_paid_at' : 'user_b_paid_at';

    // Check if already paid
    if (!empty($transaction[$payment_field])) {
        jsonResponse(['error' => 'Payment already confirmed'], 400);
    }

    try {
        $db->beginTransaction();

        // Update payment confirmation
        $stmt = $db->prepare("UPDATE transactions SET $payment_field = datetime('now') WHERE id = :id");
        $stmt->execute(['id' => $transaction_id]);

        // Check if both paid
        $stmt = $db->prepare("SELECT * FROM transactions WHERE id = :id");
        $stmt->execute(['id' => $transaction_id]);
        $updated_transaction = $stmt->fetch();

        $both_paid = !empty($updated_transaction['user_a_paid_at']) && !empty($updated_transaction['user_b_paid_at']);

        if ($both_paid) {
            // Mark as completed
            $stmt = $db->prepare("UPDATE transactions SET status = 'completed', completed_at = datetime('now') WHERE id = :id");
            $stmt->execute(['id' => $transaction_id]);

            // Update user statistics
            $stmt = $db->prepare("
                UPDATE users SET
                    total_transactions = total_transactions + 1,
                    successful_transactions = successful_transactions + 1
                WHERE id IN (:user_a, :user_b)
            ");
            $stmt->execute([
                'user_a' => $transaction['user_a_id'],
                'user_b' => $transaction['user_b_id']
            ]);

            // Send completion notifications
            sendNotification(
                $transaction['user_a_id'],
                'transaction_completed',
                'Transação Concluída!',
                "A transação {$transaction['transaction_code']} foi concluída com sucesso."
            );

            sendNotification(
                $transaction['user_b_id'],
                'transaction_completed',
                'Transação Concluída!',
                "A transação {$transaction['transaction_code']} foi concluída com sucesso."
            );
        } else {
            // Notify the partner that payment was made
            $partner_id = $is_user_a ? $transaction['user_b_id'] : $transaction['user_a_id'];
            sendNotification(
                $partner_id,
                'payment_confirmed',
                'Pagamento Confirmado',
                "Seu parceiro confirmou o pagamento da transação {$transaction['transaction_code']}. Aguardando sua confirmação."
            );

            // Notify the user
            sendNotification(
                $user['id'],
                'payment_confirmed',
                'Pagamento Registrado',
                "Seu pagamento foi registrado. Aguardando confirmação do parceiro."
            );
        }

        $db->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Payment confirmed successfully',
            'both_paid' => $both_paid,
            'status' => $both_paid ? 'completed' : 'awaiting_partner_payment'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Failed to confirm payment: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Invalid request'], 400);
