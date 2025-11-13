<?php
/**
 * User Login Endpoint
 * POST /api/auth/login
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['email']) || empty($data['password'])) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

$email = sanitize($data['email']);
$password = $data['password'];

// Get user
$db = getDB();
$stmt = $db->prepare("
    SELECT id, email, password, full_name, phone, country_code,
           rating, total_transactions, successful_transactions,
           account_status, kyc_status, created_at
    FROM users
    WHERE email = :email
");

$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['error' => 'Invalid credentials'], 401);
}

// Verify password
if (!verifyPassword($password, $user['password'])) {
    jsonResponse(['error' => 'Invalid credentials'], 401);
}

// Check account status
if ($user['account_status'] !== 'active') {
    jsonResponse(['error' => 'Account is ' . $user['account_status']], 403);
}

// Remove password from response
unset($user['password']);

// Create new session
$token = createSession($user['id']);

jsonResponse([
    'success' => true,
    'message' => 'Login successful',
    'token' => $token,
    'user' => $user
]);
