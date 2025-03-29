<?php

declare(strict_types=1);

namespace Backend;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Backend\Domain\Response;

class AuthMiddleware
{
    /**
     * Check if the user is authenticated
     * @return void 
     * @throws Exception 
     */
    public static function isAuthorized(): void
    {
        $response = new Response();

        if (!isset($_COOKIE['auth_token'])) {
            $response->setError("User not authenticated", 401);
            die(json_encode($response->getResponse()));
        }

        $jwt = $_COOKIE['auth_token'];
        $secret_key = $_ENV['JWT_SECRET'];

        try {
            $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
            # The decoded token is stored in the session
            $_SESSION['user'] = $decoded->user;
        } catch (ExpiredException $e) {
            throw new Exception("Token expired", 401);
        } catch (SignatureInvalidException $e) {
            throw new Exception("Invalid token signature", 401);
        } catch (Exception $e) {
            throw new Exception("Invalid token", 401);
        }
    }

    /**
     * Create a cookie with the JWT token
     * @param string $jwt_encoded 
     * @param int $expiration_days 
     * @return void
     */
    public static function createCookie(string $jwt_encoded, int $expiration_days): void
    {
        setcookie('auth_token', $jwt_encoded, [
            'expires' => time() + (60 * 60 * 24 * $expiration_days),
            'path' => '/',
            'httponly' => true,
            'secure' => false,
            'samesite' => 'strict'
        ]);
    }

    /**
     * Force the cookie to expire
     * @return void
     */
    public static function expireCookie(): void
    {
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => false,
            'samesite' => 'strict'
        ]);
    }
}
