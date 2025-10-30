<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Payment Model aligned to database/schema.sql
 *
 * Tables used:
 * - payments(payment_id, parcel_id, client_id, amount, payment_method, status, created_at, updated_at)
 * - clients
 * - parcels
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
     * Get payments with optional date filtering and client/parcel details.
     * The date filtering parameters are kept for compatibility with the service layer, 
     * but the logic is removed from the React component.
     * * @param string|null $startDate Optional start date for filtering (Y-m-d format)
     * @param string|null $endDate Optional end date for filtering (Y-m-d format)
     * @param string|null $period Optional predefined period (today, week, month, year)
     * @return array List of payments with client/parcel details
     */
    public function getPayments(?string $startDate = null, ?string $endDate = null, ?string $period = null): array
    {
        try {
            // Updated SQL to join clients and parcels instead of invoices
            $sql = "SELECT
                p.payment_id,
                p.parcel_id,
                p.client_id,
                p.amount,
                p.payment_method,
                p.status,
                p.created_at AS payment_date, -- Alias for date paid
                p.updated_at,
                c.firstName AS client_first_name, 
                c.lastName AS client_last_name,
                CONCAT(c.firstName, ' ', c.lastName) AS customer_name,
                l.tracking_number -- Assuming 'parcels' table has 'tracking_number' and aliasing as 'l'
            FROM
                {$this->tableName} p
            LEFT JOIN
                clients c ON p.client_id = c.client_id
            LEFT JOIN
                parcels l ON p.parcel_id = l.parcel_id
            WHERE
                1=1 
            ORDER BY
                p.payment_id DESC";
            
            // NOTE: Date filtering logic removed as requested in previous steps.

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
     * Get payment summary statistics
     * The logic is simplified since there is no 'unpaid invoice' concept now.
     * Pending is defined by payments with status 'pending' in the payments table.
     * * @return array Summary statistics
     */
    public function getPaymentSummary(): array
    {
        try {
            // Fetch all summary stats in one query using SUM/CASE
            $sql = "SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_collected,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_payment
            FROM 
                {$this->tableName}";
            
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) { 
            $this->lastError = 'Failed to get payment summary: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get payment by ID
     * Includes client and parcel details.
     */
    public function getPaymentById(int $paymentId): ?array
    {
        try {
            $sql = "SELECT
                p.*,
                c.firstName AS client_first_name, 
                c.lastName AS client_last_name
            FROM
                {$this->tableName} p
            LEFT JOIN
                clients c ON p.client_id = c.client_id
            WHERE
                p.payment_id = :payment_id";
            
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
     * Get payments by parcel ID (Replaces getPaymentsByInvoiceId)
     */
    public function getPaymentsByParcelId(int $parcelId): array
    {
        try {
            // Note: The original function was getPaymentsByInvoiceId
            $sql = "SELECT 
                        payment_id, 
                        parcel_id, 
                        client_id,
                        amount, 
                        payment_method, 
                        status, 
                        created_at AS payment_date, 
                        created_at, 
                        updated_at
                    FROM {$this->tableName}
                    WHERE parcel_id = :parcel_id
                    ORDER BY payment_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['parcel_id' => $parcelId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get payments by parcel ID: ' . $e->getMessage();
            return [];
        }
    }

    // Retained getPaymentsByClientId as it only needs p.* and is correctly joined to clients

    /**
     * Get all pending payments
     * Includes client details for better context in the frontend.
     */
    public function getPendingPayments(): array
    {
        try {
            $sql = "SELECT 
                        p.payment_id, 
                        p.parcel_id, 
                        p.client_id,
                        p.amount, 
                        p.payment_method, 
                        p.status, 
                        p.created_at AS payment_date, 
                        c.firstName AS client_first_name, 
                        c.lastName AS client_last_name
                    FROM {$this->tableName} p
                    LEFT JOIN clients c ON p.client_id = c.client_id
                    WHERE p.status = 'pending'
                    ORDER BY p.payment_id DESC";
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
     * @param array{parcel_id:int,client_id:int,amount:float,payment_method:string,status?:string} $data
     * @return int|false Inserted payment_id or false on failure
     */
    public function createPayment(array $data): int|false
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
            if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
                $this->lastError = 'Valid amount greater than 0 is required';
                return false;
            }
            if (!isset($data['payment_method']) || empty($data['payment_method'])) {
                $this->lastError = 'Payment method is required';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (parcel_id, client_id, amount, payment_method, status)
                    VALUES (:parcel_id, :client_id, :amount, :payment_method, :status)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'parcel_id'      => $data['parcel_id'],
                'client_id'      => $data['client_id'],
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

    // --- Other methods (updatePayment, updatePaymentStatus, deletePayment) are omitted as they require no structural changes ---
    
    // ... insert original updatePayment, updatePaymentStatus, deletePayment methods here ...

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

    // The method getPaymentsByClientId is kept but its logic is now aligned with the new schema (although it's already joining the payments table which contains client_id, the existing logic joins to invoices, which no longer makes sense):
    /**
     * Get payments by client ID
     */
    public function getPaymentsByClientId(int $clientId): array
    {
        try {
            // Updated SQL to directly query the client_id field now present in the payments table
            $sql = "SELECT 
                        p.payment_id, 
                        p.parcel_id, 
                        p.client_id,
                        p.amount, 
                        p.payment_method, 
                        p.status, 
                        p.created_at AS payment_date, 
                        p.created_at, 
                        p.updated_at
                    FROM {$this->tableName} p
                    WHERE p.client_id = :client_id
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
}