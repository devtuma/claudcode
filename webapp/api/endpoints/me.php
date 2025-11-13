<?php
/**
 * Get Current User Endpoint
 * GET /api/auth/me
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = requireAuth();

// Remove sensitive data
unset($user['password']);
unset($user['session_token']);

jsonResponse([
    'success' => true,
    'user' => $user
]);
