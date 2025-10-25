<?php

declare(strict_types=1);

/**
 * Clients API Routes
 *
 * These routes handle client management operations (CRUD) and authentication.
 * Clients have fields: firstName, lastName, email, phone, address, company, password_hash.
 */

require_once CONTROLLER . '/client.controller.php';
require_once CONTROLLER . '/parcel.controller.php';
require_once MIDDLEWARE . '/AuthMiddleware.php';

return function ($app): void {
    $clientController = new ClientsController();
    // Parcels controller for client-facing parcel endpoints
    $parcelController = new ParcelsController();

    // Get all clients
    $app->get('/v1/clients', function ($request, $response) use ($clientController) {
        $result = $clientController->getAllClients();
        $data = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get client by ID
    $app->get('/v1/clients/{id}', function ($request, $response, $args) use ($clientController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $clientController->getClientById($id);
        $data = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get client by email
    $app->get('/v1/clients/email/{email}', function ($request, $response, $args) use ($clientController) {
        $email = $args['email'] ?? '';
        $result = $clientController->getClientByEmail($email);
        $data = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get client by phone
    $app->get('/v1/clients/phone/{phone}', function ($request, $response, $args) use ($clientController) {
        $phone = $args['phone'] ?? '';
        $result = $clientController->getClientByPhone($phone);
        $data = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get client dashboard data (protected route)
    $app->get('/v1/clients/dashboard/data', function ($request, $response) use ($clientController) {
        // Get authenticated user from middleware
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        // echo (var_dump($clientId));
        $result = $clientController->getClientDashboard($clientId);
        $data_response = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    })->add(new AuthMiddleware());

    // Get authenticated client profile (protected)
    $app->get('/v1/clients/profile/data', function ($request, $response) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $result = $clientController->getClientProfileData($clientId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Get parcels for authenticated client (protected)
    $app->get('/v1/clients/parcels/data', function ($request, $response) use ($parcelController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $result = $parcelController->getClientParcels($clientId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Get parcel details for authenticated client (protected)
    $app->get('/v1/clients/parcels/{id}', function ($request, $response, $args) use ($parcelController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $parcelId = isset($args['id']) ? (int) $args['id'] : 0;
        
        if (!$parcelId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid parcel ID'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $result = $parcelController->getClientParcelById($clientId, $parcelId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Get client payments data (invoices and payment history) - protected
    $app->get('/v1/clients/payments/data', function ($request, $response) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $result = $clientController->getClientPaymentsData($clientId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Get single invoice details for authenticated client - protected
    $app->get('/v1/clients/invoices/{id}', function ($request, $response, $args) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $invoiceId = isset($args['id']) ? (int) $args['id'] : 0;
        
        if (!$invoiceId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid invoice ID'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $result = $clientController->getClientInvoiceById($clientId, $invoiceId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Create a new client
    // Expects: {"firstName":"...", "lastName":"...", "email":"...", "password":"...", "phone":"..." (optional), "address":"..." (optional), "company":"..." (optional)}
    $app->post('/v1/clients', function ($request, $response) use ($clientController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $clientController->createClient($data);
        $data_response = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Update client by ID
    // Accepts: {"firstName":"...", "lastName":"...", "email":"...", "phone":"...", "address":"...", "company":"..."} (all fields optional)
    $app->patch('/v1/clients/{id}', function ($request, $response, $args) use ($clientController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $clientController->updateClient($id, $data);
        $data_response = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Delete client by ID
    $app->delete('/v1/clients/{id}', function ($request, $response, $args) use ($clientController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $clientController->deleteClient($id);
        $data = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data['code']);
    });

    // Get client notifications (protected)
    $app->get('/v1/clients/notifications/data', function ($request, $response) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $result = $clientController->getNotifications($clientId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Mark notification as read (protected)
    $app->patch('/v1/clients/notifications/{id}/read', function ($request, $response, $args) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $notificationId = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $clientController->markNotificationAsRead($clientId, $notificationId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Mark all notifications as read (protected)
    $app->patch('/v1/clients/notifications/mark-all-read', function ($request, $response) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $result = $clientController->markAllNotificationsAsRead($clientId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Delete notification (protected)
    $app->delete('/v1/clients/notifications/{id}', function ($request, $response, $args) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $notificationId = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $clientController->deleteNotification($clientId, $notificationId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Get client settings (protected)
    $app->get('/v1/clients/settings/data', function ($request, $response) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $result = $clientController->getClientSettings($clientId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Update client settings (protected)
    $app->patch('/v1/clients/settings/data', function ($request, $response) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $clientController->updateClientSettings($clientId, $data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());

    // Update client profile (protected)
    $app->patch('/v1/clients/profile/data', function ($request, $response) use ($clientController) {
        $user = $request->getAttribute('user');
        if (!$user) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $clientId = (int) $user['data']->client_id;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $clientController->updateClientProfile($clientId, $data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    })->add(new AuthMiddleware());
 
};