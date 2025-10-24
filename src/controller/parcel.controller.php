<?php

declare(strict_types=1);

require_once MODEL . 'parcel.model.php';
require_once MODEL . 'parcel_item.model.php';
require_once MODEL . 'invoice.model.php';
require_once MODEL . 'shipment.model.php';

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
    protected ShipmentModel $shipmentModel;

    public function __construct()
    {
        $this->parcelModel = new ParcelModel();
        $this->itemModel = new ParcelItemModel();
        $this->invoiceModel = new InvoiceModel();
        $this->shipmentModel = new ShipmentModel();
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
     * Get parcel tracking by tracking number
     */
    public function getParcelTracking(string $trackingNumber): array
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

        // Get shipment tracking updates
        $trackingHistory = [];
        if ($parcel['shipment_id']) {
            require_once MODEL . 'shipment_tracking_update.model.php';
            $trackingModel = new ShipmentTrackingUpdateModel();
            $updates = $trackingModel->getTrackingUpdatesByShipmentId($parcel['shipment_id']);
            
            foreach ($updates as $update) {
                $trackingHistory[] = [
                    'status' => ucfirst($update['status']),
                    'description' => $update['notes'] ?? 'Status update',
                    'location' => $update['location'] ?? 'Unknown',
                    'date' => $update['updated_at'],
                    'handler' => 'XY Cargo'
                ];
            }
        }

        return [
            'status' => 'success',
            'code' => 200,
            'trackingNumber' => $trackingNumber,
            'parcelId' => $parcel['parcel_id'],
            'currentStatus' => strtoupper($parcel['status']),
            'trackingHistory' => $trackingHistory,
            'message' => null
        ];
    }

    /**
     * Create a new parcel (with optional items)
     * Expected data: client_id, shipment_id, description?, weight?, dimensions?, declared_value?, shipping_cost?, payment_status?, tags?, items?[]
     */
    public function createParcel(array $data): array
    {
        $required = ['client_id', 'shipment_id'];
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

        // Validate shipment exists
        if (!$this->shipmentModel->getShipmentById((int) $data['shipment_id'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Shipment does not exist',
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
     * Allowed fields: shipment_id, description, weight, dimensions, status, declared_value, shipping_cost, payment_status, tags
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

        // Validate shipment_id if provided
        if (isset($data['shipment_id']) && $data['shipment_id'] !== null) {
            if (!$this->shipmentModel->getShipmentById((int) $data['shipment_id'])) {
                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Shipment does not exist',
                ];
            }
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

    /**
     * Get parcels for a client (formatted for frontend)
     */
    public function getClientParcels(int $clientId): array
    {
        if (!$clientId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid client ID'
            ];
        }

        $parcels = $this->parcelModel->getParcelsByClientId($clientId);
        
        // Format parcels for frontend
        $formattedParcels = [];
        foreach ($parcels as $parcel) {
            $formattedParcels[] = $this->formatParcelForClient($parcel);
        }

        return [
            'status' => 'success',
            'code' => 200,
            'parcels' => $formattedParcels,
            'message' => null
        ];
    }

    /**
     * Get parcel details for a client (formatted for frontend)
     */
    public function getClientParcelById(int $clientId, int $parcelId): array
    {
        if (!$clientId || !$parcelId) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid client ID or parcel ID'
            ];
        }

        // Verify parcel belongs to client
        $parcel = $this->parcelModel->getParcelById($parcelId);
        if (!$parcel || $parcel['client_id'] != $clientId) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Parcel not found'
            ];
        }

        $formattedParcel = $this->formatParcelDetailsForClient($parcel);

        return [
            'status' => 'success',
            'code' => 200,
            'parcel' => $formattedParcel,
            'message' => null
        ];
    }

    /**
     * Format parcel data for client parcels list
     */
    private function formatParcelForClient(array $parcel): array
    {
        // Get shipment data if available
        $waybillNumber = '';
        $originWarehouse = '';
        $destinationWarehouse = '';
        $estimatedDelivery = null;
        $currentLocation = 'Processing';
        
        if ($parcel['shipment_id']) {
            $shipment = $this->shipmentModel->getShipmentById($parcel['shipment_id']);
            if ($shipment) {
                $waybillNumber = $shipment['waybill_number'] ?? '';
                $originWarehouse = $shipment['origin_warehouse_id'] ? 'Warehouse ' . $shipment['origin_warehouse_id'] : 'N/A';
                $destinationWarehouse = $shipment['destination_warehouse_id'] ? 'Warehouse ' . $shipment['destination_warehouse_id'] : 'N/A';
                $estimatedDelivery = $shipment['expected_delivery'];
                
                // Map shipment status to location
                $statusLocationMap = [
                    'pending' => 'Processing at Origin',
                    'in_transit' => 'In Transit',
                    'at_destination' => 'At Destination Warehouse',
                    'delivered' => 'Delivered',
                    'delayed' => 'Delayed'
                ];
                $currentLocation = $statusLocationMap[$shipment['status']] ?? 'Processing';
            }
        }

        return [
            'id' => $parcel['parcel_id'],
            'waybillNumber' => $waybillNumber,
            'trackingNumber' => $parcel['tracking_number'] ?? '',
            'description' => $parcel['description'] ?? '',
            'weight' => (float) ($parcel['weight'] ?? 0),
            'dimensions' => $parcel['dimensions'] ?? 'N/A',
            'status' => strtoupper($parcel['status']),
            'currentLocation' => $currentLocation,
            'declaredValue' => (float) ($parcel['declared_value'] ?? 0),
            'shippingCost' => (float) ($parcel['shipping_cost'] ?? 0),
            'paymentStatus' => strtoupper($parcel['payment_status']),
            'lastUpdate' => $parcel['updated_at'],
            'category' => $parcel['category'] ?? 'General',
            'originWarehouse' => $originWarehouse,
            'destinationWarehouse' => $destinationWarehouse,
            'estimatedDelivery' => $estimatedDelivery,
            'created_at' => $parcel['created_at'],
            'updated_at' => $parcel['updated_at']
        ];
    }

    /**
     * Format parcel data for client parcel details
     */
    private function formatParcelDetailsForClient(array $parcel): array
    {
        // Get parcel with all details
        $parcelDetails = $this->parcelModel->getParcelWithDetails($parcel['parcel_id']);
        
        if (!$parcelDetails) {
            return [];
        }
        
        // Get warehouse names
        $originWarehouse = null;
        $destinationWarehouse = null;
        if (isset($parcelDetails['origin_warehouse_id']) && $parcelDetails['origin_warehouse_id']) {
            $originWarehouse = 'Warehouse ' . $parcelDetails['origin_warehouse_id'];
        }
        if (isset($parcelDetails['destination_warehouse_id']) && $parcelDetails['destination_warehouse_id']) {
            $destinationWarehouse = 'Warehouse ' . $parcelDetails['destination_warehouse_id'];
        }

        // Get shipment info for tracking number and other details
        $waybillNumber = '';
        $trackingNumber = '';
        $currentLocation = 'Processing';
        $estimatedDelivery = null;
        $trackingHistory = [];
        
        if ($parcelDetails['shipment_id']) {
            $shipment = $this->shipmentModel->getShipmentById($parcelDetails['shipment_id']);
            if ($shipment) {
                $waybillNumber = $shipment['waybill_number'] ?? '';
                $trackingNumber = $shipment['tracking_number'] ?? '';
                
                // Map shipment status to location
                $statusLocationMap = [
                    'pending' => 'Processing at Origin',
                    'in_transit' => 'In Transit',
                    'at_destination' => 'At Destination Warehouse',
                    'delivered' => 'Delivered',
                    'delayed' => 'Delayed'
                ];
                $currentLocation = $statusLocationMap[$shipment['status']] ?? 'Processing';
                $estimatedDelivery = $shipment['expected_delivery'];
                
                // Format tracking history
                if (isset($parcelDetails['trackingHistory']) && is_array($parcelDetails['trackingHistory'])) {
                    foreach ($parcelDetails['trackingHistory'] as $update) {
                        $trackingHistory[] = [
                            'status' => ucfirst(str_replace('_', ' ', $update['status'])),
                            'description' => $update['notes'] ?? 'Status update',
                            'location' => $update['location'] ?? 'Unknown',
                            'date' => $update['updated_at'],
                            'active' => $update['status'] === $shipment['status'],
                            'handler' => 'XY Cargo Staff'
                        ];
                    }
                }
            }
        }
        
        // Use parcel tracking number if shipment tracking is not available
        if (empty($trackingNumber)) {
            $trackingNumber = $parcelDetails['tracking_number'] ?? '';
        }

        // Format items
        $formattedItems = [];
        if (isset($parcelDetails['items']) && is_array($parcelDetails['items'])) {
            foreach ($parcelDetails['items'] as $item) {
                $length = $item['length'] ?? 0;
                $width = $item['width'] ?? 0;
                $height = $item['height'] ?? 0;
                $dimensions = $length && $width && $height 
                    ? sprintf('%scm x %scm x %scm', $length, $width, $height)
                    : 'N/A';
                
                $formattedItems[] = [
                    'id' => 'item-' . $item['item_id'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? '',
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'weight' => (float) ($item['weight'] ?? 0),
                    'dimensions' => $dimensions,
                    'declaredValue' => (float) ($item['value'] ?? 0),
                    'specialPackaging' => (bool) ($item['special_packaging'] ?? false),
                    'status' => 'Normal',
                    'category' => 'General',
                    'condition' => 'New',
                    'notes' => '',
                    'separatedParcelId' => null
                ];
            }
        }

        // Format documents
        $formattedDocuments = [];
        if (isset($parcelDetails['documents']) && is_array($parcelDetails['documents'])) {
            foreach ($parcelDetails['documents'] as $doc) {
                $formattedDocuments[] = [
                    'type' => $doc['type'],
                    'name' => $doc['name'],
                    'url' => $doc['url'],
                    'createdDate' => $doc['created_at']
                ];
            }
        }

        return [
            'id' => $parcelDetails['parcel_id'],
            'waybillNumber' => $waybillNumber,
            'trackingNumber' => $trackingNumber,
            'description' => $parcelDetails['description'] ?? '',
            'weight' => (float) ($parcelDetails['weight'] ?? 0),
            'dimensions' => $parcelDetails['dimensions'] ?? 'N/A',
            'status' => strtoupper($parcelDetails['status']),
            'currentLocation' => $currentLocation,
            'declaredValue' => (float) ($parcelDetails['declared_value'] ?? 0),
            'shippingCost' => (float) ($parcelDetails['shipping_cost'] ?? 0),
            'paymentStatus' => strtoupper($parcelDetails['payment_status']),
            'lastUpdate' => $parcelDetails['updated_at'],
            'category' => $parcelDetails['category'] ?? 'General',
            'notes' => $parcelDetails['notes'] ?? '',
            'originWarehouse' => $originWarehouse ?? 'N/A',
            'destinationWarehouse' => $destinationWarehouse ?? 'N/A',
            'estimatedDelivery' => $estimatedDelivery,
            'trackingHistory' => $trackingHistory,
            'items' => $formattedItems,
            'documents' => $formattedDocuments,
            'separatedParcels' => [],
            'supportContacts' => [
                [
                    'type' => 'Customer Service',
                    'phone' => '+1 (555) 123-4567',
                    'email' => 'support@xycargo.com',
                    'hours' => '24/7'
                ],
                [
                    'type' => 'Warehouse',
                    'phone' => '+1 (555) 987-6543',
                    'email' => 'warehouse@xycargo.com',
                    'hours' => 'Mon-Fri 8AM-6PM'
                ]
            ]
        ];
    }
}
