<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Parcel Model aligned to database/schema.sql
 *
 * Tables used:
 * - parcels(parcel_id, client_id, description, weight, dimensions, status, tracking_number, declared_value, shipping_cost, payment_status, tags, created_at, updated_at)
 */
class ParcelModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'parcels';

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
     * Generate a unique tracking number
     */
    private function generateTrackingNumber(): string
    {
        do {
            $trackingNumber = 'XYC' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 10));
            $exists = $this->getParcelByTrackingNumber($trackingNumber);
        } while ($exists);

        return $trackingNumber;
    }

    /**
     * List all parcels
     */
    public function getAllParcels(): array
    {
        try {
            $sql = "SELECT parcel_id, client_id, description, weight, dimensions, status, tracking_number, declared_value, shipping_cost, payment_status, tags, created_at, updated_at
                    FROM {$this->tableName}
                    ORDER BY parcel_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get parcels: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get parcel by ID
     */
    public function getParcelById(int $parcelId): ?array
    {
        try {
            $sql = "SELECT parcel_id, client_id, description, weight, dimensions, status, tracking_number, declared_value, shipping_cost, payment_status, tags, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE parcel_id = :parcel_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['parcel_id' => $parcelId])) {
                return null;
            }
            $parcel = $stmt->fetch(PDO::FETCH_ASSOC);
            return $parcel ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get parcel by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get parcel by tracking number
     */
    public function getParcelByTrackingNumber(string $trackingNumber): ?array
    {
        try {
            $sql = "SELECT parcel_id, client_id, description, weight, dimensions, status, tracking_number, declared_value, shipping_cost, payment_status, tags, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE tracking_number = :tracking_number";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['tracking_number' => $trackingNumber])) {
                return null;
            }
            $parcel = $stmt->fetch(PDO::FETCH_ASSOC);
            return $parcel ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get parcel by tracking number: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get parcels by client ID
     */
    public function getParcelsByClientId(int $clientId): array
    {
        try {
            $sql = "SELECT parcel_id, client_id, description, weight, dimensions, status, tracking_number, declared_value, shipping_cost, payment_status, tags, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE client_id = :client_id
                    ORDER BY parcel_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['client_id' => $clientId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get parcels by client ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new parcel
     * @param array{client_id:int,description?:string,weight?:float,dimensions?:string,declared_value?:float,shipping_cost?:float,payment_status?:string,tags?:string} $data
     * @return int|false Inserted parcel_id or false on failure
     */
    public function createParcel(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['client_id']) || !is_int($data['client_id']) || $data['client_id'] <= 0) {
                $this->lastError = 'Valid client_id is required';
                return false;
            }

            // Check if client exists (assuming ClientsModel is available)
            // You might want to add a check here

            $trackingNumber = $this->generateTrackingNumber();

            $sql = "INSERT INTO {$this->tableName} (client_id, description, weight, dimensions, status, tracking_number, declared_value, shipping_cost, payment_status, tags)
                    VALUES (:client_id, :description, :weight, :dimensions, :status, :tracking_number, :declared_value, :shipping_cost, :payment_status, :tags)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'client_id'       => $data['client_id'],
                'description'     => $data['description'] ?? null,
                'weight'          => $data['weight'] ?? null,
                'dimensions'      => $data['dimensions'] ?? null,
                'status'          => 'pending',
                'tracking_number' => $trackingNumber,
                'declared_value'  => $data['declared_value'] ?? null,
                'shipping_cost'   => $data['shipping_cost'] ?? null,
                'payment_status'  => $data['payment_status'] ?? 'unpaid',
                'tags'            => isset($data['tags']) ? json_encode($data['tags']) : null,
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create parcel: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update parcel fields
     */
    public function updateParcel(int $parcelId, array $data): bool
    {
        try {
            if (!$this->getParcelById($parcelId)) {
                $this->lastError = 'Parcel not found';
                return false;
            }

            $allowedFields = ['description', 'weight', 'dimensions', 'status', 'declared_value', 'shipping_cost', 'payment_status', 'tags'];
            $sets = [];
            $params = ['parcel_id' => $parcelId];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields, true)) {
                    if ($key === 'tags') {
                        $value = json_encode($value);
                    }
                    $sets[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }

            if (empty($sets)) {
                $this->lastError = 'No valid fields provided for update.';
                return false;
            }

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE parcel_id = :parcel_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update parcel: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete a parcel by ID
     */
    public function deleteParcel(int $parcelId): bool
    {
        try {
            if (!$this->getParcelById($parcelId)) {
                $this->lastError = 'Parcel not found';
                return false;
            }
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE parcel_id = :parcel_id");
            return $this->executeQuery($stmt, ['parcel_id' => $parcelId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete parcel: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update parcel status
     */
    public function updateParcelStatus(int $parcelId, string $status): bool
    {
        return $this->updateParcel($parcelId, ['status' => $status]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $parcelId, string $paymentStatus): bool
    {
        return $this->updateParcel($parcelId, ['payment_status' => $paymentStatus]);
    }

    /**
     * Get parcel with its items
     */
    public function getParcelWithItems(int $parcelId): ?array
    {
        $parcel = $this->getParcelById($parcelId);
        if (!$parcel) {
            return null;
        }

        // Load parcel items
        require_once MODEL . 'parcel_item.model.php';
        $itemModel = new ParcelItemModel();
        $items = $itemModel->getItemsByParcelId($parcelId);

        $parcel['items'] = $items;
        return $parcel;
    }

    /**
     * Create a new parcel with items
     * @param array{client_id:int,description?:string,weight?:float,dimensions?:string,declared_value?:float,shipping_cost?:float,payment_status?:string,tags?:array,items?:array} $data
     * @return int|false Inserted parcel_id or false on failure
     */
    public function createParcelWithItems(array $data): int|false
    {
        // Create the parcel first
        $parcelData = $data;
        unset($parcelData['items']); // Remove items from parcel data
        $parcelId = $this->createParcel($parcelData);
        if (!$parcelId) {
            return false;
        }

        // Create items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            require_once MODEL . 'parcel_item.model.php';
            $itemModel = new ParcelItemModel();
            foreach ($data['items'] as $item) {
                $item['parcel_id'] = $parcelId;
                if (!$itemModel->createItem($item)) {
                    $this->lastError = 'Failed to create item: ' . $itemModel->getLastError();
                    // Optionally delete the parcel if items fail, but for now just log
                    return false;
                }
            }
        }

        return $parcelId;
    }
}
