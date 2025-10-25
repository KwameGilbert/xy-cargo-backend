<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Client Settings Model
 *
 * Handles client settings operations including timezone, language, and currency preferences.
 */

class ClientSettingsModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'client_settings';

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
            error_log("ClientSettingsModel constructor error: " . $e->getMessage());
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
     * Get client settings by client ID
     */
    public function getClientSettings(int $clientId): array
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE client_id = :client_id";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, ['client_id' => $clientId])) {
                return [];
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get client settings: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Update or create client settings
     */
    public function updateClientSettings(int $clientId, array $settings): bool
    {
        try {
            // Check if settings already exist
            $existing = $this->getClientSettings($clientId);

            if (!empty($existing)) {
                // Update existing settings
                $sql = "UPDATE {$this->tableName} SET
                       timezone = :timezone,
                       language = :language,
                       currency = :currency,
                       updated_at = CURRENT_TIMESTAMP
                       WHERE client_id = :client_id";
            } else {
                // Create new settings
                $sql = "INSERT INTO {$this->tableName} (client_id, timezone, language, currency)
                       VALUES (:client_id, :timezone, :language, :currency)";
            }

            $stmt = $this->db->prepare($sql);
            $params = [
                'client_id' => $clientId,
                'timezone' => $settings['timezone'] ?? 'UTC',
                'language' => $settings['language'] ?? 'en',
                'currency' => $settings['currency'] ?? 'USD'
            ];

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update client settings: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}