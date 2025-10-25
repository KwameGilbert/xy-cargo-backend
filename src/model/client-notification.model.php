<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * ClientNotificationModel
 *
 * Handles client notifications CRUD operations.
 * Works with client_notifications table schema.
 */
class ClientNotificationModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'client_notifications';

    /** @var string */
    private string $lastError = '';

    public function __construct()
    {
        try {
            $database = new Database();
            $connection = $database->getConnection();
            if (!$connection) {
                throw new PDOException('Database connection is null');
            }
            $this->db = $connection;
        } catch (Exception $e) {
            error_log("ClientNotificationModel constructor error: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            throw $e;
        }
    }

    /**
     * Execute a prepared statement with error handling
     */
    protected function executeQuery(\PDOStatement $statement, array $params = []): bool
    {
        try {
            return $statement->execute($params);
        } catch (PDOException $e) {
            $this->lastError = 'Query execution failed: ' . $e->getMessage();
            error_log($this->lastError . ' - SQL: ' . $statement->queryString);
            return false;
        }
    }

    /**
     * Get all notifications for a client
     */
    public function getClientNotifications(int $clientId): array
    {
        try {
            $sql = "SELECT notification_id, client_id, type, title, message, reference_id, reference_type, is_read, icon, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE client_id = :client_id
                    ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['client_id' => $clientId])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get client notifications: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get unread notifications count for a client
     */
    public function getUnreadCount(int $clientId): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->tableName}
                    WHERE client_id = :client_id AND is_read = FALSE";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['client_id' => $clientId])) {
                return 0;
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get unread count: ' . $e->getMessage();
            error_log($this->lastError);
            return 0;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $clientId): bool
    {
        try {
            $sql = "UPDATE {$this->tableName}
                    SET is_read = TRUE, updated_at = CURRENT_TIMESTAMP
                    WHERE notification_id = :notification_id AND client_id = :client_id";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'notification_id' => $notificationId,
                'client_id' => $clientId
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to mark notification as read: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Mark all notifications as read for a client
     */
    public function markAllAsRead(int $clientId): bool
    {
        try {
            $sql = "UPDATE {$this->tableName}
                    SET is_read = TRUE, updated_at = CURRENT_TIMESTAMP
                    WHERE client_id = :client_id AND is_read = FALSE";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, ['client_id' => $clientId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to mark all notifications as read: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Create a new notification
     */
    public function createNotification(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->tableName}
                    (client_id, type, title, message, reference_id, reference_type, is_read, icon)
                    VALUES (:client_id, :type, :title, :message, :reference_id, :reference_type, :is_read, :icon)";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'client_id' => $data['client_id'],
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'reference_id' => $data['reference_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'is_read' => $data['is_read'] ?? false,
                'icon' => $data['icon'] ?? 'bell'
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create notification: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(int $notificationId, int $clientId): bool
    {
        try {
            $sql = "DELETE FROM {$this->tableName}
                    WHERE notification_id = :notification_id AND client_id = :client_id";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'notification_id' => $notificationId,
                'client_id' => $clientId
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete notification: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}