<?php
/**
 * Authentication Functions
 */

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate session token
 */
function generateToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Create session
 */
function createSession($userId) {
    $db = getDB();
    $token = generateToken();
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

    $stmt = $db->prepare("
        INSERT INTO sessions (user_id, session_token, expires_at)
        VALUES (:user_id, :token, :expires_at)
    ");

    $stmt->execute([
        'user_id' => $userId,
        'token' => $token,
        'expires_at' => $expiresAt
    ]);

    return $token;
}

/**
 * Validate session
 */
function validateSession($token) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT s.*, u.*
        FROM sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.session_token = :token
        AND s.expires_at > datetime('now')
    ");

    $stmt->execute(['token' => $token]);
    return $stmt->fetch();
}

/**
 * Get current user from request
 */
function getCurrentUser() {
    $headers = getallheaders();
    $token = null;

    // Try Authorization header
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    }

    // Try cookie
    if (!$token && isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    }

    if (!$token) {
        return null;
    }

    return validateSession($token);
}

/**
 * Require authentication
 */
function requireAuth() {
    $user = getCurrentUser();

    if (!$user) {
        jsonResponse(['error' => 'Unauthorized'], 401);
        exit;
    }

    return $user;
}

/**
 * Destroy session
 */
function destroySession($token) {
    $db = getDB();

    $stmt = $db->prepare("DELETE FROM sessions WHERE session_token = :token");
    $stmt->execute(['token' => $token]);

    return true;
}
