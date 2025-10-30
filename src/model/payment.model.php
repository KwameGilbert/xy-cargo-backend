<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Payment Model aligned to database/schema.sql
 *
 * Tables used:
 * - payments(payment_id, invoice_id, amount, payment_method, status, created_at, updated_at)
 */
class PaymentModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'payments';

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
     * List all payments
     */
    public function getAllPayments(): array
    {
        try {
            $sql = "SELECT payment_id, invoice_id, amount, payment_method, status, created_at, updated_at
                    FROM {$this->tableName}
                    ORDER BY payment_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get payments: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get payment by ID
     */
    public function getPaymentById(int $paymentId): ?array
    {
        try {
            $sql = "SELECT payment_id, invoice_id, amount, payment_method, status, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE payment_id = :payment_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['payment_id' => $paymentId])) {
                return null;
            }
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            return $payment ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get payment by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get payments by invoice ID
     */
    public function getPaymentsByInvoiceId(int $invoiceId): array
    {
        try {
            // payment_date and transaction_id are not in schema; alias created_at and a synthetic reference
            $sql = "SELECT 
                        payment_id, 
                        invoice_id, 
                        amount, 
                        payment_method, 
                        status, 
                        created_at AS payment_date, 
                        NULL AS transaction_id, 
                        created_at, 
                        updated_at
                    FROM {$this->tableName}
                    WHERE invoice_id = :invoice_id
                    ORDER BY payment_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['invoice_id' => $invoiceId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get payments by invoice ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get payments by client ID (through invoices)
     */
    public function getPaymentsByClientId(int $clientId): array
    {
        try {
            // payment_date and transaction_id are not in schema; alias accordingly
            $sql = "SELECT 
                        p.payment_id, 
                        p.invoice_id, 
                        p.amount, 
                        p.payment_method, 
                        p.status, 
                        p.created_at AS payment_date, 
                        NULL AS transaction_id, 
                        p.created_at, 
                        p.updated_at
                    FROM {$this->tableName} p
                    INNER JOIN invoices i ON p.invoice_id = i.invoice_id
                    WHERE i.client_id = :client_id
                    ORDER BY p.payment_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['client_id' => $clientId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get payments by client ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get all pending payments
     */
    public function getPendingPayments(): array
    {
        try {
            $sql = "SELECT payment_id, invoice_id, amount, payment_method, status, created_at, updated_at
                    FROM {$this->tableName}
                    WHERE status = 'pending'
                    ORDER BY payment_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get pending payments: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new payment
     * @param array{invoice_id:int,amount:float,payment_method:string,status?:string} $data
     * @return int|false Inserted payment_id or false on failure
     */
    public function createPayment(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['invoice_id']) || !is_int($data['invoice_id']) || $data['invoice_id'] <= 0) {
                $this->lastError = 'Valid invoice_id is required';
                return false;
            }
            if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
                $this->lastError = 'Valid amount greater than 0 is required';
                return false;
            }
            if (!isset($data['payment_method']) || empty($data['payment_method'])) {
                $this->lastError = 'Payment method is required';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (invoice_id, amount, payment_method, status)
                    VALUES (:invoice_id, :amount, :payment_method, :status)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'invoice_id'     => $data['invoice_id'],
                'amount'         => $data['amount'],
                'payment_method' => $data['payment_method'],
                'status'         => $data['status'] ?? 'pending',
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create payment: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update payment fields
     */
    public function updatePayment(int $paymentId, array $data): bool
    {
        try {
            $allowedFields = ['amount', 'payment_method', 'status'];
            $sets = [];
            $params = ['payment_id' => $paymentId];

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

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE payment_id = :payment_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update payment: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $paymentId, string $status): bool
    {
        return $this->updatePayment($paymentId, ['status' => $status]);
    }

    /**
     * Delete a payment by ID
     */
    public function deletePayment(int $paymentId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE payment_id = :payment_id");
            return $this->executeQuery($stmt, ['payment_id' => $paymentId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete payment: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}