<?php
/**
 * Notifications Endpoint
 * GET /api/notifications - List user's notifications
 * POST /api/notifications/:id/read - Mark notification as read
 * POST /api/notifications/read-all - Mark all as read
 */

$method = $_SERVER['REQUEST_METHOD'];
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$notification_id = isset($path_parts[3]) ? intval($path_parts[3]) : null;
$action = isset($path_parts[4]) ? $path_parts[4] : null;

$user = requireAuth();
$db = getDB();

// GET /api/notifications - List user's notifications
if ($method === 'GET' && !$notification_id) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $unread_only = isset($_GET['unread']) && $_GET['unread'] === '1';

    $where = "user_id = :user_id";
    if ($unread_only) {
        $where .= " AND is_read = 0";
    }

    $stmt = $db->prepare("
        SELECT *
        FROM notifications
        WHERE $where
        ORDER BY created_at DESC
        LIMIT :limit
    ");

    $stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $notifications = $stmt->fetchAll();

    // Get unread count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0");
    $stmt->execute(['user_id' => $user['id']]);
    $unread_count = $stmt->fetch()['count'];

    jsonResponse([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'total' => count($notifications)
    ]);
}

// POST /api/notifications/:id/read - Mark notification as read
if ($method === 'POST' && $notification_id && $action === 'read') {
    $stmt = $db->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = :id AND user_id = :user_id
    ");

    $stmt->execute([
        'id' => $notification_id,
        'user_id' => $user['id']
    ]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Notification not found'], 404);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);
}

// POST /api/notifications/read-all - Mark all as read
if ($method === 'POST' && !$notification_id && $path_parts[3] === 'read-all') {
    $stmt = $db->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = :user_id AND is_read = 0
    ");

    $stmt->execute(['user_id' => $user['id']]);
    $count = $stmt->rowCount();

    jsonResponse([
        'success' => true,
        'message' => "$count notifications marked as read",
        'count' => $count
    ]);
}

jsonResponse(['error' => 'Invalid request'], 400);
