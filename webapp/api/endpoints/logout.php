<?php
/**
 * User Logout Endpoint
 * POST /api/auth/logout
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get token
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    jsonResponse(['error' => 'No token provided'], 400);
}

// Destroy session
destroySession($token);

jsonResponse([
    'success' => true,
    'message' => 'Logged out successfully'
]);
