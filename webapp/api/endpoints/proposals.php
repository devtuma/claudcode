<?php
/**
 * Proposals Endpoint
 * GET /api/proposals - List proposals
 * POST /api/proposals - Create proposal
 * GET /api/proposals/:id - Get single proposal
 * POST /api/proposals/:id/accept - Accept a proposal
 * DELETE /api/proposals/:id - Cancel proposal
 */

$method = $_SERVER['REQUEST_METHOD'];
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$proposal_id = isset($path_parts[3]) ? intval($path_parts[3]) : null;
$action = isset($path_parts[4]) ? $path_parts[4] : null;

$db = getDB();

// GET /api/proposals - List proposals
if ($method === 'GET' && !$proposal_id) {
    // Optional: require auth to see proposals
    $current_user = getCurrentUser();

    $filters = [];
    $params = [];

    // Filter by user's proposals
    if (isset($_GET['my']) && $_GET['my'] === '1' && $current_user) {
        $filters[] = "user_id = :user_id";
        $params['user_id'] = $current_user['id'];
    }

    // Filter by status
    if (isset($_GET['status'])) {
        $filters[] = "status = :status";
        $params['status'] = sanitize($_GET['status']);
    } else {
        // Default: only show open proposals
        $filters[] = "status = 'open'";
    }

    // Filter by currencies
    if (isset($_GET['from_currency'])) {
        $filters[] = "from_currency = :from_currency";
        $params['from_currency'] = strtoupper(sanitize($_GET['from_currency']));
    }

    if (isset($_GET['to_currency'])) {
        $filters[] = "to_currency = :to_currency";
        $params['to_currency'] = strtoupper(sanitize($_GET['to_currency']));
    }

    // Exclude expired
    $filters[] = "expires_at > datetime('now')";

    // Exclude own proposals if viewing available
    if ($current_user && (!isset($_GET['my']) || $_GET['my'] !== '1')) {
        $filters[] = "user_id != :exclude_user_id";
        $params['exclude_user_id'] = $current_user['id'];
    }

    $where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

    $stmt = $db->prepare("
        SELECT
            p.*,
            u.full_name as user_name,
            u.rating as user_rating,
            u.total_transactions as user_transactions,
            c1.name as from_country,
            c1.currency_symbol as from_symbol,
            c2.name as to_country,
            c2.currency_symbol as to_symbol
        FROM proposals p
        JOIN users u ON p.user_id = u.id
        JOIN countries c1 ON p.from_country = c1.country_code
        JOIN countries c2 ON p.to_country = c2.country_code
        $where
        ORDER BY p.created_at DESC
        LIMIT 100
    ");

    $stmt->execute($params);
    $proposals = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'proposals' => $proposals,
        'total' => count($proposals)
    ]);
}

