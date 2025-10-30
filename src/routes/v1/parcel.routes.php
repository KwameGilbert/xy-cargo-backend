<?php

declare(strict_types=1);

/**
 * Parcels API Routes
 *
 * These routes handle parcel management operations (CRUD), status updates,
 * and item management. Parcels can be created with or without items.
 */

require_once CONTROLLER . '/parcel.controller.php';

return function ($app): void {
    $parcelController = new ParcelsController();

    // Get all parcels
    $app->get('/v1/parcels', function ($request, $response) use ($parcelController) {
        $result = $parcelController->getAllParcels();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get parcel by ID
    $app->get('/v1/parcels/{id}', function ($request, $response, $args) use ($parcelController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $parcelController->getParcelById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get parcel with items by ID
    $app->get('/v1/parcels/{id}/with-items', function ($request, $response, $args) use ($parcelController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $parcelController->getParcelWithItems($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get parcels by client ID
    $app->get('/v1/parcels/client/{clientId}', function ($request, $response, $args) use ($parcelController) {
        $clientId = isset($args['clientId']) ? (int) $args['clientId'] : 0;
        $result = $parcelController->getParcelsByClientId($clientId);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get parcel by tracking number
    $app->get('/v1/parcels/tracking/{trackingNumber}', function ($request, $response, $args) use ($parcelController) {
        $trackingNumber = $args['trackingNumber'] ?? '';
        $result = $parcelController->getParcelByTrackingNumber($trackingNumber);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get parcel tracking by tracking number
    $app->get('/v1/parcels/track/{trackingNumber}', function ($request, $response, $args) use ($parcelController) {
        $trackingNumber = $args['trackingNumber'] ?? '';
        $result = $parcelController->getParcelTracking($trackingNumber);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create a new parcel
    // Expects: {"client_id": int, "description"?: string, "weight"?: float, "dimensions"?: string, "declared_value"?: float, "shipping_cost"?: float, "payment_status"?: string, "tags"?: array, "items"?: array}
    $app->post('/v1/parcels', function ($request, $response) use ($parcelController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $parcelController->createParcel($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update parcel by ID
    // Accepts: {"description"?: string, "weight"?: float, "dimensions"?: string, "status"?: string, "declared_value"?: float, "shipping_cost"?: float, "payment_status"?: string, "tags"?: array}
    $app->patch('/v1/parcels/{id}', function ($request, $response, $args) use ($parcelController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $parcelController->updateParcel($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update parcel status
    // Expects: {"status": string}
    $app->patch('/v1/parcels/{id}/status', function ($request, $response, $args) use ($parcelController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $status = $data['status'] ?? '';
        $result = $parcelController->updateParcelStatus($id, $status);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update payment status
    // Expects: {"payment_status": string}
    $app->patch('/v1/parcels/{id}/payment-status', function ($request, $response, $args) use ($parcelController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $paymentStatus = $data['payment_status'] ?? '';
        $result = $parcelController->updatePaymentStatus($id, $paymentStatus);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete parcel by ID
    $app->delete('/v1/parcels/{id}', function ($request, $response, $args) use ($parcelController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $parcelController->deleteParcel($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Add item to parcel
    // Expects: {"name": string, "quantity"?: int, "value"?: float, "weight"?: float, "height"?: float, "width"?: float, "length"?: float, "fragile"?: bool, "special_packaging"?: bool}
    $app->post('/v1/parcels/{id}/items', function ($request, $response, $args) use ($parcelController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $parcelController->addItemToParcel($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update parcel item
    // Accepts: {"name"?: string, "quantity"?: int, "value"?: float, "weight"?: float, "height"?: float, "width"?: float, "length"?: float, "fragile"?: bool, "special_packaging"?: bool}
    $app->patch('/v1/parcels/items/{itemId}', function ($request, $response, $args) use ($parcelController) {
        $itemId = isset($args['itemId']) ? (int) $args['itemId'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $parcelController->updateParcelItem($itemId, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete parcel item
    $app->delete('/v1/parcels/items/{itemId}', function ($request, $response, $args) use ($parcelController) {
        $itemId = isset($args['itemId']) ? (int) $args['itemId'] : 0;
        $result = $parcelController->deleteParcelItem($itemId);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });
};