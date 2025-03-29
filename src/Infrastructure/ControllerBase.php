<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use Backend\AuthMiddleware;
use Backend\Domain\Response;
use Backend\Domain\User;
use Firebase\JWT\JWT;

class ControllerBase
{
    private string $jwt_secret;
    private int $jwt_exp_days;
    private string $jwt_algorithm;
    protected array $parameters;
    protected Response $response;
    protected User $user;

    /**
     * Controller constructor
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->jwt_secret = $_ENV['JWT_SECRET'];
        $this->jwt_exp_days = (int) $_ENV['JWT_EXP_DAYS'];
        $this->jwt_algorithm = $_ENV['JWT_ALGORITHM'];
        $this->parameters = $parameters;
        $this->response = new Response();
        $this->user = new User();
    }

    /**
     * Check if the user is authenticated. 
     * If not, AuthMiddleware return Exception.
     * @return array 
     */
    public function isAuth(): array
    {
        $this->response->setSuccess("User authenticated");
        return $this->response->getResponse();
    }

    /**
     * Login de users
     * @return array
     */
    public function login(): array
    {
        if (!isset($this->parameters['user']) || !isset($this->parameters['password'])) {
            $this->response->setError("User and password are required", 400);
            return $this->response->getResponse();
        }

        # --------------------------------------------
        # TODO: Search user in your users implementation
        # --------------------------------------------
        $stored_users = [
            'root' => [
                'password' => '$2y$10$D70tD4vV168.d2EwiU2ab.heQUH9.Oeo.q65jB4BzuDqZhzkxcqqK', // password: root
                'role' => 'admin',
            ]
        ];

        if (!isset($stored_users[$this->parameters['user']])) {
            $this->response->setError("User not found", 404);
            return $this->response->getResponse();
        }

        $store_user = $stored_users[$this->parameters['user']];
        if (!password_verify($this->parameters['password'], $store_user['password'])) {
            $this->response->setError("Password incorrect", 401);
            return $this->response->getResponse();
        }
        # --------------------------------------------

        $payload = [
            'user' => [
                'user' => $this->parameters['user'],
                'role' => $store_user['role'],
            ]
        ];
        $jwt_encoded = JWT::encode(
            $payload,
            $this->jwt_secret,
            $this->jwt_algorithm
        );
        AuthMiddleware::createCookie($jwt_encoded, $this->jwt_exp_days);

        $this->response->setSuccess("Successful login");
        return $this->response->getResponse();
    }

    /**
     * Logout de users
     * @return array 
     */
    public function logout(): array
    {
        AuthMiddleware::expireCookie();
        $this->response->setSuccess("Successful logout");
        return $this->response->getResponse();
    }
}