// GET /api/proposals/:id - Get single proposal
if ($method === 'GET' && $proposal_id && !$action) {
    $stmt = $db->prepare("
        SELECT
            p.*,
            u.full_name as user_name,
            u.email as user_email,
            u.phone as user_phone,
            u.rating as user_rating,
            u.total_transactions as user_transactions,
            c1.name as from_country,
            c1.currency_name as from_currency_name,
            c1.currency_symbol as from_symbol,
            c1.payment_method as from_payment_method,
            c2.name as to_country,
            c2.currency_name as to_currency_name,
            c2.currency_symbol as to_symbol,
            c2.payment_method as to_payment_method
        FROM proposals p
        JOIN users u ON p.user_id = u.id
        JOIN countries c1 ON p.from_country = c1.country_code
        JOIN countries c2 ON p.to_country = c2.country_code
        WHERE p.id = :id
    ");

    $stmt->execute(['id' => $proposal_id]);
    $proposal = $stmt->fetch();

    if (!$proposal) {
        jsonResponse(['error' => 'Proposal not found'], 404);
    }

    jsonResponse([
        'success' => true,
        'proposal' => $proposal
    ]);
}

// POST /api/proposals - Create proposal
if ($method === 'POST' && !$proposal_id) {
    $user = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['from_country', 'from_currency', 'to_country', 'to_currency', 'send_amount'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['error' => "Field '$field' is required"], 400);
        }
    }

    $from_country = strtoupper(sanitize($data['from_country']));
    $from_currency = strtoupper(sanitize($data['from_currency']));
    $to_country = strtoupper(sanitize($data['to_country']));
    $to_currency = strtoupper(sanitize($data['to_currency']));
    $send_amount = floatval($data['send_amount']);

    if ($send_amount <= 0) {
        jsonResponse(['error' => 'Send amount must be greater than 0'], 400);
    }

    // Get exchange rate
    $rate = getExchangeRate($from_currency, $to_currency);
    $receive_amount = $send_amount * $rate;
    $fee_amount = $send_amount * PLATFORM_FEE;

    // Expiration: 24 hours from now
    $expires_at = date('Y-m-d H:i:s', time() + 24 * 3600);

    // Insert proposal
    $stmt = $db->prepare("
        INSERT INTO proposals (
            user_id, from_country, from_currency, to_country, to_currency,
            send_amount, receive_amount, exchange_rate, fee_amount,
            recipient_name, recipient_phone, recipient_details,
            status, expires_at
        ) VALUES (
            :user_id, :from_country, :from_currency, :to_country, :to_currency,
            :send_amount, :receive_amount, :exchange_rate, :fee_amount,
            :recipient_name, :recipient_phone, :recipient_details,
            'open', :expires_at
        )
    ");

    try {
        $stmt->execute([
            'user_id' => $user['id'],
            'from_country' => $from_country,
            'from_currency' => $from_currency,
            'to_country' => $to_country,
            'to_currency' => $to_currency,
            'send_amount' => $send_amount,
            'receive_amount' => $receive_amount,
            'exchange_rate' => $rate,
            'fee_amount' => $fee_amount,
            'recipient_name' => isset($data['recipient_name']) ? sanitize($data['recipient_name']) : null,
            'recipient_phone' => isset($data['recipient_phone']) ? sanitize($data['recipient_phone']) : null,
            'recipient_details' => isset($data['recipient_details']) ? sanitize($data['recipient_details']) : null,
            'expires_at' => $expires_at
        ]);

        $new_id = $db->lastInsertId();

        // Get created proposal
        $stmt = $db->prepare("SELECT * FROM proposals WHERE id = :id");
        $stmt->execute(['id' => $new_id]);
        $proposal = $stmt->fetch();

        // Send notification
        sendNotification(
            $user['id'],
            'proposal_created',
            'Proposta Criada',
            "Sua proposta de {$from_currency} para {$to_currency} foi criada. Aguardando matching."
        );

        jsonResponse([
            'success' => true,
            'message' => 'Proposal created successfully',
            'proposal' => $proposal
        ], 201);

    } catch (Exception $e) {
        jsonResponse(['error' => 'Failed to create proposal: ' . $e->getMessage()], 500);
    }
}

