<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Shipment Model aligned to database/schema.sql
 *
 * Tables used:
 * - shipments(shipment_id, tracking_number, origin_country, destination_country, departure_date, arrival_date, status, priority, warehouse_id, shipped_at, expected_delivery, delivered_at, notes, created_at, updated_at)
 */
class ShipmentModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'shipments';

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
            $trackingNumber = 'SHP' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 12));
            $exists = $this->getShipmentByTrackingNumber($trackingNumber);
        } while ($exists);

        return $trackingNumber;
    }

    /**
     * List all shipments
     */
    public function getAllShipments(): array
    {
        try {
            $sql = "SELECT shipment_id, tracking_number, origin_country, destination_country, departure_date, arrival_date, status, priority, warehouse_id, shipped_at, expected_delivery, delivered_at, notes, created_at, updated_at
                    FROM {$this->tableName}
                    ORDER BY shipment_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get shipments: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get shipment by ID
     */
    public function getShipmentById(int $shipmentId): ?array
    {
        try {
            $sql = "SELECT shipment_id, tracking_number, origin_country, destination_country, departure_date, arrival_date, status, priority, warehouse_id, shipped_at, expected_delivery, delivered_at, notes, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE shipment_id = :shipment_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['shipment_id' => $shipmentId])) {
                return null;
            }
            $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
            return $shipment ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get shipment by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get shipment by tracking number
     */
    public function getShipmentByTrackingNumber(string $trackingNumber): ?array
    {
        try {
            $sql = "SELECT shipment_id, tracking_number, origin_country, destination_country, departure_date, arrival_date, status, priority, warehouse_id, shipped_at, expected_delivery, delivered_at, notes, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE tracking_number = :tracking_number";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['tracking_number' => $trackingNumber])) {
                return null;
            }
            $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
            return $shipment ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get shipment by tracking number: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get shipments by status
     */
    public function getShipmentsByStatus(string $status): array
    {
        try {
            $sql = "SELECT shipment_id, tracking_number, origin_country, destination_country, departure_date, arrival_date, status, priority, warehouse_id, shipped_at, expected_delivery, delivered_at, notes, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE status = :status
                    ORDER BY shipment_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['status' => $status])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get shipments by status: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get shipments by warehouse ID
     */
    public function getShipmentsByWarehouseId(int $warehouseId): array
    {
        try {
            $sql = "SELECT shipment_id, tracking_number, origin_country, destination_country, departure_date, arrival_date, status, priority, warehouse_id, shipped_at, expected_delivery, delivered_at, notes, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE warehouse_id = :warehouse_id
                    ORDER BY shipment_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['warehouse_id' => $warehouseId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get shipments by warehouse ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new shipment
     * @param array{tracking_number?:string,origin_country?:string,destination_country?:string,departure_date?:string,arrival_date?:string,status?:string,priority?:string,warehouse_id?:int,shipped_at?:string,expected_delivery?:string,delivered_at?:string,notes?:string} $data
     * @return int|false Inserted shipment_id or false on failure
     */
    public function createShipment(array $data): int|false
    {
        try {
            // Generate tracking number if not provided
            if (!isset($data['tracking_number']) || empty($data['tracking_number'])) {
                $data['tracking_number'] = $this->generateTrackingNumber();
            } else {
                // Check if tracking number is unique
                $existing = $this->getShipmentByTrackingNumber($data['tracking_number']);
                if ($existing) {
                    $this->lastError = 'Tracking number already exists';
                    return false;
                }
            }

            $sql = "INSERT INTO {$this->tableName} (tracking_number, origin_country, destination_country, departure_date, arrival_date, status, priority, warehouse_id, shipped_at, expected_delivery, delivered_at, notes)
                    VALUES (:tracking_number, :origin_country, :destination_country, :departure_date, :arrival_date, :status, :priority, :warehouse_id, :shipped_at, :expected_delivery, :delivered_at, :notes)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'tracking_number'     => $data['tracking_number'],
                'origin_country'      => $data['origin_country'] ?? null,
                'destination_country' => $data['destination_country'] ?? null,
                'departure_date'      => $data['departure_date'] ?? null,
                'arrival_date'        => $data['arrival_date'] ?? null,
                'status'              => $data['status'] ?? 'pending',
                'priority'            => $data['priority'] ?? 'normal',
                'warehouse_id'        => $data['warehouse_id'] ?? null,
                'shipped_at'          => $data['shipped_at'] ?? null,
                'expected_delivery'   => $data['expected_delivery'] ?? null,
                'delivered_at'        => $data['delivered_at'] ?? null,
                'notes'               => $data['notes'] ?? null,
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create shipment: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update shipment fields
     */
    public function updateShipment(int $shipmentId, array $data): bool
    {
        try {
            $allowedFields = ['tracking_number', 'origin_country', 'destination_country', 'departure_date', 'arrival_date', 'status', 'priority', 'warehouse_id', 'shipped_at', 'expected_delivery', 'delivered_at', 'notes'];
            $sets = [];
            $params = ['shipment_id' => $shipmentId];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields, true)) {
                    // Special handling for tracking_number uniqueness
                    if ($key === 'tracking_number') {
                        $existing = $this->getShipmentByTrackingNumber($value);
                        if ($existing && $existing['shipment_id'] != $shipmentId) {
                            $this->lastError = 'Tracking number already exists';
                            return false;
                        }
                    }
                    $sets[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }

            if (empty($sets)) {
                $this->lastError = 'No valid fields provided for update.';
                return false;
            }

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE shipment_id = :shipment_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update shipment: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update shipment status
     */
    public function updateShipmentStatus(int $shipmentId, string $status): bool
    {
        return $this->updateShipment($shipmentId, ['status' => $status]);
    }

    /**
     * Change tracking number
     */
    public function changeTrackingNumber(int $shipmentId, string $newTrackingNumber): bool
    {
        // Validate the new tracking number
        if (empty($newTrackingNumber)) {
            $this->lastError = 'New tracking number cannot be empty';
            return false;
        }

        // Check if the new tracking number already exists
        $existing = $this->getShipmentByTrackingNumber($newTrackingNumber);
        if ($existing) {
            $this->lastError = 'Tracking number already exists';
            return false;
        }

        return $this->updateShipment($shipmentId, ['tracking_number' => $newTrackingNumber]);
    }

    /**
     * Delete a shipment by ID
     */
    public function deleteShipment(int $shipmentId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE shipment_id = :shipment_id");
            return $this->executeQuery($stmt, ['shipment_id' => $shipmentId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete shipment: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}