<?php

declare(strict_types=1);

/**
 * Clients API Routes
 *
 * These routes handle client management operations (CRUD) and authentication.
 * Clients have fields: firstName, lastName, email, phone, address, company, password_hash.
 */

require_once CONTROLLER . '/client.controller.php';

return function ($app): void {
    $clientController = new ClientsController();

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

    // Login
    // // Expects: {"email":"...", "password":"..."}
    // $app->post('/v1/clients/login', function ($request, $response) use ($clientController) {
    //     $data = json_decode((string) $request->getBody(), true) ?? [];
    //     $email = (string) ($data['email'] ?? '');
    //     $password = (string) ($data['password'] ?? '');
    //     $result = $clientController->login($email, $password);
    //     $data_response = json_decode($result, true);
    //     $response->getBody()->write($result);
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    // });

    // Update password for a client
    // // Expects: {"client_id":..., "new_password":"..."}
    // $app->post('/v1/clients/password/update', function ($request, $response) use ($clientController) {
    //     $data = json_decode((string) $request->getBody(), true) ?? [];
    //     $clientId = (int) ($data['client_id'] ?? 0);
    //     $newPassword = (string) ($data['new_password'] ?? '');
    //     $result = $clientController->updatePassword($clientId, $newPassword);
    //     $data_response = json_decode($result, true);
    //     $response->getBody()->write($result);
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    // });
};