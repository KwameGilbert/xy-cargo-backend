<?php

declare(strict_types=1);

require_once MODEL . 'shipment.model.php';

/**
 * ShipmentsController
 *
 * Handles shipment CRUD operations and tracking number management.
 */
class ShipmentsController
{
    protected ShipmentModel $shipmentModel;

    public function __construct()
    {
        $this->shipmentModel = new ShipmentModel();
    }

    /**
     * Get all shipments
     */
    public function getAllShipments(): array
    {
        $shipments = $this->shipmentModel->getAllShipments();
        return [
            'status' => !empty($shipments) ? 'success' : 'error',
            'shipments' => $shipments,
            'message' => empty($shipments) ? 'No shipments found' : null,
            'code' => !empty($shipments) ? 200 : 404,
        ];
    }

    /**
     * Get shipment by ID
     */
    public function getShipmentById(int $shipmentId): array
    {
        if (!$shipmentId) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Invalid shipment ID'];
        }

        $shipment = $this->shipmentModel->getShipmentById($shipmentId);
        if (!$shipment) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment not found'];
        }

        return ['status' => 'success', 'code' => 200, 'shipment' => $shipment, 'message' => null];
    }

    /**
     * Get shipment by tracking number
     */
    public function getShipmentByTrackingNumber(string $trackingNumber): array
    {
        if (!$trackingNumber) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Tracking number is required'];
        }

        $shipment = $this->shipmentModel->getShipmentByTrackingNumber($trackingNumber);
        if (!$shipment) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment not found'];
        }

        return ['status' => 'success', 'code' => 200, 'shipment' => $shipment, 'message' => null];
    }

    /**
     * Create a new shipment
     */
    public function createShipment(array $data): array
    {
        $shipmentId = $this->shipmentModel->createShipment($data);
        if ($shipmentId === false) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to create shipment: ' . $this->shipmentModel->getLastError()];
        }

        $shipment = $this->shipmentModel->getShipmentById((int) $shipmentId);
        return ['status' => 'success', 'code' => 201, 'shipment' => $shipment, 'message' => 'Shipment created successfully'];
    }

    /**
     * Update shipment
     */
    public function updateShipment(int $shipmentId, array $data): array
    {
        $existing = $this->shipmentModel->getShipmentById($shipmentId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment not found'];
        }

        $updated = $this->shipmentModel->updateShipment($shipmentId, $data);
        if (!$updated) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update shipment: ' . $this->shipmentModel->getLastError()];
        }

        $shipment = $this->shipmentModel->getShipmentById($shipmentId);
        return ['status' => 'success', 'code' => 200, 'shipment' => $shipment, 'message' => 'Shipment updated successfully'];
    }

    /**
     * Change tracking number for a shipment
     */
    public function changeTrackingNumber(int $shipmentId, string $newTrackingNumber): array
    {
        $existing = $this->shipmentModel->getShipmentById($shipmentId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment not found'];
        }

        $changed = $this->shipmentModel->changeTrackingNumber($shipmentId, $newTrackingNumber);
        if (!$changed) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Failed to change tracking number: ' . $this->shipmentModel->getLastError()];
        }

        $shipment = $this->shipmentModel->getShipmentById($shipmentId);
        return ['status' => 'success', 'code' => 200, 'shipment' => $shipment, 'message' => 'Tracking number changed successfully'];
    }

    /**
     * Update shipment status
     */
    public function updateShipmentStatus(int $shipmentId, string $status): array
    {
        $existing = $this->shipmentModel->getShipmentById($shipmentId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment not found'];
        }

        $updated = $this->shipmentModel->updateShipmentStatus($shipmentId, $status);
        if (!$updated) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update status: ' . $this->shipmentModel->getLastError()];
        }

        $shipment = $this->shipmentModel->getShipmentById($shipmentId);
        return ['status' => 'success', 'code' => 200, 'shipment' => $shipment, 'message' => 'Status updated successfully'];
    }

    /**
     * Delete a shipment
     */
    public function deleteShipment(int $shipmentId): array
    {
        $existing = $this->shipmentModel->getShipmentById($shipmentId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Shipment not found'];
        }

        $deleted = $this->shipmentModel->deleteShipment($shipmentId);
        return ['status' => $deleted ? 'success' : 'error', 'code' => $deleted ? 200 : 500, 'message' => $deleted ? 'Shipment deleted successfully' : ('Failed to delete shipment: ' . $this->shipmentModel->getLastError())];
    }
}
