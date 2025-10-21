<?php

declare(strict_types=1);

require_once MODEL . 'shipment-type.model.php';

/**
 * ShipmentTypesController
 *
 * Handles shipment type CRUD operations.
 */
class ShipmentTypesController
{
    protected ShipmentTypeModel $shipmentTypeModel;

    public function __construct()
    {
        $this->shipmentTypeModel = new ShipmentTypeModel();
    }

    /**
     * Get all shipment types
     */
    public function getAllShipmentTypes(): array
    {
        $shipmentTypes = $this->shipmentTypeModel->getAllShipmentTypes();
        return [
            'status' => !empty($shipmentTypes) ? 'success' : 'error',
            'shipment_types' => $shipmentTypes,
            'message' => empty($shipmentTypes) ? 'No shipment types found' : null,
            'code' => !empty($shipmentTypes) ? 200 : 404,
        ];
    }

    /**
     * Get shipment type by ID
     */
    public function getShipmentTypeById(int $shipmentTypeId): array
    {
        if (!$shipmentTypeId) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Invalid shipment type ID'];
        }

        $shipmentType = $this->shipmentTypeModel->getShipmentTypeById($shipmentTypeId);
        if (!$shipmentType) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment type not found'];
        }

        return ['status' => 'success', 'code' => 200, 'shipment_type' => $shipmentType, 'message' => null];
    }

    /**
     * Create a new shipment type
     */
    public function createShipmentType(array $data): array
    {
        $shipmentTypeId = $this->shipmentTypeModel->createShipmentType($data);
        if ($shipmentTypeId === false) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to create shipment type: ' . $this->shipmentTypeModel->getLastError()];
        }

        $shipmentType = $this->shipmentTypeModel->getShipmentTypeById((int) $shipmentTypeId);
        return ['status' => 'success', 'code' => 201, 'shipment_type' => $shipmentType, 'message' => 'Shipment type created successfully'];
    }

    /**
     * Update shipment type
     */
    public function updateShipmentType(int $shipmentTypeId, array $data): array
    {
        $existing = $this->shipmentTypeModel->getShipmentTypeById($shipmentTypeId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment type not found'];
        }

        $updated = $this->shipmentTypeModel->updateShipmentType($shipmentTypeId, $data);
        if (!$updated) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update shipment type: ' . $this->shipmentTypeModel->getLastError()];
        }

        $shipmentType = $this->shipmentTypeModel->getShipmentTypeById($shipmentTypeId);
        return ['status' => 'success', 'code' => 200, 'shipment_type' => $shipmentType, 'message' => 'Shipment type updated successfully'];
    }

    /**
     * Delete a shipment type
     */
    public function deleteShipmentType(int $shipmentTypeId): array
    {
        $existing = $this->shipmentTypeModel->getShipmentTypeById($shipmentTypeId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment type not found'];
        }

        $deleted = $this->shipmentTypeModel->deleteShipmentType($shipmentTypeId);
        return ['status' => $deleted ? 'success' : 'error', 'code' => $deleted ? 200 : 500, 'message' => $deleted ? 'Shipment type deleted successfully' : ('Failed to delete shipment type: ' . $this->shipmentTypeModel->getLastError())];
    }
}