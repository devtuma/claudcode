<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['email', 'password', 'full_name', 'country_code'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        jsonResponse(['error' => "Field '$field' is required"], 400);
    }
}

// Validate email
if (!validateEmail($data['email'])) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

// Validate password strength
if (strlen($data['password']) < 8) {
    jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
}

// Sanitize inputs
$email = sanitize($data['email']);
$full_name = sanitize($data['full_name']);
$country_code = sanitize($data['country_code']);
$phone = isset($data['phone']) ? sanitize($data['phone']) : null;
$document_type = isset($data['document_type']) ? sanitize($data['document_type']) : null;
$document_number = isset($data['document_number']) ? sanitize($data['document_number']) : null;

// Check if email already exists
$db = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    jsonResponse(['error' => 'Email already registered'], 409);
}

// Verify country exists
$stmt = $db->prepare("SELECT id FROM countries WHERE country_code = :code AND status = 'active'");
$stmt->execute(['code' => $country_code]);

if (!$stmt->fetch()) {
    jsonResponse(['error' => 'Invalid country code'], 400);
}

// Hash password
$password_hash = hashPassword($data['password']);

// Insert user
$stmt = $db->prepare("
    INSERT INTO users (
        email, password, full_name, phone, country_code,
        document_type, document_number, account_status, kyc_status
    ) VALUES (
        :email, :password, :full_name, :phone, :country_code,
        :document_type, :document_number, 'active', 'not_started'
    )
");

try {
    $stmt->execute([
        'email' => $email,
        'password' => $password_hash,
        'full_name' => $full_name,
        'phone' => $phone,
        'country_code' => $country_code,
        'document_type' => $document_type,
        'document_number' => $document_number
    ]);

    $user_id = $db->lastInsertId();

    // Create session
    $token = createSession($user_id);

    // Get user data
    $stmt = $db->prepare("SELECT id, email, full_name, phone, country_code, rating, total_transactions, account_status, kyc_status, created_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch();

    // Send welcome notification
    sendNotification(
        $user_id,
        'welcome',
        'Bem-vindo ao TransKwanza!',
        'Sua conta foi criada com sucesso. Comece criando sua primeira proposta.'
    );

    jsonResponse([
        'success' => true,
        'message' => 'User registered successfully',
        'token' => $token,
        'user' => $user
    ], 201);

} catch (Exception $e) {
    jsonResponse(['error' => 'Registration failed: ' . $e->getMessage()], 500);
}
