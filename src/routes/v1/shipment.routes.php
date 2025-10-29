<?php

declare(strict_types=1);

/**
 * Shipments API Routes
 */

require_once CONTROLLER . '/shipment.controller.php';

return function ($app): void {
    $shipmentController = new ShipmentsController();

    // Get all shipments
    $app->get('/v1/shipments', function ($request, $response) use ($shipmentController) {
        $result = $shipmentController->getAllShipments();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get shipment by ID
    $app->get('/v1/shipments/{id}', function ($request, $response, $args) use ($shipmentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $shipmentController->getShipmentById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get shipment by tracking number
    $app->get('/v1/shipments/tracking/{trackingNumber}', function ($request, $response, $args) use ($shipmentController) {
        $trackingNumber = $args['trackingNumber'] ?? '';
        $result = $shipmentController->getShipmentByTrackingNumber($trackingNumber);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create shipment
    $app->post('/v1/shipments', function ($request, $response) use ($shipmentController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $shipmentController->createShipment($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update shipment
    $app->patch('/v1/shipments/{id}', function ($request, $response, $args) use ($shipmentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $shipmentController->updateShipment($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Change tracking number
    $app->patch('/v1/shipments/{id}/tracking', function ($request, $response, $args) use ($shipmentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $newTracking = (string) ($data['tracking_number'] ?? '');
        $result = $shipmentController->changeTrackingNumber($id, $newTracking);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update shipment status
    $app->patch('/v1/shipments/{id}/status', function ($request, $response, $args) use ($shipmentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $status = (string) ($data['status'] ?? '');
        $result = $shipmentController->updateShipmentStatus($id, $status);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete shipment
    $app->delete('/v1/shipments/{id}', function ($request, $response, $args) use ($shipmentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $shipmentController->deleteShipment($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });
};
