<?php

declare(strict_types=1);

/**
 * Shipment Tracking Updates API Routes
 */

require_once CONTROLLER . '/shipment-tracking-update.controller.php';

return function ($app): void {
    $trackingUpdateController = new ShipmentTrackingUpdatesController();

    // Get all tracking updates
    $app->get('/v1/shipment-tracking-updates', function ($request, $response) use ($trackingUpdateController) {
        $result = $trackingUpdateController->getAllTrackingUpdates();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get tracking update by ID
    $app->get('/v1/shipment-tracking-updates/{id}', function ($request, $response, $args) use ($trackingUpdateController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $trackingUpdateController->getTrackingUpdateById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get tracking updates by shipment ID
    $app->get('/v1/shipments/{shipmentId}/tracking-updates', function ($request, $response, $args) use ($trackingUpdateController) {
        $shipmentId = isset($args['shipmentId']) ? (int) $args['shipmentId'] : 0;
        $result = $trackingUpdateController->getTrackingUpdatesByShipmentId($shipmentId);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create tracking update
    $app->post('/v1/shipment-tracking-updates', function ($request, $response) use ($trackingUpdateController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $trackingUpdateController->createTrackingUpdate($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update tracking update
    $app->patch('/v1/shipment-tracking-updates/{id}', function ($request, $response, $args) use ($trackingUpdateController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $trackingUpdateController->updateTrackingUpdate($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete tracking update
    $app->delete('/v1/shipment-tracking-updates/{id}', function ($request, $response, $args) use ($trackingUpdateController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $trackingUpdateController->deleteTrackingUpdate($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });
};