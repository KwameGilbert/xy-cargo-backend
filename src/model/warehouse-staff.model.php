<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Warehouse Staff Model aligned to database/schema.sql
 *
 * Tables used:
 * - warehouse_staff(staff_id, warehouse_id, firstName, lastName, email, phone, password_hash, role, status, profile_picture, created_at, updated_at)
 * - warehouses(warehouse_id) - for validation
 */
class WarehouseStaffModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'warehouse_staff';

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
     * List all warehouse staff
     */
    public function getAllWarehouseStaff(): array
    {
        try {
            $sql = "SELECT ws.staff_id, ws.warehouse_id, ws.firstName, ws.lastName, ws.email, ws.phone, ws.role, ws.status, ws.profile_picture, ws.created_at, ws.updated_at,
                           w.name as warehouse_name
                    FROM {$this->tableName} ws
                    LEFT JOIN warehouses w ON ws.warehouse_id = w.warehouse_id
                    ORDER BY ws.staff_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouse staff: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get warehouse staff by ID
     */
    public function getWarehouseStaffById(int $staffId): ?array
    {
        try {
            $sql = "SELECT ws.staff_id, ws.warehouse_id, ws.firstName, ws.lastName, ws.email, ws.phone, ws.role, ws.status, ws.profile_picture, ws.created_at, ws.updated_at,
                           w.name as warehouse_name
                    FROM {$this->tableName} ws
                    LEFT JOIN warehouses w ON ws.warehouse_id = w.warehouse_id
                    WHERE ws.staff_id = :staff_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['staff_id' => $staffId])) {
                return null;
            }
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            return $staff ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouse staff by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get warehouse staff by warehouse ID
     */
    public function getWarehouseStaffByWarehouse(int $warehouseId): array
    {
        try {
            $sql = "SELECT ws.staff_id, ws.warehouse_id, ws.firstName, ws.lastName, ws.email, ws.phone, ws.role, ws.status, ws.profile_picture, ws.created_at, ws.updated_at,
                           w.name as warehouse_name
                    FROM {$this->tableName} ws
                    LEFT JOIN warehouses w ON ws.warehouse_id = w.warehouse_id
                    WHERE ws.warehouse_id = :warehouse_id
                    ORDER BY ws.staff_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['warehouse_id' => $warehouseId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouse staff by warehouse: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get warehouse staff by status
     */
    public function getWarehouseStaffByStatus(string $status): array
    {
        try {
            $sql = "SELECT ws.staff_id, ws.warehouse_id, ws.firstName, ws.lastName, ws.email, ws.phone, ws.role, ws.status, ws.profile_picture, ws.created_at, ws.updated_at,
                           w.name as warehouse_name
                    FROM {$this->tableName} ws
                    LEFT JOIN warehouses w ON ws.warehouse_id = w.warehouse_id
                    WHERE ws.status = :status
                    ORDER BY ws.staff_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['status' => $status])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouse staff by status: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Validate warehouse exists
     */
    private function validateWarehouseExists(int $warehouseId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT warehouse_id FROM warehouses WHERE warehouse_id = :warehouse_id");
            if (!$this->executeQuery($stmt, ['warehouse_id' => $warehouseId])) {
                return false;
            }
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to validate warehouse: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Check if email is unique (excluding current staff for updates)
     */
    private function isEmailUnique(string $email, ?int $excludeStaffId = null): bool
    {
        try {
            $sql = "SELECT staff_id FROM {$this->tableName} WHERE email = :email";
            $params = ['email' => $email];

            if ($excludeStaffId !== null) {
                $sql .= " AND staff_id != :exclude_id";
                $params['exclude_id'] = $excludeStaffId;
            }

            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }
            return $stmt->fetch(PDO::FETCH_ASSOC) === false;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to check email uniqueness: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Create a new warehouse staff member
     * @param array{warehouse_id:int,firstName:string,lastName:string,email:string,password_hash:string,phone?:string,role?:string,status?:string,profile_picture?:string} $data
     * @return int|false Inserted staff_id or false on failure
     */
    public function createWarehouseStaff(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['warehouse_id']) || !isset($data['firstName']) || !isset($data['lastName']) || !isset($data['email']) || !isset($data['password_hash'])) {
                $this->lastError = 'Warehouse ID, first name, last name, email, and password are required';
                return false;
            }

            // Validate warehouse exists
            if (!$this->validateWarehouseExists($data['warehouse_id'])) {
                $this->lastError = 'Invalid warehouse ID - warehouse does not exist';
                return false;
            }

            // Validate email uniqueness
            if (!$this->isEmailUnique($data['email'])) {
                $this->lastError = 'Email address already exists';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (warehouse_id, firstName, lastName, email, phone, password_hash, role, status, profile_picture)
                    VALUES (:warehouse_id, :firstName, :lastName, :email, :phone, :password_hash, :role, :status, :profile_picture)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'warehouse_id'   => $data['warehouse_id'],
                'firstName'      => $data['firstName'],
                'lastName'       => $data['lastName'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'password_hash'  => $data['password_hash'],
                'role'           => $data['role'] ?? null,
                'status'         => $data['status'] ?? 'active',
                'profile_picture' => $data['profile_picture'] ?? null,
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create warehouse staff: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update warehouse staff fields
     */
    public function updateWarehouseStaff(int $staffId, array $data): bool
    {
        try {
            $allowedFields = ['warehouse_id', 'firstName', 'lastName', 'email', 'phone', 'role', 'status', 'profile_picture'];
            $sets = [];
            $params = ['staff_id' => $staffId];

            // Validate warehouse_id if provided
            if (isset($data['warehouse_id']) && !$this->validateWarehouseExists($data['warehouse_id'])) {
                $this->lastError = 'Invalid warehouse ID - warehouse does not exist';
                return false;
            }

            // Validate email uniqueness if email is being updated
            if (isset($data['email']) && !$this->isEmailUnique($data['email'], $staffId)) {
                $this->lastError = 'Email address already exists';
                return false;
            }

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields, true)) {
                    $sets[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }

            if (empty($sets)) {
                $this->lastError = 'No valid fields provided for warehouse staff update.';
                return false;
            }

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE staff_id = :staff_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update warehouse staff: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update warehouse staff status
     */
    public function updateWarehouseStaffStatus(int $staffId, string $status): bool
    {
        return $this->updateWarehouseStaff($staffId, ['status' => $status]);
    }

    /**
     * Authenticate warehouse staff login
     */
    public function warehouseStaffLogin(string $email, string $password): ?array
    {
        try {
            $staff = $this->getWarehouseStaffByEmail($email);
            if (!$staff) {
                $this->lastError = 'Staff member not found with this email';
                return null;
            }

            // Check if staff member is active
            if (isset($staff['status']) && $staff['status'] !== 'active') {
                $this->lastError = 'Staff account is not active';
                return null;
            }

            if (!password_verify($password, $staff['password_hash'])) {
                $this->lastError = 'Invalid password';
                return null;
            }

            // Remove password_hash from return for security
            unset($staff['password_hash']);

            return $staff;
        } catch (PDOException $e) {
            $this->lastError = 'Login failed: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get warehouse staff by email
     */
    public function getWarehouseStaffByEmail(string $email): ?array
    {
        try {
            $sql = "SELECT ws.staff_id, ws.warehouse_id, ws.firstName, ws.lastName, ws.email, ws.phone, ws.password_hash, ws.role, ws.status, ws.profile_picture, ws.created_at, ws.updated_at,
                           w.name as warehouse_name
                    FROM {$this->tableName} ws
                    LEFT JOIN warehouses w ON ws.warehouse_id = w.warehouse_id
                    WHERE ws.email = :email";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['email' => $email])) {
                return null;
            }
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            return $staff ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get warehouse staff by email: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Delete a warehouse staff member by ID
     */
    public function deleteWarehouseStaff(int $staffId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE staff_id = :staff_id");
            return $this->executeQuery($stmt, ['staff_id' => $staffId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete warehouse staff: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}