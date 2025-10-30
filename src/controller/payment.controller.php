<?php

declare(strict_types=1);

require_once MODEL . 'payment.model.php';

/**
 * PaymentsController
 *
 * Handles payment CRUD operations.
 * Works with PaymentModel.
 */
class PaymentsController
{
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        // NOTE: The MODEL constant should be defined to point to the directory 
        // containing the PaymentModel.php file.
        $this->paymentModel = new PaymentModel();
    }

    /**
     * Get payments with optional date filtering
     */
    public function getPayments(): array
    {
        // Date filtering parameters are often passed via Request handler, 
        // but since the original implementation didn't show the request flow, 
        // we'll leave it simple for now, relying on the model's default behavior.
        $payments = $this->paymentModel->getPayments();
        $summary = $this->paymentModel->getPaymentSummary();
        
        return [
            'status' => !empty($payments) ? 'success' : 'error',
            'summary' => $summary,
            'payments' => $payments,
            'message' => empty($payments) ? 'No payments found' : null,
            'code' => !empty($payments) ? 200 : 404,
        ];
    }

    /**
     * Get payment by ID
     */
    public function getPaymentById(int $paymentId): array
    {
        if (!$paymentId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid payment ID'
            ];
        }

        $payment = $this->paymentModel->getPaymentById($paymentId);
        if (!$payment) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Payment not found'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'payment' => $payment,
            'message' => null
        ];
    }

    /**
     * Get payments by PARCEL ID (Updated from getPaymentsByInvoiceId)
     */
    public function getPaymentsByParcelId(int $parcelId): array
    {
        if (!$parcelId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid parcel ID'
            ];
        }

        $payments = $this->paymentModel->getPaymentsByParcelId($parcelId);
        return [
            'status' => !empty($payments) ? 'success' : 'error',
            'payments' => $payments,
            'message' => empty($payments) ? 'No payments found for this parcel' : null,
            'code' => !empty($payments) ? 200 : 404,
        ];
    }
    
    // NOTE: The original getPaymentsByInvoiceId method is removed/replaced.
    // If you need to keep a client filter:
    public function getPaymentsByClientId(int $clientId): array
    {
         if (!$clientId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid client ID'
            ];
        }

        $payments = $this->paymentModel->getPaymentsByClientId($clientId);
        return [
            'status' => !empty($payments) ? 'success' : 'error',
            'payments' => $payments,
            'message' => empty($payments) ? 'No payments found for this client' : null,
            'code' => !empty($payments) ? 200 : 404,
        ];
    }

    /**
     * Get all pending payments
     */
    public function getPendingPayments(): array
    {
        $payments = $this->paymentModel->getPendingPayments();
        return [
            'status' => !empty($payments) ? 'success' : 'error',
            'payments' => $payments,
            'message' => empty($payments) ? 'No pending payments found' : null,
            'code' => !empty($payments) ? 200 : 404,
        ];
    }

    /**
     * Create a new payment (Updated required fields)
     * Expected data: parcel_id, client_id, amount, payment_method, status?
     */
    public function createPayment(array $data): array
    {
        // REQUIRED FIELDS CHANGED: invoice_id removed, parcel_id and client_id added
        $required = ['parcel_id', 'client_id', 'amount', 'payment_method'];
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Missing required fields: ' . implode(', ', $missing),
            ];
        }

        $paymentId = $this->paymentModel->createPayment($data);
        if ($paymentId === false) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to create payment: ' . $this->paymentModel->getLastError(),
            ];
        }

        $payment = $this->paymentModel->getPaymentById((int) $paymentId);
        return [
            'status' => 'success',
            'code' => 201,
            'payment' => $payment,
            'message' => 'Payment created successfully',
        ];
    }

    /**
     * Update payment fields
     * Allowed fields: amount, payment_method, status
     */
    public function updatePayment(int $paymentId, array $data): array
    {
        $existing = $this->paymentModel->getPaymentById($paymentId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Payment not found'
            ];
        }

        $updated = $this->paymentModel->updatePayment($paymentId, $data);
        if (!$updated) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update payment: ' . $this->paymentModel->getLastError(),
            ];
        }

        $payment = $this->paymentModel->getPaymentById($paymentId);
        return [
            'status' => 'success',
            'code' => 200,
            'payment' => $payment,
            'message' => 'Payment updated successfully',
        ];
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $paymentId, string $status): array
    {
        $existing = $this->paymentModel->getPaymentById($paymentId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Payment not found'
            ];
        }

        $updated = $this->paymentModel->updatePaymentStatus($paymentId, $status);
        if (!$updated) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update payment status: ' . $this->paymentModel->getLastError(),
            ];
        }

        $payment = $this->paymentModel->getPaymentById($paymentId);
        return [
            'status' => 'success',
            'code' => 200,
            'payment' => $payment,
            'message' => 'Payment status updated successfully',
        ];
    }

    /**
     * Delete a payment
     */
    public function deletePayment(int $paymentId): array
    {
        $existing = $this->paymentModel->getPaymentById($paymentId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Payment not found'
            ];
        }

        $deleted = $this->paymentModel->deletePayment($paymentId);
        return [
            'status' => $deleted ? 'success' : 'error',
            'code' => $deleted ? 200 : 500,
            'message' => $deleted ? 'Payment deleted successfully' : ('Failed to delete payment: ' . $this->paymentModel->getLastError()),
        ];
    }
}