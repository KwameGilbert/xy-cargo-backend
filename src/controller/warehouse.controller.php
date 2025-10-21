<?php

declare(strict_types=1);

require_once MODEL . 'warehouse.model.php';

/**
 * WarehousesController
 *
 * Handles warehouse CRUD operations.
 */
class WarehousesController
{
    protected WarehouseModel $warehouseModel;

    public function __construct()
    {
        $this->warehouseModel = new WarehouseModel();
    }

    /**
     * Get all warehouses
     */
    public function getAllWarehouses(): array
    {
        $warehouses = $this->warehouseModel->getAllWarehouses();
        return [
            'status' => !empty($warehouses) ? 'success' : 'error',
            'warehouses' => $warehouses,
            'message' => empty($warehouses) ? 'No warehouses found' : null,
            'code' => !empty($warehouses) ? 200 : 404,
        ];
    }

    /**
     * Get warehouse by ID
     */
    public function getWarehouseById(int $warehouseId): array
    {
        if (!$warehouseId) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Invalid warehouse ID'];
        }

        $warehouse = $this->warehouseModel->getWarehouseById($warehouseId);
        if (!$warehouse) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Warehouse not found'];
        }

        return ['status' => 'success', 'code' => 200, 'warehouse' => $warehouse, 'message' => null];
    }

    /**
     * Create a new warehouse
     */
    public function createWarehouse(array $data): array
    {
        $warehouseId = $this->warehouseModel->createWarehouse($data);
        if ($warehouseId === false) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to create warehouse: ' . $this->warehouseModel->getLastError()];
        }

        $warehouse = $this->warehouseModel->getWarehouseById((int) $warehouseId);
        return ['status' => 'success', 'code' => 201, 'warehouse' => $warehouse, 'message' => 'Warehouse created successfully'];
    }

    /**
     * Update warehouse
     */
    public function updateWarehouse(int $warehouseId, array $data): array
    {
        $existing = $this->warehouseModel->getWarehouseById($warehouseId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Warehouse not found'];
        }

        $updated = $this->warehouseModel->updateWarehouse($warehouseId, $data);
        if (!$updated) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update warehouse: ' . $this->warehouseModel->getLastError()];
        }

        $warehouse = $this->warehouseModel->getWarehouseById($warehouseId);
        return ['status' => 'success', 'code' => 200, 'warehouse' => $warehouse, 'message' => 'Warehouse updated successfully'];
    }

    /**
     * Update warehouse status
     */
    public function updateWarehouseStatus(int $warehouseId, string $status): array
    {
        $existing = $this->warehouseModel->getWarehouseById($warehouseId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Warehouse not found'];
        }

        $updated = $this->warehouseModel->updateWarehouseStatus($warehouseId, $status);
        if (!$updated) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update status: ' . $this->warehouseModel->getLastError()];
        }

        $warehouse = $this->warehouseModel->getWarehouseById($warehouseId);
        return ['status' => 'success', 'code' => 200, 'warehouse' => $warehouse, 'message' => 'Status updated successfully'];
    }

    /**
     * Delete a warehouse
     */
    public function deleteWarehouse(int $warehouseId): array
    {
        $existing = $this->warehouseModel->getWarehouseById($warehouseId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Warehouse not found'];
        }

        $deleted = $this->warehouseModel->deleteWarehouse($warehouseId);
        return ['status' => $deleted ? 'success' : 'error', 'code' => $deleted ? 200 : 500, 'message' => $deleted ? 'Warehouse deleted successfully' : ('Failed to delete warehouse: ' . $this->warehouseModel->getLastError())];
    }
}