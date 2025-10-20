<?php

declare(strict_types=1);

require_once MODEL . 'parcel.model.php';
require_once MODEL . 'parcel_item.model.php';
require_once MODEL . 'invoice.model.php';

/**
 * ParcelsController
 *
 * Handles parcel CRUD and item management.
 * Works with ParcelModel and ParcelItemModel.
 */
class ParcelsController
{
    protected ParcelModel $parcelModel;
    protected ParcelItemModel $itemModel;
    protected InvoiceModel $invoiceModel;

    public function __construct()
    {
        $this->parcelModel = new ParcelModel();
        $this->itemModel = new ParcelItemModel();
        $this->invoiceModel = new InvoiceModel();
    }

    /**
     * Get all parcels
     */
    public function getAllParcels(): array
    {
        $parcels = $this->parcelModel->getAllParcels();
        
        // Add items to each parcel
        foreach ($parcels as &$parcel) {
            $parcel['items'] = $this->itemModel->getItemsByParcelId($parcel['parcel_id']);
        }
        
        return [
            'status' => !empty($parcels) ? 'success' : 'error',
            'parcels' => $parcels,
            'message' => empty($parcels) ? 'No parcels found' : null,
            'code' => !empty($parcels) ? 200 : 404,
        ];
    }

    /**
     * Get parcel by ID
     */
    public function getParcelById(int $parcelId): array
    {
        if (!$parcelId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid parcel ID'
            ];
        }

