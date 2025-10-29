<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Client Model aligned to database/schema.sql
 *
 * Tables used:
 * - clients(client_id, firstName, lastName, email, phone, address, company, password_hash, created_at, updated_at)
 */
class ClientsModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'clients';

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
     * List all clients (excludes password_hash for security)
     */
    public function getAllClients(): array
    {
        try {
            $sql = "SELECT client_id, firstName, lastName, email, phone, address, company, created_at, updated_at
                    FROM {$this->tableName}
                    ORDER BY client_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get clients: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    public function getClientById(int $clientId): ?array
    {
        try {
            $sql = "SELECT client_id, firstName, lastName, email, phone, address, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE client_id = :client_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['client_id' => $clientId])) {
                return null;
            }
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            return $client ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get client by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    public function getClientByEmail(string $email): ?array
    {
        try {
            $sql = "SELECT client_id, firstName, lastName, email, phone, address, password_hash, company, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['email' => $email])) {
                return null;
            }
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            return $client ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get client by email: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    public function getClientByPhone(string $phone): ?array
    {
        try {
            $sql = "SELECT client_id, firstName, lastName, email, phone, address, company, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE phone = :phone";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['phone' => $phone])) {
                return null;
            }
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            return $client ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get client by phone: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Create a new client
     * @param array{firstName:string,lastName:string,email:string,phone?:string,address?:string,company?:string,password_hash:string} $data
     * @return int|false Inserted client_id or false on failure
     */
    public function createClient(array $data): int|false
    {
        try {
            // Validate required fields
            $required = ['firstName', 'lastName', 'email', 'phone','password_hash'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $this->lastError = "Missing required field: {$field}";
                    return false;
                }
            }

            // Enforce uniqueness of email
            if ($this->getClientByEmail($data['email'])) {
                $this->lastError = 'Client already exists with this email';
                return false;
            }

            if ($this->getClientByPhone($data['phone'] ?? '')) {
                $this->lastError = 'Client already exists with this phone number';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (firstName, lastName, email, phone, address, company, password_hash)
                    VALUES (:firstName, :lastName, :email, :phone, :address, :company, :password_hash)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'firstName'    => $data['firstName'],
                'lastName'     => $data['lastName'],
                'email'        => $data['email'],
                'phone'        => $data['phone'] ?? null,
                'address'      => $data['address'] ?? null,
                'company'      => $data['company'] ?? null,
                'password_hash' => $data['password_hash'],
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create client: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update client fields (excludes password_hash; use updatePassword for that)
     */
    public function updateClient(int $clientId, array $data): bool
    {
        try {
            if (!$this->getClientById($clientId)) {
                $this->lastError = 'Client not found';
                return false;
            }

            $allowedFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'company'];
            $sets = [];
            $params = ['client_id' => $clientId];

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

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE client_id = :client_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update client: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update password for a client
     */
    public function updateClientPassword(int $clientId, string $newPasswordHash): bool
    {
        try {
            if (!$this->getClientById($clientId)) {
                $this->lastError = 'Client not found';
                return false;
            }

            $sql = "UPDATE {$this->tableName} SET password_hash = :password_hash WHERE client_id = :client_id";
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, ['password_hash' => $newPasswordHash, 'client_id' => $clientId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update password: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Authenticate client login
     */
    public function clientLogin(string $email, string $password): ?array
    {
        try {
            $client = $this->getClientByEmail($email);
            if (!$client) {
                $this->lastError = 'User not found with this email';
                return null;
            }
            if (!password_verify($password, $client['password_hash'])) {
                $this->lastError = 'Invalid password';
                return null;
            }
            // Remove password_hash from return for security
            unset($client['password_hash']);
            
            return $client;
        } catch (PDOException $e) {
            $this->lastError = 'Login failed: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Delete a client by ID
     * @param int $clientId
     * @return bool
     */
    public function deleteClient(int $clientId): bool
    {
        try {
            if (!$this->getClientById($clientId)) {
                $this->lastError = 'Client not found';
                return false;
            }
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE client_id = :client_id");
            return $this->executeQuery($stmt, ['client_id' => $clientId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete client: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}