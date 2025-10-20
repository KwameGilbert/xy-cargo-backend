<?php

declare(strict_types=1);

/**
 * Payments API Routes
 *
 * These routes handle payment management operations (CRUD) and status updates.
 */

require_once CONTROLLER . '/payment.controller.php';

return function ($app): void {
    $paymentController = new PaymentsController();

    // Get all payments
    $app->get('/v1/payments', function ($request, $response) use ($paymentController) {
        $result = $paymentController->getAllPayments();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get payment by ID
    $app->get('/v1/payments/{id}', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $paymentController->getPaymentById($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get payments by invoice ID
    $app->get('/v1/payments/invoice/{invoiceId}', function ($request, $response, $args) use ($paymentController) {
        $invoiceId = isset($args['invoiceId']) ? (int) $args['invoiceId'] : 0;
        $result = $paymentController->getPaymentsByInvoiceId($invoiceId);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create a new payment
    // Expects: {"invoice_id": int, "amount": float, "payment_method": string, "status"?: string}
    $app->post('/v1/payments', function ($request, $response) use ($paymentController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $paymentController->createPayment($data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update payment by ID
    // Accepts: {"amount"?: float, "payment_method"?: string, "status"?: string}
    $app->patch('/v1/payments/{id}', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $paymentController->updatePayment($id, $data);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update payment status
    // Expects: {"status": string}
    $app->patch('/v1/payments/{id}/status', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $status = $data['status'] ?? '';
        $result = $paymentController->updatePaymentStatus($id, $status);
        $data_response = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete payment by ID
    $app->delete('/v1/payments/{id}', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $paymentController->deletePayment($id);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });
};