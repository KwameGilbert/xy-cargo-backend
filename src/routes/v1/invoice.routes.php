<?php

declare(strict_types=1);

/**
 * Invoices API Routes
 *
 * These routes handle invoice management operations (CRUD) and status updates.
 */

require_once CONTROLLER . '/invoice.controller.php';

return function ($app): void {
    $invoiceController = new InvoicesController();

    // Get all invoices
    $app->get('/v1/invoices', function ($request, $response) use ($invoiceController) {
        $result = $invoiceController->getAllInvoices();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get invoice by ID
    $app->get('/v1/invoices/{id}', function ($request, $response, $args) use ($invoiceController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $invoiceController->getInvoiceById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get invoices by parcel ID
    $app->get('/v1/invoices/parcel/{parcelId}', function ($request, $response, $args) use ($invoiceController) {
        $parcelId = isset($args['parcelId']) ? (int) $args['parcelId'] : 0;
        $result = $invoiceController->getInvoicesByParcelId($parcelId);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get invoices by client ID
    $app->get('/v1/invoices/client/{clientId}', function ($request, $response, $args) use ($invoiceController) {
        $clientId = isset($args['clientId']) ? (int) $args['clientId'] : 0;
        $result = $invoiceController->getInvoicesByClientId($clientId);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create a new invoice
    // Expects: {"parcel_id": int, "client_id": int, "amount": float, "status"?: string}
    $app->post('/v1/invoices', function ($request, $response) use ($invoiceController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $invoiceController->createInvoice($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update invoice by ID
    // Accepts: {"amount"?: float, "status"?: string}
    $app->patch('/v1/invoices/{id}', function ($request, $response, $args) use ($invoiceController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $invoiceController->updateInvoice($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update invoice status
    // Expects: {"status": string}
    $app->patch('/v1/invoices/{id}/status', function ($request, $response, $args) use ($invoiceController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $status = $data['status'] ?? '';
        $result = $invoiceController->updateInvoiceStatus($id, $status);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete invoice by ID
    $app->delete('/v1/invoices/{id}', function ($request, $response, $args) use ($invoiceController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $invoiceController->deleteInvoice($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });
};