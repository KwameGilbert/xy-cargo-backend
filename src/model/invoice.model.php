<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Invoice Model aligned to database/schema.sql
 *
 * Tables used:
 * - invoices(invoice_id, parcel_id, client_id, amount, status, created_at, updated_at)
 */
class InvoiceModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'invoices';

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
     * List all invoices
     */
    public function getAllInvoices(): array
    {
        try {
            $sql = "SELECT invoice_id, parcel_id, client_id, amount, status, created_at, updated_at
                    FROM {$this->tableName}
                    ORDER BY invoice_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get invoices: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get invoice by ID
     */
    public function getInvoiceById(int $invoiceId): ?array
    {
        try {
            $sql = "SELECT invoice_id, parcel_id, client_id, amount, status, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['invoice_id' => $invoiceId])) {
                return null;
            }
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            return $invoice ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get invoice by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get invoices by parcel ID
     */
    public function getInvoicesByParcelId(int $parcelId): array
    {
        try {
            $sql = "SELECT invoice_id, parcel_id, client_id, amount, status, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE parcel_id = :parcel_id
                    ORDER BY invoice_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['parcel_id' => $parcelId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get invoices by parcel ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get invoices by client ID
     */
    public function getInvoicesByClientId(int $clientId): array
    {
        try {
            $sql = "SELECT invoice_id, parcel_id, client_id, amount, status, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE client_id = :client_id
                    ORDER BY invoice_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['client_id' => $clientId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get invoices by client ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new invoice
     * @param array{parcel_id:int,client_id:int,amount:float,status?:string} $data
     * @return int|false Inserted invoice_id or false on failure
     */
    public function createInvoice(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['parcel_id']) || !is_int($data['parcel_id']) || $data['parcel_id'] <= 0) {
                $this->lastError = 'Valid parcel_id is required';
                return false;
            }
            if (!isset($data['client_id']) || !is_int($data['client_id']) || $data['client_id'] <= 0) {
                $this->lastError = 'Valid client_id is required';
                return false;
            }
            if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] < 0) {
                $this->lastError = 'Valid amount is required';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (parcel_id, client_id, amount, status)
                    VALUES (:parcel_id, :client_id, :amount, :status)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'parcel_id' => $data['parcel_id'],
                'client_id' => $data['client_id'],
                'amount'    => $data['amount'],
                'status'    => $data['status'] ?? 'unpaid',
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create invoice: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update invoice fields
     */
    public function updateInvoice(int $invoiceId, array $data): bool
    {
        try {
            $allowedFields = ['amount', 'status'];
            $sets = [];
            $params = ['invoice_id' => $invoiceId];

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

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE invoice_id = :invoice_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update invoice: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete an invoice by ID
     */
    public function deleteInvoice(int $invoiceId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE invoice_id = :invoice_id");
            return $this->executeQuery($stmt, ['invoice_id' => $invoiceId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete invoice: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update invoice status
     */
    public function updateInvoiceStatus(int $invoiceId, string $status): bool
    {
        return $this->updateInvoice($invoiceId, ['status' => $status]);
    }
}