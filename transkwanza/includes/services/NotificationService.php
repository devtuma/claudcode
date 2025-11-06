<?php
/**
 * TransKwanza - Notification Service
 *
 * Handles all notification-related operations
 */

class TK_NotificationService {

    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'tk_notifications';
    }

    /**
     * Create a new notification
     *
     * @param int $user_id User ID
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $data Additional data
     * @return int|false Notification ID or false on error
     */
    public function create($user_id, $type, $title, $message, $data = []) {
        $result = $this->wpdb->insert($this->table_name, [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'is_read' => 0,
            'created_at' => current_time('mysql')
        ]);

        if ($result) {
            $notification_id = $this->wpdb->insert_id;

            // Send email notification
            $this->sendEmailNotification($user_id, $title, $message);

            return $notification_id;
        }

        return false;
    }

    /**
     * Get user's notifications
     *
     * @param int $user_id User ID
     * @param bool $unread_only Only unread notifications
     * @param int $limit Number of notifications to retrieve
     * @return array Array of notifications
     */
    public function getUserNotifications($user_id, $unread_only = false, $limit = 50) {
        $sql = "SELECT * FROM {$this->table_name} WHERE user_id = %d";
        $params = [$user_id];

        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }

    /**
     * Mark notification as read
     *
     * @param int $notification_id Notification ID
     * @param int $user_id User ID (for permission check)
     * @return bool Success
     */
    public function markAsRead($notification_id, $user_id) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql')
            ],
            [
                'id' => $notification_id,
                'user_id' => $user_id
            ]
        ) !== false;
    }

    /**
     * Mark all notifications as read
     *
     * @param int $user_id User ID
     * @return bool Success
     */
    public function markAllAsRead($user_id) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql')
            ],
            ['user_id' => $user_id]
        ) !== false;
    }

    /**
     * Get unread count
     *
     * @param int $user_id User ID
     * @return int Unread count
     */
    public function getUnreadCount($user_id) {
        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }

    /**
     * Delete notification
     *
     * @param int $notification_id Notification ID
     * @param int $user_id User ID (for permission check)
     * @return bool Success
     */
    public function delete($notification_id, $user_id) {
        return $this->wpdb->delete(
            $this->table_name,
            [
                'id' => $notification_id,
                'user_id' => $user_id
            ]
        ) !== false;
    }

    /**
     * Send email notification
     *
     * @param int $user_id User ID
     * @param string $title Email subject
     * @param string $message Email message
     */
    private function sendEmailNotification($user_id, $title, $message) {
        // Get user email from WordPress
        $user_table = $this->wpdb->prefix . 'tk_users';
        $tk_user = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT wp_user_id FROM $user_table WHERE id = %d",
            $user_id
        ));

        if ($tk_user) {
            $wp_user = get_userdata($tk_user->wp_user_id);
            if ($wp_user && $wp_user->user_email) {
                $headers = ['Content-Type: text/html; charset=UTF-8'];

                $email_body = $this->getEmailTemplate($title, $message);

                wp_mail($wp_user->user_email, '[TransKwanza] ' . $title, $email_body, $headers);
            }
        }
    }

    /**
     * Get email template
     *
     * @param string $title Email title
     * @param string $message Email message
     * @return string HTML email template
     */
    private function getEmailTemplate($title, $message) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1a1a1a; color: #fff; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; padding: 12px 30px; background: #4CAF50; color: #fff; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>TransKwanza</h1>
                    <p>Plataforma de remessas cruzadas Segura</p>
                </div>
                <div class="content">
                    <h2>' . esc_html($title) . '</h2>
                    <p>' . nl2br(esc_html($message)) . '</p>
                    <a href="' . home_url('/dashboard') . '" class="button">Acessar Dashboard</a>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' TransKwanza. Todos os direitos reservados.</p>
                    <p>Suporte: <a href="https://wa.me/5511934363623">WhatsApp +55 11 93436-3623</a></p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Clean old notifications (cron job)
     * Delete read notifications older than 30 days
     */
    public function cleanOldNotifications() {
        $this->wpdb->query(
            "DELETE FROM {$this->table_name}
            WHERE is_read = 1
            AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
    }
}