// POST /api/proposals/:id/accept - Accept a proposal
if ($method === 'POST' && $proposal_id && $action === 'accept') {
    $user = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);

    // Get proposal
    $stmt = $db->prepare("SELECT * FROM proposals WHERE id = :id AND status = 'open' AND expires_at > datetime('now')");
    $stmt->execute(['id' => $proposal_id]);
    $proposal = $stmt->fetch();

    if (!$proposal) {
        jsonResponse(['error' => 'Proposal not found or no longer available'], 404);
    }

    // Can't accept own proposal
    if ($proposal['user_id'] == $user['id']) {
        jsonResponse(['error' => 'Cannot accept your own proposal'], 400);
    }

    // Check if user has a matching proposal
    $matching_proposal_id = isset($data['matching_proposal_id']) ? intval($data['matching_proposal_id']) : null;

    if ($matching_proposal_id) {
        // Verify the matching proposal belongs to current user
        $stmt = $db->prepare("SELECT * FROM proposals WHERE id = :id AND user_id = :user_id AND status = 'open'");
        $stmt->execute(['id' => $matching_proposal_id, 'user_id' => $user['id']]);
        $user_proposal = $stmt->fetch();

        if (!$user_proposal) {
            jsonResponse(['error' => 'Your proposal not found'], 404);
        }

        // Verify proposals match (reverse currencies)
        if ($proposal['from_currency'] !== $user_proposal['to_currency'] ||
            $proposal['to_currency'] !== $user_proposal['from_currency']) {
            jsonResponse(['error' => 'Proposals do not match'], 400);
        }
    }

    // Create transaction
    try {
        $db->beginTransaction();

        $transaction_code = generateTransactionCode();

        $stmt = $db->prepare("
            INSERT INTO transactions (
                transaction_code, proposal_a_id, proposal_b_id,
                user_a_id, user_b_id,
                amount_a, currency_a, amount_b, currency_b,
                status
            ) VALUES (
                :code, :prop_a, :prop_b,
                :user_a, :user_b,
                :amount_a, :currency_a, :amount_b, :currency_b,
                'pending'
            )
        ");

        $stmt->execute([
            'code' => $transaction_code,
            'prop_a' => $proposal['id'],
            'prop_b' => $matching_proposal_id,
            'user_a' => $proposal['user_id'],
            'user_b' => $user['id'],
            'amount_a' => $proposal['send_amount'],
            'currency_a' => $proposal['from_currency'],
            'amount_b' => $matching_proposal_id ? $user_proposal['send_amount'] : 0,
            'currency_b' => $matching_proposal_id ? $user_proposal['from_currency'] : ''
        ]);

        $transaction_id = $db->lastInsertId();

        // Update proposals
        $stmt = $db->prepare("UPDATE proposals SET status = 'matched', matched_proposal_id = :matched WHERE id = :id");
        $stmt->execute(['matched' => $matching_proposal_id, 'id' => $proposal['id']]);

        if ($matching_proposal_id) {
            $stmt->execute(['matched' => $proposal['id'], 'id' => $matching_proposal_id]);
        }

        $db->commit();

        // Send notifications
        sendNotification(
            $proposal['user_id'],
            'proposal_matched',
            'Match Encontrado!',
            "Sua proposta foi aceita. Código da transação: {$transaction_code}"
        );

        sendNotification(
            $user['id'],
            'proposal_matched',
            'Match Confirmado!',
            "Você aceitou uma proposta. Código da transação: {$transaction_code}"
        );

        jsonResponse([
            'success' => true,
            'message' => 'Proposal accepted successfully',
            'transaction_code' => $transaction_code,
            'transaction_id' => $transaction_id
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Failed to accept proposal: ' . $e->getMessage()], 500);
    }
}

// DELETE /api/proposals/:id - Cancel proposal
if ($method === 'DELETE' && $proposal_id) {
    $user = requireAuth();

    // Get proposal
    $stmt = $db->prepare("SELECT * FROM proposals WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $proposal_id, 'user_id' => $user['id']]);
    $proposal = $stmt->fetch();

    if (!$proposal) {
        jsonResponse(['error' => 'Proposal not found'], 404);
    }

    if ($proposal['status'] !== 'open') {
        jsonResponse(['error' => 'Can only cancel open proposals'], 400);
    }

    // Update status
    $stmt = $db->prepare("UPDATE proposals SET status = 'cancelled' WHERE id = :id");
    $stmt->execute(['id' => $proposal_id]);

    sendNotification(
        $user['id'],
        'proposal_cancelled',
        'Proposta Cancelada',
        'Sua proposta foi cancelada com sucesso.'
    );

    jsonResponse([
        'success' => true,
        'message' => 'Proposal cancelled successfully'
    ]);
}

jsonResponse(['error' => 'Invalid request'], 400);
