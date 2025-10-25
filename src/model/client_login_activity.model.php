<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Client Login Activity Model
 *
 * Handles client login activity logging and retrieval.
 */

class ClientLoginActivityModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'client_login_activity';

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
            error_log("ClientLoginActivityModel constructor error: " . $e->getMessage());
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
     * Log a client login activity
     */
    public function logLoginActivity(int $clientId, string $ipAddress, string $userAgent, string $status = 'success'): bool
    {
        try {
            // Parse user agent for device info
            $deviceInfo = $this->parseUserAgent($userAgent);

            // Get location info from IP (simplified - in production you'd use a geolocation service)
            $location = $this->getLocationFromIP($ipAddress);

            $sql = "INSERT INTO {$this->tableName}
                   (client_id, ip_address, user_agent, device_info, location, status)
                   VALUES (:client_id, :ip_address, :user_agent, :device_info, :location, :status)";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'client_id' => $clientId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'device_info' => $deviceInfo,
                'location' => $location,
                'status' => $status
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to log login activity: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get client login activity history
     */
    public function getClientLoginHistory(int $clientId, int $limit = 10): array
    {
        try {
            $sql = "SELECT activity_id, login_time, ip_address, device_info, location, status
                   FROM {$this->tableName}
                   WHERE client_id = :client_id
                   ORDER BY login_time DESC
                   LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue('client_id', $clientId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);

            if (!$this->executeQuery($stmt)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get client login history: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get recent login activities for security monitoring
     */
    public function getRecentLoginActivities(int $clientId, int $hours = 24): array
    {
        try {
            $sql = "SELECT activity_id, login_time, ip_address, device_info, location, status
                   FROM {$this->tableName}
                   WHERE client_id = :client_id
                   AND login_time >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :hours HOUR)
                   ORDER BY login_time DESC";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'client_id' => $clientId,
                'hours' => $hours
            ]) ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get recent login activities: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Parse user agent string to extract device info
     */
    private function parseUserAgent(string $userAgent): string
    {
        // Simple user agent parsing - in production, use a proper library
        if (strpos($userAgent, 'Mobile') !== false) {
            return 'Mobile App';
        } elseif (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome / Desktop';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox / Desktop';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari / Desktop';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge / Desktop';
        } else {
            return 'Unknown Device';
        }
    }

    /**
     * Get location from IP address (simplified)
     */
    private function getLocationFromIP(string $ipAddress): string
    {
        // In production, use a geolocation service like MaxMind GeoIP
        // For now, return a placeholder
        return 'Unknown Location';
    }

    /**
     * Clean up old login activity records (keep last 100 per client)
     */
    public function cleanupOldRecords(int $clientId, int $keepRecords = 100): bool
    {
        try {
            $sql = "DELETE FROM {$this->tableName}
                   WHERE client_id = :client_id
                   AND activity_id NOT IN (
                       SELECT activity_id FROM (
                           SELECT activity_id
                           FROM {$this->tableName}
                           WHERE client_id = :client_id
                           ORDER BY login_time DESC
                           LIMIT :keep_records
                       ) AS recent
                   )";

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'client_id' => $clientId,
                'keep_records' => $keepRecords
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to cleanup old records: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}