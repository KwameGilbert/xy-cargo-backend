<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Parcel Item Model aligned to database/schema.sql
 *
 * Tables used:
 * - parcel_items(item_id, parcel_id, name, quantity, value, weight, height, width, length, fragile, special_packaging, created_at, updated_at)
 */
class ParcelItemModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'parcel_items';

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
     * Get items by parcel ID
     */
    public function getItemsByParcelId(int $parcelId): array
    {
        try {
            $sql = "SELECT item_id, parcel_id, name, description, quantity, value, weight, height, width, length, fragile, special_packaging, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE parcel_id = :parcel_id
                    ORDER BY item_id ASC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['parcel_id' => $parcelId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get items by parcel ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new parcel item
     * @param array{parcel_id:int,name:string,quantity?:int,value?:float,weight?:float,height?:float,width?:float,length?:float,fragile?:bool,special_packaging?:bool} $data
     * @return int|false Inserted item_id or false on failure
     */
    public function createItem(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['parcel_id']) || !is_int($data['parcel_id']) || $data['parcel_id'] <= 0) {
                $this->lastError = 'Valid parcel_id is required';
                return false;
            }
            if (!isset($data['name']) || empty($data['name'])) {
                $this->lastError = 'Item name is required';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (parcel_id, name, quantity, value, weight, height, width, length, fragile, special_packaging)
                    VALUES (:parcel_id, :name, :quantity, :value, :weight, :height, :width, :length, :fragile, :special_packaging)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'parcel_id'        => $data['parcel_id'],
                'name'             => $data['name'],
                'quantity'         => $data['quantity'] ?? 1,
                'value'            => $data['value'] ?? null,
                'weight'           => $data['weight'] ?? null,
                'height'           => $data['height'] ?? null,
                'width'            => $data['width'] ?? null,
                'length'           => $data['length'] ?? null,
                'fragile'          => $data['fragile'] ?? false,
                'special_packaging' => $data['special_packaging'] ?? false,
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create item: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update item fields
     */
    public function updateItem(int $itemId, array $data): bool
    {
        try {
            $allowedFields = ['name', 'quantity', 'value', 'weight', 'height', 'width', 'length', 'fragile', 'special_packaging'];
            $sets = [];
            $params = ['item_id' => $itemId];

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

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE item_id = :item_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update item: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete an item by ID
     */
    public function deleteItem(int $itemId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE item_id = :item_id");
            return $this->executeQuery($stmt, ['item_id' => $itemId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete item: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete all items for a parcel
     */
    public function deleteItemsByParcelId(int $parcelId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE parcel_id = :parcel_id");
            return $this->executeQuery($stmt, ['parcel_id' => $parcelId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete items by parcel ID: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}