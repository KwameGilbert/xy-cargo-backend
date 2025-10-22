<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Authentication Middleware
 *
 * Validates Bearer tokens in Authorization header and protects routes.
 * Adds authenticated user data to request attributes for use in controllers.
 */
class AuthMiddleware
{
    /**
     * Process incoming request and validate authentication
     *
     * @param Request $request
     * @param RequestHandler $handler
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Get Authorization header
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->createUnauthorizedResponse('Authorization header is required');
        }

        // Check if it's a Bearer token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $this->createUnauthorizedResponse('Invalid authorization header format. Expected: Bearer <token>');
        }

        $token = $matches[1];

        // Validate the JWT token
        require_once HELPER . '/JwtHelper.php';
        $decoded = JwtHelper::validateToken($token);

        if ($decoded === null) {
            return $this->createUnauthorizedResponse('Invalid or expired token');
        }

        // Add user data to request attributes for use in controllers
        $request = $request->withAttribute('user', $decoded);

        // Continue with the request
        return $handler->handle($request);
    }

    /**
     * Create an unauthorized response
     *
     * @param string $message
     * @return Response
     */
    private function createUnauthorizedResponse(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'code' => 401,
            'message' => $message
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}