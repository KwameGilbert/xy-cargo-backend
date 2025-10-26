<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * WarehouseActivityLogModel
 *
 * Handles warehouse activity log operations.
 * Works with warehouse_activity_log table schema.
 */
class WarehouseActivityLogModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'warehouse_activity_log';

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
            error_log("WarehouseActivityLogModel constructor error: " . $e->getMessage());
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
     * Get recent activities for a warehouse
     */
    public function getRecentActivities(int $warehouseId, int $limit = 20): array
    {
        try {
            $sql = "SELECT al.activity_id, al.warehouse_id, al.staff_id, al.action, al.description,
                           al.reference_id, al.reference_type, al.metadata, al.created_at,
                           ws.firstName, ws.lastName
                    FROM {$this->tableName} al
                    LEFT JOIN warehouse_staff ws ON al.staff_id = ws.staff_id
                    WHERE al.warehouse_id = :warehouse_id
                    ORDER BY al.created_at DESC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, [
                'warehouse_id' => $warehouseId,
                'limit' => $limit
            ])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get recent activities: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get activities by action type
     */
    public function getActivitiesByAction(int $warehouseId, string $action, int $limit = 50): array
    {
        try {
            $sql = "SELECT al.activity_id, al.warehouse_id, al.staff_id, al.action, al.description,
                           al.reference_id, al.reference_type, al.metadata, al.created_at,
                           ws.firstName, ws.lastName
                    FROM {$this->tableName} al
                    LEFT JOIN warehouse_staff ws ON al.staff_id = ws.staff_id
                    WHERE al.warehouse_id = :warehouse_id AND al.action = :action
                    ORDER BY al.created_at DESC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, [
                'warehouse_id' => $warehouseId,
                'action' => $action,
                'limit' => $limit
            ])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get activities by action: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get activities by staff member
     */
    public function getActivitiesByStaff(int $warehouseId, int $staffId, int $limit = 50): array
    {
        try {
            $sql = "SELECT al.activity_id, al.warehouse_id, al.staff_id, al.action, al.description,
                           al.reference_id, al.reference_type, al.metadata, al.created_at,
                           ws.firstName, ws.lastName
                    FROM {$this->tableName} al
                    LEFT JOIN warehouse_staff ws ON al.staff_id = ws.staff_id
                    WHERE al.warehouse_id = :warehouse_id AND al.staff_id = :staff_id
                    ORDER BY al.created_at DESC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, [
                'warehouse_id' => $warehouseId,
                'staff_id' => $staffId,
                'limit' => $limit
            ])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get activities by staff: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Log a new activity
     */
    public function logActivity(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->tableName}
                    (warehouse_id, staff_id, action, description, reference_id, reference_type, metadata, ip_address, user_agent)
                    VALUES (:warehouse_id, :staff_id, :action, :description, :reference_id, :reference_type, :metadata, :ip_address, :user_agent)";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'warehouse_id' => $data['warehouse_id'],
                'staff_id' => $data['staff_id'],
                'action' => $data['action'],
                'description' => $data['description'],
                'reference_id' => $data['reference_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'metadata' => $data['metadata'] ? json_encode($data['metadata']) : null,
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to log activity: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get activity statistics for a warehouse
     */
    public function getActivityStats(int $warehouseId, string $period = '7 days'): array
    {
        try {
            $sql = "SELECT
                        action,
                        COUNT(*) as count,
                        MAX(created_at) as last_activity
                    FROM {$this->tableName}
                    WHERE warehouse_id = :warehouse_id
                    AND created_at >= DATE_SUB(NOW(), INTERVAL " . (strpos($period, ' ') !== false ? explode(' ', $period)[0] : '7') . " DAY)
                    GROUP BY action
                    ORDER BY count DESC";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['warehouse_id' => $warehouseId])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get activity stats: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get activity timeline for a specific date range
     */
    public function getActivityTimeline(int $warehouseId, string $startDate, string $endDate): array
    {
        try {
            $sql = "SELECT
                        DATE(created_at) as date,
                        action,
                        COUNT(*) as count
                    FROM {$this->tableName}
                    WHERE warehouse_id = :warehouse_id
                    AND DATE(created_at) BETWEEN :start_date AND :end_date
                    GROUP BY DATE(created_at), action
                    ORDER BY date ASC, count DESC";

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, [
                'warehouse_id' => $warehouseId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get activity timeline: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }
}