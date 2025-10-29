<?php

declare(strict_types=1);

/**
 * Warehouses API Routes
 */

require_once CONTROLLER . '/warehouse.controller.php';

return function ($app): void {
    $warehouseController = new WarehousesController();

    // Get all warehouses
    $app->get('/v1/warehouses', function ($request, $response) use ($warehouseController) {
        $result = $warehouseController->getAllWarehouses();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get warehouse by ID
    $app->get('/v1/warehouses/{id}', function ($request, $response, $args) use ($warehouseController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $warehouseController->getWarehouseById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create warehouse
    $app->post('/v1/warehouses', function ($request, $response) use ($warehouseController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $warehouseController->createWarehouse($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update warehouse
    $app->patch('/v1/warehouses/{id}', function ($request, $response, $args) use ($warehouseController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $warehouseController->updateWarehouse($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update warehouse status
    $app->patch('/v1/warehouses/{id}/status', function ($request, $response, $args) use ($warehouseController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $status = (string) ($data['status'] ?? '');
        $result = $warehouseController->updateWarehouseStatus($id, $status);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete warehouse
    $app->delete('/v1/warehouses/{id}', function ($request, $response, $args) use ($warehouseController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $warehouseController->deleteWarehouse($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });
};