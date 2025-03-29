<?php

declare(strict_types=1);

namespace Backend;

use Exception;
use Backend\AuthMiddleware;
use Backend\Infrastructure\ControllerBase;
use Backend\Infrastructure\Controller;

class Router
{
    private string $method;
    private string $service;
    private array $routes = [
        'WS' => [],
        'GET' => [
            'isAuth' => [
                'controller' => ControllerBase::class,
                'function' => 'isAuth',
                'auth' => true
            ],
            'logout' => [
                'controller' => ControllerBase::class,
                'function' => 'logout',
                'auth' => true
            ],
            'helloWorld' => [
                'controller' => Controller::class,
                'function' => 'helloWorld',
                'auth' => true
            ],
        ],
        'POST' => [
            'login' => [
                'controller' => ControllerBase::class,
                'function' => 'login',
                'auth' => false
            ]
        ]
    ];

    public function __construct(string $method, string $service)
    {
        $this->method = $method;
        $this->service = $service;
    }

    /**
     * Validate the route
     * 
     * @throws Exception
     */
    public function validateRoute(): void
    {
        if (!isset($this->routes[$this->method][$this->service])) {
            throw new Exception("Service not available: [{$this->method}][{$this->service}]", 404);
        }
    }

    /**
     * Handle route authentication
     * 
     * @throws Exception
     */
    public function handleAuthentication(): void
    {
        if ($this->routes[$this->method][$this->service]['auth']) {
            try {
                AuthMiddleware::isAuthorized();
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), 401);
            }
        }
    }

    /**
     * Mount and execute the controller function
     * 
     * @param array $parameters
     * @throws Exception
     */
    public function executeController(array $parameters)
    {
        $route = $this->routes[$this->method][$this->service];
        $controller = $route['controller'];
        $function = $route['function'];

        if (!class_exists($controller)) {
            throw new Exception("Controller not found: {$controller}", 404);
        }

        if (!method_exists($controller, $function)) {
            throw new Exception("Function not found: {$function} in {$controller}", 404);
        }

        $controller_manager = new $controller($parameters);
        return $controller_manager->$function();
    }
}
