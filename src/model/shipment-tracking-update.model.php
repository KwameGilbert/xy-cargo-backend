<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';
require_once MODEL . 'shipment.model.php';

/**
 * Shipment Tracking Update Model aligned to database/schema.sql
 *
 * Tables used:
 * - shipment_tracking_updates(update_id, shipment_id, status, location, updated_at, notes)
 */
class ShipmentTrackingUpdateModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'shipment_tracking_updates';

    /** @var string */
    private string $lastError = '';

    /** @var ShipmentModel */
    protected ShipmentModel $shipmentModel;

    public function __construct()
    {
        try {
            $database = new Database();
            $connection = $database->getConnection();
            if (!$connection) {
                throw new PDOException('Database connection is null');
            }
            $this->db = $connection;
            $this->shipmentModel = new ShipmentModel();
        } catch (PDOException $e) {
            $this->lastError = 'Database connection failed: ' . $e->getMessage();
            error_log($this->lastError);
            throw $e;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
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
     * List all tracking updates
     */
    public function getAllTrackingUpdates(): array
    {
        try {
            $sql = "SELECT update_id, shipment_id, status, location, updated_at, notes
                    FROM {$this->tableName}
                    ORDER BY update_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get tracking updates: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get tracking update by ID
     */
    public function getTrackingUpdateById(int $updateId): ?array
    {
        try {
            $sql = "SELECT update_id, shipment_id, status, location, updated_at, notes
                    FROM {$this->tableName}
                    WHERE update_id = :update_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['update_id' => $updateId])) {
                return null;
            }
            $update = $stmt->fetch(PDO::FETCH_ASSOC);
            return $update ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get tracking update by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get tracking updates by shipment ID
     */
    public function getTrackingUpdatesByShipmentId(int $shipmentId): array
    {
        try {
            $sql = "SELECT update_id, shipment_id, status, location, updated_at, notes
                    FROM {$this->tableName}
                    WHERE shipment_id = :shipment_id
                    ORDER BY updated_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['shipment_id' => $shipmentId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get tracking updates by shipment ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get tracking updates by status
     */
    public function getTrackingUpdatesByStatus(string $status): array
    {
        try {
            $sql = "SELECT update_id, shipment_id, status, location, updated_at, notes
                    FROM {$this->tableName}
                    WHERE status = :status
                    ORDER BY updated_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['status' => $status])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get tracking updates by status: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new tracking update
     * @param array{shipment_id:int,status:string,location?:string,notes?:string} $data
     * @return int|false Inserted update_id or false on failure
     */
    public function createTrackingUpdate(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['shipment_id']) || !isset($data['status']) || empty($data['status'])) {
                $this->lastError = 'Shipment ID and status are required';
                return false;
            }

            // Validate shipment exists
            if (!$this->shipmentModel->getShipmentById($data['shipment_id'])) {
                $this->lastError = 'Shipment does not exist';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (shipment_id, status, location, notes)
                    VALUES (:shipment_id, :status, :location, :notes)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'shipment_id' => $data['shipment_id'],
                'status'      => $data['status'],
                'location'    => $data['location'] ?? null,
                'notes'       => $data['notes'] ?? null,
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create tracking update: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update tracking update fields
     */
    public function updateTrackingUpdate(int $updateId, array $data): bool
    {
        try {
            $allowedFields = ['status', 'location', 'notes'];
            $sets = [];
            $params = ['update_id' => $updateId];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields, true)) {
                    $sets[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }

            if (empty($sets)) {
                $this->lastError = 'No valid fields provided for update.';
                return false;
            }

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE update_id = :update_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update tracking update: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete a tracking update by ID
     */
    public function deleteTrackingUpdate(int $updateId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE update_id = :update_id");
            return $this->executeQuery($stmt, ['update_id' => $updateId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete tracking update: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}