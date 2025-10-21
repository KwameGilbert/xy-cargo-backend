<?php

declare(strict_types=1);

require_once MODEL . 'warehouse-staff.model.php';

/**
 * WarehouseStaffController
 *
 * Handles warehouse staff CRUD operations.
 */
class WarehouseStaffController
{
    protected WarehouseStaffModel $warehouseStaffModel;

    public function __construct()
    {
        $this->warehouseStaffModel = new WarehouseStaffModel();
    }

    /**
     * Get all warehouse staff
     */
    public function getAllWarehouseStaff(): array
    {
        $staff = $this->warehouseStaffModel->getAllWarehouseStaff();
        return [
            'status' => !empty($staff) ? 'success' : 'error',
            'staff' => $staff,
            'message' => empty($staff) ? 'No warehouse staff found' : null,
            'code' => !empty($staff) ? 200 : 404,
        ];
    }

    /**
     * Get warehouse staff by ID
     */
    public function getWarehouseStaffById(int $staffId): array
    {
        if (!$staffId) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Invalid staff ID'];
        }

        $staff = $this->warehouseStaffModel->getWarehouseStaffById($staffId);
        if (!$staff) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Warehouse staff member not found'];
        }

        return ['status' => 'success', 'code' => 200, 'staff' => $staff, 'message' => null];
    }

    /**
     * Get warehouse staff by warehouse ID
     */
    public function getWarehouseStaffByWarehouse(int $warehouseId): array
    {
        if (!$warehouseId) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Invalid warehouse ID'];
        }

        $staff = $this->warehouseStaffModel->getWarehouseStaffByWarehouse($warehouseId);
        return [
            'status' => 'success',
            'staff' => $staff,
            'message' => null,
            'code' => 200,
        ];
    }

    /**
     * Get warehouse staff by status
     */
    public function getWarehouseStaffByStatus(string $status): array
    {
        if (empty($status)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Status parameter is required'];
        }

        $staff = $this->warehouseStaffModel->getWarehouseStaffByStatus($status);
        return [
            'status' => 'success',
            'staff' => $staff,
            'message' => null,
            'code' => 200,
        ];
    }

    /**
     * Create a new warehouse staff member
     */
    public function createWarehouseStaff(array $data): array
    {
        $staffId = $this->warehouseStaffModel->createWarehouseStaff($data);
        if ($staffId === false) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to create warehouse staff: ' . $this->warehouseStaffModel->getLastError()];
        }

        $staff = $this->warehouseStaffModel->getWarehouseStaffById((int) $staffId);
        return ['status' => 'success', 'code' => 201, 'staff' => $staff, 'message' => 'Warehouse staff member created successfully'];
    }

    /**
     * Update warehouse staff
     */
    public function updateWarehouseStaff(int $staffId, array $data): array
    {
        $existing = $this->warehouseStaffModel->getWarehouseStaffById($staffId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Warehouse staff member not found'];
        }

        $updated = $this->warehouseStaffModel->updateWarehouseStaff($staffId, $data);
        if (!$updated) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update warehouse staff: ' . $this->warehouseStaffModel->getLastError()];
        }

        $staff = $this->warehouseStaffModel->getWarehouseStaffById($staffId);
        return ['status' => 'success', 'code' => 200, 'staff' => $staff, 'message' => 'Warehouse staff member updated successfully'];
    }

    /**
     * Update warehouse staff status
     */
    public function updateWarehouseStaffStatus(int $staffId, string $status): array
    {
        $existing = $this->warehouseStaffModel->getWarehouseStaffById($staffId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Warehouse staff member not found'];
        }

        $updated = $this->warehouseStaffModel->updateWarehouseStaffStatus($staffId, $status);
        if (!$updated) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update status: ' . $this->warehouseStaffModel->getLastError()];
        }

        $staff = $this->warehouseStaffModel->getWarehouseStaffById($staffId);
        return ['status' => 'success', 'code' => 200, 'staff' => $staff, 'message' => 'Status updated successfully'];
    }

    /**
     * Delete a warehouse staff member
     */
    public function deleteWarehouseStaff(int $staffId): array
    {
        $existing = $this->warehouseStaffModel->getWarehouseStaffById($staffId);
        if (!$existing) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Warehouse staff member not found'];
        }

        $deleted = $this->warehouseStaffModel->deleteWarehouseStaff($staffId);
        return ['status' => $deleted ? 'success' : 'error', 'code' => $deleted ? 200 : 500, 'message' => $deleted ? 'Warehouse staff member deleted successfully' : ('Failed to delete warehouse staff: ' . $this->warehouseStaffModel->getLastError())];
    }
}