<?php

declare(strict_types=1);

require_once MODEL . 'shipment-tracking-update.model.php';

/**
 * ShipmentTrackingUpdatesController
 *
 * Handles shipment tracking update CRUD operations.
 */
class ShipmentTrackingUpdatesController
{
    protected ShipmentTrackingUpdateModel $trackingUpdateModel;

    public function __construct()
    {
        $this->trackingUpdateModel = new ShipmentTrackingUpdateModel();
    }

    /**
     * Get all tracking updates
     */
    public function getAllTrackingUpdates(): array
    {
        $updates = $this->trackingUpdateModel->getAllTrackingUpdates();
        return [
            'status' => !empty($updates) ? 'success' : 'error',
            'tracking_updates' => $updates,
            'message' => empty($updates) ? 'No tracking updates found' : null,
            'code' => !empty($updates) ? 200 : 404,
        ];
    }

    /**
     * Get tracking update by ID
     */
    public function getTrackingUpdateById(int $updateId): array
    {
        if (!$updateId) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Invalid tracking update ID'];
        }

        $update = $this->trackingUpdateModel->getTrackingUpdateById($updateId);
        if (!$update) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Tracking update not found'];
        }

        return ['status' => 'success', 'code' => 200, 'tracking_update' => $update, 'message' => null];
    }

    /**
     * Get tracking updates by shipment ID
     */
    public function getTrackingUpdatesByShipmentId(int $shipmentId): array
    {
        if (!$shipmentId) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Invalid shipment ID'];
        }

        $updates = $this->trackingUpdateModel->getTrackingUpdatesByShipmentId($shipmentId);
        return [
            'status' => !empty($updates) ? 'success' : 'error',
            'tracking_updates' => $updates,
            'message' => empty($updates) ? 'No tracking updates found for this shipment' : null,
            'code' => !empty($updates) ? 200 : 404,
        ];
    }

    /**
     * Create a new tracking update
     */
    public function createTrackingUpdate(array $data): array
    {
        $updateId = $this->trackingUpdateModel->createTrackingUpdate($data);
        if ($updateId === false) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to create tracking update: ' . $this->trackingUpdateModel->getLastError()];
        }

        $update = $this->trackingUpdateModel->getTrackingUpdateById((int) $updateId);
        return ['status' => 'success', 'code' => 201, 'tracking_update' => $update, 'message' => 'Tracking update created successfully'];
    }

    /**
     * Update tracking update
     */
    public function updateTrackingUpdate(int $updateId, array $data): array
    {
        $existing = $this->trackingUpdateModel->getTrackingUpdateById($updateId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Tracking update not found'];
        }

        $updated = $this->trackingUpdateModel->updateTrackingUpdate($updateId, $data);
        if (!$updated) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update tracking update: ' . $this->trackingUpdateModel->getLastError()];
        }

        $update = $this->trackingUpdateModel->getTrackingUpdateById($updateId);
        return ['status' => 'success', 'code' => 200, 'tracking_update' => $update, 'message' => 'Tracking update updated successfully'];
    }

    /**
     * Delete a tracking update
     */
    public function deleteTrackingUpdate(int $updateId): array
    {
        $existing = $this->trackingUpdateModel->getTrackingUpdateById($updateId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Tracking update not found'];
        }

        $deleted = $this->trackingUpdateModel->deleteTrackingUpdate($updateId);
        return ['status' => $deleted ? 'success' : 'error', 'code' => $deleted ? 200 : 500, 'message' => $deleted ? 'Tracking update deleted successfully' : ('Failed to delete tracking update: ' . $this->trackingUpdateModel->getLastError())];
    }
}