        $parcel = $this->parcelModel->getParcelById($parcelId);
        if (!$parcel) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'parcel' => $parcel,
            'message' => null
        ];
    }

    /**
     * Get parcel with items
     */
    public function getParcelWithItems(int $parcelId): array
    {
        if (!$parcelId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid parcel ID'
            ];
        }

        $parcel = $this->parcelModel->getParcelWithItems($parcelId);
        if (!$parcel) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'parcel' => $parcel,
            'message' => null
        ];
    }

    /**
     * Get parcels by client ID
     */
    public function getParcelsByClientId(int $clientId): array
    {
        if (!$clientId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid client ID'
            ];
        }

        $parcels = $this->parcelModel->getParcelsByClientId($clientId);
        
        // Add items to each parcel
        foreach ($parcels as &$parcel) {
            $parcel['items'] = $this->itemModel->getItemsByParcelId($parcel['parcel_id']);
        }

        return [
            'status' => !empty($parcels) ? 'success' : 'error',
            'parcels' => $parcels,
            'message' => empty($parcels) ? 'No parcels found for this client' : null,
            'code' => !empty($parcels) ? 200 : 404,
        ];
    }

    /**
     * Get parcel by tracking number
     */
    public function getParcelByTrackingNumber(string $trackingNumber): array
    {
        if (!$trackingNumber) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Tracking number is required'
            ];
        }

        $parcel = $this->parcelModel->getParcelByTrackingNumber($trackingNumber);
        if (!$parcel) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found with this tracking number'
            ];
        }

        // Add items to the parcel
        $parcel['items'] = $this->itemModel->getItemsByParcelId($parcel['parcel_id']);

        return [
            'status' => 'success',
            'code' => 200,
            'parcel' => $parcel,
            'message' => null
        ];
    }

    /**
     * Create a new parcel (with optional items)
     * Expected data: client_id, description?, weight?, dimensions?, declared_value?, shipping_cost?, payment_status?, tags?, items?[]
     */
    public function createParcel(array $data): array
    {
        $required = ['client_id'];
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

        $parcelId = $this->parcelModel->createParcelWithItems($data);
        if ($parcelId === false) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to create parcel: ' . $this->parcelModel->getLastError(),
            ];
        }

        // Create invoice for the parcel
        $invoiceData = [
            'parcel_id' => (int) $parcelId,
            'client_id' => $data['client_id'],
            'amount' => $data['shipping_cost'] ?? 0.00,
            'status' => 'unpaid'
        ];
        $invoiceId = $this->invoiceModel->createInvoice($invoiceData);
        if ($invoiceId === false) {
            // Log the error but don't fail the parcel creation
            error_log('Failed to create invoice for parcel ' . $parcelId . ': ' . $this->invoiceModel->getLastError());
        }

        $parcel = $this->parcelModel->getParcelWithItems((int) $parcelId);
        $message = 'Parcel created successfully';
        if ($invoiceId !== false) {
            $message .= ' (Invoice created automatically)';
        }
        return [
            'status' => 'success',
            'code' => 201,
            'parcel' => $parcel,
            'message' => $message,
        ];
    }

    /**
     * Update parcel fields
     * Allowed fields: description, weight, dimensions, status, declared_value, shipping_cost, payment_status, tags
     */
    public function updateParcel(int $parcelId, array $data): array
    {
        $existing = $this->parcelModel->getParcelById($parcelId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found',
            ];
        }

        $updated = $this->parcelModel->updateParcel($parcelId, $data);
        if (!$updated) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update parcel: ' . $this->parcelModel->getLastError(),
            ];
        }

        $parcel = $this->parcelModel->getParcelById($parcelId);
        return [
            'status' => 'success',
            'code' => 200,
            'parcel' => $parcel,
            'message' => 'Parcel updated successfully',
        ];
    }

    /**
     * Update parcel status
     */
    public function updateParcelStatus(int $parcelId, string $status): array
    {
        $existing = $this->parcelModel->getParcelById($parcelId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found',
            ];
        }

        $updated = $this->parcelModel->updateParcelStatus($parcelId, $status);
        if (!$updated) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update status: ' . $this->parcelModel->getLastError(),
            ];
        }

        $parcel = $this->parcelModel->getParcelById($parcelId);
        return [
            'status' => 'success',
            'code' => 200,
            'parcel' => $parcel,
            'message' => 'Status updated successfully',
        ];
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $parcelId, string $paymentStatus): array
    {
        $existing = $this->parcelModel->getParcelById($parcelId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found',
            ];
        }

        $updated = $this->parcelModel->updatePaymentStatus($parcelId, $paymentStatus);
        if (!$updated) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update payment status: ' . $this->parcelModel->getLastError(),
            ];
        }

        $parcel = $this->parcelModel->getParcelById($parcelId);
        return [
            'status' => 'success',
            'code' => 200,
            'parcel' => $parcel,
            'message' => 'Payment status updated successfully',
        ];
    }

    /**
     * Delete a parcel
     */
    public function deleteParcel(int $parcelId): array
    {
        $existing = $this->parcelModel->getParcelById($parcelId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found',
            ];
        }

        // Delete items first
        $this->itemModel->deleteItemsByParcelId($parcelId);

        $deleted = $this->parcelModel->deleteParcel($parcelId);
        return [
            'status' => $deleted ? 'success' : 'error',
            'code' => $deleted ? 200 : 500,
            'message' => $deleted ? 'Parcel deleted successfully' : ('Failed to delete parcel: ' . $this->parcelModel->getLastError()),
        ];
    }

    /**
     * Add item to parcel
     */
    public function addItemToParcel(int $parcelId, array $itemData): array
    {
        $existing = $this->parcelModel->getParcelById($parcelId);
        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found',
            ];
        }

        $itemData['parcel_id'] = $parcelId;
        $itemId = $this->itemModel->createItem($itemData);
        if ($itemId === false) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to add item: ' . $this->itemModel->getLastError(),
            ];
        }

        // Get the created item
        $items = $this->itemModel->getItemsByParcelId($parcelId);
        $newItem = array_filter($items, fn($item) => $item['item_id'] == $itemId);
        $newItem = reset($newItem);

        return [
            'status' => 'success',
            'code' => 201,
            'item' => $newItem,
            'message' => 'Item added successfully',
        ];
    }

    /**
     * Update parcel item
     */
    public function updateParcelItem(int $itemId, array $data): array
    {
        $updated = $this->itemModel->updateItem($itemId, $data);
        if (!$updated) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update item: ' . $this->itemModel->getLastError(),
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Item updated successfully',
        ];
    }

    /**
     * Delete parcel item
     */
    public function deleteParcelItem(int $itemId): array
    {
        $deleted = $this->itemModel->deleteItem($itemId);
        return [
            'status' => $deleted ? 'success' : 'error',
            'code' => $deleted ? 200 : 500,
            'message' => $deleted ? 'Item deleted successfully' : ('Failed to delete item: ' . $this->itemModel->getLastError()),
        ];
    }
}
