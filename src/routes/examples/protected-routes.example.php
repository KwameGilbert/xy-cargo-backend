<?php

declare(strict_types=1);

/**
 * Example: How to use AuthMiddleware
 *
 * This file demonstrates how to apply the AuthMiddleware to protect routes.
 * You can apply it to individual routes or route groups.
 */

require_once CONTROLLER . '/client.controller.php';
require_once MIDDLEWARE . '/AuthMiddleware.php';

return function ($app): void {
    $clientsController = new ClientsController();
    $authMiddleware = new AuthMiddleware();

    // Example 1: Apply middleware to individual routes
    $app->get('/v1/protected/profile', function ($request, $response) use ($clientsController) {
        // Access authenticated user data
        $user = $request->getAttribute('user');

        // The user data contains the decoded JWT payload
        // $user['data'] contains the client information from login

        return $response->withJson([
            'status' => 'success',
            'message' => 'This is a protected route',
            'user' => $user
        ]);
    })->add($authMiddleware);

    // Example 2: Apply middleware to a route group (protects all routes in group)
    $app->group('/v1/protected', function ($group) use ($clientsController) {
        $group->get('/clients', function ($request, $response) use ($clientsController) {
            // This route is protected
            $result = $clientsController->getAllClients();
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
        });

        $group->get('/clients/{id}', function ($request, $response, $args) use ($clientsController) {
            // This route is protected
            $id = isset($args['id']) ? (int) $args['id'] : 0;
            $result = $clientsController->getClientById($id);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
        });

        $group->post('/clients', function ($request, $response) use ($clientsController) {
            // This route is protected
            $data = json_decode((string) $request->getBody(), true) ?? [];
            $result = $clientsController->createClient($data);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
        });
    })->add($authMiddleware);

    // Example 3: Public routes (no middleware)
    $app->get('/v1/public/status', function ($request, $response) {
        return $response->withJson([
            'status' => 'success',
            'message' => 'This is a public route - no authentication required'
        ]);
    });
};

/*
USAGE INSTRUCTIONS:

1. Include the AuthMiddleware in your route files:
   require_once MIDDLEWARE . '/AuthMiddleware.php';

2. Create an instance of the middleware:
   $authMiddleware = new AuthMiddleware();

3. Apply to individual routes:
   $app->get('/protected-route', $handler)->add($authMiddleware);

4. Apply to route groups:
   $app->group('/protected', function($group) {
       // routes here
   })->add($authMiddleware);

5. Access user data in your route handlers:
   $user = $request->getAttribute('user');
   // $user contains the decoded JWT payload

AUTHENTICATION FLOW:

1. Client logs in via POST /v1/auth/client/login
2. Receives JWT token in response
3. Client includes token in Authorization header:
   Authorization: Bearer <jwt_token>
4. Protected routes validate the token automatically
5. Invalid/expired tokens return 401 Unauthorized

ERROR RESPONSES:

- Missing Authorization header: 401 "Authorization header is required"
- Invalid format: 401 "Invalid authorization header format. Expected: Bearer <token>"
- Invalid/expired token: 401 "Invalid or expired token"
*/