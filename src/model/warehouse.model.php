<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Warehouse Model aligned to database/schema.sql
 *
 * Tables used:
 * - warehouses(warehouse_id, name, address, status, created_at, updated_at)
 */
class WarehouseModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'warehouses';

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
     * List all warehouses
     */
    public function getAllWarehouses(): array
    {
        try {
            $sql = "SELECT warehouse_id, name, address, status, created_at, updated_at
                    FROM {$this->tableName}
                    ORDER BY warehouse_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouses: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get warehouse by ID
     */
    public function getWarehouseById(int $warehouseId): ?array
    {
        try {
            $sql = "SELECT warehouse_id, name, address, status, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE warehouse_id = :warehouse_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['warehouse_id' => $warehouseId])) {
                return null;
            }
            $warehouse = $stmt->fetch(PDO::FETCH_ASSOC);
            return $warehouse ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouse by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get warehouses by status
     */
    public function getWarehousesByStatus(string $status): array
    {
        try {
            $sql = "SELECT warehouse_id, name, address, status, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE status = :status
                    ORDER BY warehouse_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['status' => $status])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouses by status: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new warehouse
     * @param array{name:string,address?:string,status?:string} $data
     * @return int|false Inserted warehouse_id or false on failure
     */
    public function createWarehouse(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['name']) || empty($data['name'])) {
                $this->lastError = 'Warehouse name is required';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (name, address, status)
                    VALUES (:name, :address, :status)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'name'    => $data['name'],
                'address' => $data['address'] ?? null,
                'status'  => $data['status'] ?? 'active',
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create warehouse: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update warehouse fields
     */
    public function updateWarehouse(int $warehouseId, array $data): bool
    {
        try {
            $allowedFields = ['name', 'address', 'status'];
            $sets = [];
            $params = ['warehouse_id' => $warehouseId];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields, true)) {
                    $sets[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }

            if (empty($sets)) {
                $this->lastError = 'No valid fields provided for warehouse update.';
                return false;
            }

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE warehouse_id = :warehouse_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update warehouse: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update warehouse status
     */
    public function updateWarehouseStatus(int $warehouseId, string $status): bool
    {
        return $this->updateWarehouse($warehouseId, ['status' => $status]);
    }

    /**
     * Delete a warehouse by ID
     */
    public function deleteWarehouse(int $warehouseId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE warehouse_id = :warehouse_id");
            return $this->executeQuery($stmt, ['warehouse_id' => $warehouseId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete warehouse: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}