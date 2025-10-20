<?php

declare(strict_types=1);

require_once MODEL . 'invoice.model.php';

/**
 * InvoicesController
 *
 * Handles invoice CRUD operations.
 * Works with InvoiceModel.
 */
class InvoicesController
{
    protected InvoiceModel $invoiceModel;

    public function __construct()
    {
        $this->invoiceModel = new InvoiceModel();
    }

    /**
     * Get all invoices
     */
    public function getAllInvoices(): array
    {
        $invoices = $this->invoiceModel->getAllInvoices();
        return [
            'status' => !empty($invoices) ? 'success' : 'error',
            'invoices' => $invoices,
            'message' => empty($invoices) ? 'No invoices found' : null,
            'code' => !empty($invoices) ? 200 : 404,
        ];
    }

    /**
     * Get invoice by ID
     */
    public function getInvoiceById(int $invoiceId): array
    {
        if (!$invoiceId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid invoice ID'
            ];
        }

        $invoice = $this->invoiceModel->getInvoiceById($invoiceId);
        if (!$invoice) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Invoice not found'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'invoice' => $invoice,
            'message' => null
        ];
    }

    /**
     * Get invoices by parcel ID
     */
    public function getInvoicesByParcelId(int $parcelId): array
    {
        if (!$parcelId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid parcel ID'
            ];
        }

        $invoices = $this->invoiceModel->getInvoicesByParcelId($parcelId);
        return [
            'status' => !empty($invoices) ? 'success' : 'error',
            'invoices' => $invoices,
            'message' => empty($invoices) ? 'No invoices found for this parcel' : null,
            'code' => !empty($invoices) ? 200 : 404,
        ];
    }

    /**
     * Get invoices by client ID
     */
    public function getInvoicesByClientId(int $clientId): array
    {
        if (!$clientId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid client ID'
            ];
        }

        $invoices = $this->invoiceModel->getInvoicesByClientId($clientId);
        return [
            'status' => !empty($invoices) ? 'success' : 'error',
            'invoices' => $invoices,
            'message' => empty($invoices) ? 'No invoices found for this client' : null,
            'code' => !empty($invoices) ? 200 : 404,
        ];
    }

    /**
     * Create a new invoice
     * Expected data: parcel_id, client_id, amount, status?
     */
    public function createInvoice(array $data): array
    {
        $required = ['parcel_id', 'client_id', 'amount'];
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

        $invoiceId = $this->invoiceModel->createInvoice($data);
        if ($invoiceId === false) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to create invoice: ' . $this->invoiceModel->getLastError(),
            ];
        }

        $invoice = $this->invoiceModel->getInvoiceById((int) $invoiceId);
        return [
            'status' => 'success',
            'code' => 201,
            'invoice' => $invoice,
            'message' => 'Invoice created successfully',
        ];
    }

    /**
     * Update invoice fields
     * Allowed fields: amount, status
     */
    public function updateInvoice(int $invoiceId, array $data): array
    {
        $existing = $this->invoiceModel->getInvoiceById($invoiceId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Invoice not found'
            ];
        }

        $updated = $this->invoiceModel->updateInvoice($invoiceId, $data);
        if (!$updated) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update invoice: ' . $this->invoiceModel->getLastError(),
            ];
        }

        $invoice = $this->invoiceModel->getInvoiceById($invoiceId);
        return [
            'status' => 'success',
            'code' => 200,
            'invoice' => $invoice,
            'message' => 'Invoice updated successfully',
        ];
    }

    /**
     * Update invoice status
     */
    public function updateInvoiceStatus(int $invoiceId, string $status): array
    {
        $existing = $this->invoiceModel->getInvoiceById($invoiceId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Invoice not found'
            ];
        }

        $updated = $this->invoiceModel->updateInvoiceStatus($invoiceId, $status);
        if (!$updated) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update invoice status: ' . $this->invoiceModel->getLastError(),
            ];
        }

        $invoice = $this->invoiceModel->getInvoiceById($invoiceId);
        return [
            'status' => 'success',
            'code' => 200,
            'invoice' => $invoice,
            'message' => 'Invoice status updated successfully',
        ];
    }

    /**
     * Delete an invoice
     */
    public function deleteInvoice(int $invoiceId): array
    {
        $existing = $this->invoiceModel->getInvoiceById($invoiceId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Invoice not found'
            ];
        }

        $deleted = $this->invoiceModel->deleteInvoice($invoiceId);
        return [
            'status' => $deleted ? 'success' : 'error',
            'code' => $deleted ? 200 : 500,
            'message' => $deleted ? 'Invoice deleted successfully' : ('Failed to delete invoice: ' . $this->invoiceModel->getLastError()),
        ];
    }
}