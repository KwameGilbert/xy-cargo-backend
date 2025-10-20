<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{

    public static function generateToken(array $payload, ?int $expirySeconds = null): string
    {
        if ($expirySeconds === null) {
            $expirySeconds = (int)$_ENV['JWT_EXPIRY'];
        }
        
        $issuedAt = time();
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $issuedAt + $expirySeconds;
        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], $_ENV['JWT_ALGORITHM']);
        return $token;
    }

    public static function validateToken(string $token): ?array
    {
        try {
            return (array) JWT::decode($token, new Key($_ENV['JWT_SECRET'], $_ENV['JWT_ALGORITHM']));
        } catch (\Exception $e) {
            return null;
        }
    }
}
