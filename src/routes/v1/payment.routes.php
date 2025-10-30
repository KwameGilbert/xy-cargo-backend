<?php

declare(strict_types=1);

/**
 * Payments API Routes
 *
 * These routes handle payment management operations (CRUD) and status updates.
 * All routes are protected by authentication middleware.
 */

require_once CONTROLLER . '/payment.controller.php';
require_once MIDDLEWARE . '/AuthMiddleware.php';

return function ($app): void {
    $paymentController = new PaymentsController();
    $authMiddleware = new AuthMiddleware();

    // Get payments with optional date filtering
    // NOTE: Date parameters are accepted here but ignored by the current controller logic.
    $app->get('/v1/payments', function ($request, $response) use ($paymentController) {
        $params = $request->getQueryParams();
        // $startDate = $params['start_date'] ?? null;
        // $endDate = $params['end_date'] ?? null;
        // $period = $params['period'] ?? null; // daily, weekly, monthly
        
        // Calling getPayments without parameters, as per the current controller's signature
        $result = $paymentController->getPayments(); 
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add($authMiddleware);

    // Get payment by ID
    $app->get('/v1/payments/{id}', function ($request, $response, $args) use ($paymentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $paymentController->getPaymentById($id);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add($authMiddleware);

    // Get payments by PARCEL ID (Replaced former /invoice/{invoiceId} route)
    $app->get('/v1/payments/parcel/{parcelId}', function ($request, $response, $args) use ($paymentController) {
        $parcelId = isset($args['parcelId']) ? (int) $args['parcelId'] : 0;
        $result = $paymentController->getPaymentsByParcelId($parcelId);
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get all pending payments
    $app->get('/v1/payments/status/pending', function ($request, $response) use ($paymentController) {
        $result = $paymentController->getPendingPayments();
        $data = $result;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Create a new payment (Updated required fields)
    // Expects: {"parcel_id": int, "client_id": int, "amount": float, "payment_method": string, "status"?: string}
    $app->post('/v1/payments', function ($request, $response) use ($paymentController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $paymentController->createPayment($data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add($authMiddleware);

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
    })->add($authMiddleware);
};