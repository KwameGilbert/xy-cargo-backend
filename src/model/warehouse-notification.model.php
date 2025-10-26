<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * WarehouseNotificationModel
 *
 * Handles warehouse notifications CRUD operations.
 * Works with warehouse_notifications table schema.
 */
class WarehouseNotificationModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'warehouse_notifications';

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
            error_log("WarehouseNotificationModel constructor error: " . $e->getMessage());
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
     * Get all notifications for a warehouse (for all staff or specific staff)
     */
    public function getWarehouseNotifications(int $warehouseId, ?int $staffId = null): array
    {
        try {
            $sql = "SELECT notification_id, warehouse_id, staff_id, type, title, message, reference_id, reference_type, is_read, priority, icon, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE warehouse_id = :warehouse_id";

            $params = ['warehouse_id' => $warehouseId];

            if ($staffId !== null) {
                $sql .= " AND (staff_id IS NULL OR staff_id = :staff_id)";
                $params['staff_id'] = $staffId;
            } else {
                $sql .= " AND staff_id IS NULL"; // Only general warehouse notifications
            }

            $sql .= " ORDER BY priority DESC, created_at DESC";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, $params)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouse notifications: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get unread notifications count for a warehouse
     */
    public function getUnreadCount(int $warehouseId, ?int $staffId = null): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->tableName}
                    WHERE warehouse_id = :warehouse_id AND is_read = FALSE";

            $params = ['warehouse_id' => $warehouseId];

            if ($staffId !== null) {
                $sql .= " AND (staff_id IS NULL OR staff_id = :staff_id)";
                $params['staff_id'] = $staffId;
            } else {
                $sql .= " AND staff_id IS NULL";
            }

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, $params)) {
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
    public function markAsRead(int $notificationId, int $warehouseId): bool
    {
        try {
            $sql = "UPDATE {$this->tableName}
                    SET is_read = TRUE, updated_at = CURRENT_TIMESTAMP
                    WHERE notification_id = :notification_id AND warehouse_id = :warehouse_id";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'notification_id' => $notificationId,
                'warehouse_id' => $warehouseId
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to mark notification as read: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Mark all notifications as read for a warehouse
     */
    public function markAllAsRead(int $warehouseId, ?int $staffId = null): bool
    {
        try {
            $sql = "UPDATE {$this->tableName}
                    SET is_read = TRUE, updated_at = CURRENT_TIMESTAMP
                    WHERE warehouse_id = :warehouse_id AND is_read = FALSE";

            $params = ['warehouse_id' => $warehouseId];

            if ($staffId !== null) {
                $sql .= " AND (staff_id IS NULL OR staff_id = :staff_id)";
                $params['staff_id'] = $staffId;
            } else {
                $sql .= " AND staff_id IS NULL";
            }

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
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
                    (warehouse_id, staff_id, type, title, message, reference_id, reference_type, is_read, priority, icon)
                    VALUES (:warehouse_id, :staff_id, :type, :title, :message, :reference_id, :reference_type, :is_read, :priority, :icon)";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'warehouse_id' => $data['warehouse_id'],
                'staff_id' => $data['staff_id'] ?? null,
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'reference_id' => $data['reference_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'is_read' => $data['is_read'] ?? false,
                'priority' => $data['priority'] ?? 'normal',
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
    public function deleteNotification(int $notificationId, int $warehouseId): bool
    {
        try {
            $sql = "DELETE FROM {$this->tableName}
                    WHERE notification_id = :notification_id AND warehouse_id = :warehouse_id";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'notification_id' => $notificationId,
                'warehouse_id' => $warehouseId
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete notification: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get notifications by priority
     */
    public function getNotificationsByPriority(int $warehouseId, string $priority): array
    {
        try {
            $sql = "SELECT notification_id, warehouse_id, staff_id, type, title, message, reference_id, reference_type, is_read, priority, icon, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE warehouse_id = :warehouse_id AND priority = :priority
                    ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, [
                'warehouse_id' => $warehouseId,
                'priority' => $priority
            ])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get notifications by priority: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }
}