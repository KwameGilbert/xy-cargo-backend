<?php

declare(strict_types=1);

/**
 * Warehouse Staff API Routes
 */

require_once CONTROLLER . '/warehouse-staff.controller.php';
require_once CONTROLLER . '/warehouse-dashboard.controller.php';

return function ($app): void {
    $warehouseStaffController = new WarehouseStaffController();
    $warehouseDashboardController = new WarehouseDashboardController();

     // Get warehouse dashboard data
    $app->get('/v1/warehouse-staff/dashboard/data', function ($request, $response) use ($warehouseDashboardController) {
        $result = $warehouseDashboardController->getDashboardData();
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    });

    // Get all warehouse staff
    $app->get('/v1/warehouse-staff', function ($request, $response) use ($warehouseStaffController) {
        $result = $warehouseStaffController->getAllWarehouseStaff();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get warehouse staff by ID
    $app->get('/v1/warehouse-staff/{id}', function ($request, $response, $args) use ($warehouseStaffController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $warehouseStaffController->getWarehouseStaffById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get warehouse staff by warehouse ID
    $app->get('/v1/warehouse-staff/warehouse/{warehouseId}', function ($request, $response, $args) use ($warehouseStaffController) {
        $warehouseId = isset($args['warehouseId']) ? (int) $args['warehouseId'] : 0;
        $result = $warehouseStaffController->getWarehouseStaffByWarehouse($warehouseId);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get warehouse staff by status
    $app->get('/v1/warehouse-staff/status/{status}', function ($request, $response, $args) use ($warehouseStaffController) {
        $status = isset($args['status']) ? (string) $args['status'] : '';
        $result = $warehouseStaffController->getWarehouseStaffByStatus($status);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create warehouse staff
    $app->post('/v1/warehouse-staff', function ($request, $response) use ($warehouseStaffController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $warehouseStaffController->createWarehouseStaff($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update warehouse staff
    $app->patch('/v1/warehouse-staff/{id}', function ($request, $response, $args) use ($warehouseStaffController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $warehouseStaffController->updateWarehouseStaff($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update warehouse staff status
    $app->patch('/v1/warehouse-staff/{id}/status', function ($request, $response, $args) use ($warehouseStaffController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $status = (string) ($data['status'] ?? '');
        $result = $warehouseStaffController->updateWarehouseStaffStatus($id, $status);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete warehouse staff
    $app->delete('/v1/warehouse-staff/{id}', function ($request, $response, $args) use ($warehouseStaffController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $warehouseStaffController->deleteWarehouseStaff($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get warehouse dashboard data
    $app->get('/v1/warehouse-staff/dashboard/data/{warehouseId}', function ($request, $response, $args) use ($warehouseDashboardController) {
        $warehouseId = isset($args['warehouseId']) ? (int) $args['warehouseId'] : 0;
        if (!$warehouseId) {
            $result = ['status' => 'error', 'code' => 400, 'message' => 'Invalid warehouse ID'];
        } else {
            $result = $warehouseDashboardController->getDashboardData($warehouseId);
        }
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });
};