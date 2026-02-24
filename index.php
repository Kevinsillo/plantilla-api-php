<?php

declare(strict_types=1);

use Backend\Domain\Response;
use Backend\Envs;
use Backend\Petitions;
use Backend\Router;

const ROOT_DIR = __DIR__;
require(ROOT_DIR . '/vendor/autoload.php');

try {
    # Load environment variables
    $envs = new Envs(ROOT_DIR);

    # Check required environment variables
    $envs::checkRequiredEnvVars();

    # If DEV_MODE is true, allow showing errors for debugging
    if (filter_var($_ENV['DEV_MODE'], FILTER_VALIDATE_BOOLEAN)) {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
    }

    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Origin: " . $_ENV['CORS_ORIGIN']);
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Authorization, Accept, Access-Control-Request-Method");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header('Content-Type: application/json');

    # Handle preflight requests
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    # Instance of the response
    $response = new Response();

    # Get request details
    $method = Petitions::getHttpMethod();
    $service = Petitions::getService();

    # Default route
    if (empty($service)) {
        $response->setSuccess("API is running");
        echo json_encode($response->getResponse());
        exit;
    }

    $parameters = Petitions::getParameters();

    # Initialize Router, validate the route and handle authentication
    $router = new Router($method, $service);
    $router->validateRoute();
    $router->handleAuthentication();

    # Execute the controller function
    $result = $router->executeController($parameters);

    # Return the response
    echo json_encode($result);
    exit;
} catch (Exception $e) {
    $response->setError($e->getMessage(), $e->getCode());
    echo json_encode($response->getResponse());
    exit;
}
