<?php

declare(strict_types=1);

/**
 * Shipment Types API Routes
 */

require_once CONTROLLER . '/shipment-type.controller.php';

return function ($app): void {
    $shipmentTypeController = new ShipmentTypesController();

    // Get all shipment types
    $app->get('/v1/shipment-types', function ($request, $response) use ($shipmentTypeController) {
        $result = $shipmentTypeController->getAllShipmentTypes();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get shipment type by ID
    $app->get('/v1/shipment-types/{id}', function ($request, $response, $args) use ($shipmentTypeController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $shipmentTypeController->getShipmentTypeById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create shipment type
    $app->post('/v1/shipment-types', function ($request, $response) use ($shipmentTypeController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $shipmentTypeController->createShipmentType($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update shipment type
    $app->patch('/v1/shipment-types/{id}', function ($request, $response, $args) use ($shipmentTypeController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $shipmentTypeController->updateShipmentType($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete shipment type
    $app->delete('/v1/shipment-types/{id}', function ($request, $response, $args) use ($shipmentTypeController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $shipmentTypeController->deleteShipmentType($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });
};