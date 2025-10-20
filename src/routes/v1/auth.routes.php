<?php

declare(strict_types=1);

require_once CONTROLLER . '/auth.controller.php';

return function ($app): void {
    $authController = new AuthController();

    // Client authentication
    // Expects: {"email":"...", "password":"..."}
    $app->post('/v1/auth/client/login', function ($request, $response) use ($authController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $result = $authController->authenticateClient($email, $password);
        $data_response = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });

    // Client sign up
    $app->post('/v1/auth/client/signup', function ($request, $response) use ($authController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $authController->clientSignUp($data);
        $data_response = json_decode($result, true);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($data_response['code']);
    });
};