<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Shipment Type Model
 * Table
 * - shipment_type (type_id, name, description, created_at, updated_at)
 */
class ShipmentTypeModel{
     /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'shipment_types';

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
     * List all shipment types
     */
    public function getAllShipmentTypes(): array
    {
        try {
            $sql = "SELECT type_id, name, description, created_at, updated_at
                    FROM {$this->tableName} 
                    ORDER BY type_id DESC";
            $stmt = $this->db->prepare($sql);
            if(!$this->executeQuery($stmt)){
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get shipment types: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get shipment type by ID
     * @param $shipmentTypeId
     */
    public function getShipmentTypeById(int $shipmentTypeId): ?array
    {
         try {
            $sql = "SELECT type_id, name, description, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE type_id = :type_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['type_id' => $shipmentTypeId])) {
                return null;
            }
            $shipmentType = $stmt->fetch(PDO::FETCH_ASSOC);
            return $shipmentType ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get shipment type by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Create a new shipment type
     * @param array{name:string,description?:string} $data
     * @return int|false Inserted type_id or false on failure
     */
    public function createShipmentType(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['name']) || empty($data['name'])) {
                $this->lastError = 'Shipment type name is required';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (name, description)
                    VALUES (:name, :description)";
            $stmt = $this->db->prepare($sql);
            $params = [
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null
            ];

            if(!$this->executeQuery($stmt, $params)){
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create shipment type: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update a shipment type
     * @param int $shipmentTypeId
     * @param array $data
     */
    public function updateShipmentType(int $shipmentTypeId, array $data): bool{
       try {
            $allowedFields = ['name', 'description'];
            $sets = [];
            $params = ['type_id' => $shipmentTypeId];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields, true)) {
                    $sets[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }

            if (empty($sets)) {
                $this->lastError = 'No valid fields provided for shipment type update.';
                return false;
            }

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE type_id = :type_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update shipment type: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete shipment type
     * @param int $shipmentTypeId
     */
    public function deleteShipmentType(int $shipmentTypeId) :bool{
         try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE type_id = :type_id");
            return $this->executeQuery($stmt, ['type_id' => $shipmentTypeId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete shipment type: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}

