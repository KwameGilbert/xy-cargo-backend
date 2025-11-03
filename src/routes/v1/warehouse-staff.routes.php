<?php

declare(strict_types=1);

/**
 * Warehouse Staff API Routes
 */

require_once CONTROLLER . '/warehouse-staff.controller.php';
require_once CONTROLLER . '/warehouse-dashboard.controller.php';
require_once CONTROLLER . '/payment.controller.php';
require_once CONTROLLER . '/shipment.controller.php';

return function ($app): void {
    $warehouseStaffController = new WarehouseStaffController();
    $warehouseDashboardController = new WarehouseDashboardController();
    $paymentController = new PaymentsController();
    $shipmentController = new ShipmentsController();

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

    // Warehouse-staff payment routes (mirror /v1/payments but scoped for warehouse staff)
    // List payments with optional filters: ?period=... or ?start_date=...&end_date=...
    $app->get('/v1/warehouse-staff/payments/data', function ($request, $response) use ($paymentController) {
        $result = $paymentController->getPayments();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get payment by ID
    $app->get('/v1/warehouse-staff/payments/{id}', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $paymentController->getPaymentById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });


    // Get all pending payments
    $app->get('/v1/warehouse-staff/payments/status/pending', function ($request, $response) use ($paymentController) {
        $result = $paymentController->getPendingPayments();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create a new payment
    $app->post('/v1/warehouse-staff/payments', function ($request, $response) use ($paymentController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $paymentController->createPayment($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

   
  
    // Update payment by ID
    $app->patch('/v1/warehouse-staff/payments/{id}', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $paymentController->updatePayment($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update payment status
    $app->patch('/v1/warehouse-staff/payments/{id}/status', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $status = (string) ($data['status'] ?? '');
        $result = $paymentController->updatePaymentStatus($id, $status);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete payment
    $app->delete('/v1/warehouse-staff/payments/{id}', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $paymentController->deletePayment($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get shipment details by shipment id
    $app->get('/v1/warehouse-staff/shipments/{id}', function ($request, $response, $args) use ($shipmentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $shipmentController->getShipmentDetails($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

};