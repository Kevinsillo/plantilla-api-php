<?php

declare(strict_types=1);

use Backend\Domain\Response;
use Backend\Petitions;
use Backend\Router;

# Show errors
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

const ROOT_DIR = __DIR__;
require(ROOT_DIR . '/vendor/autoload.php');

# Headers for CORS
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Authorization, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Allow: GET, POST, DELETE");
header('Content-Type: application/json');

# Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

# Instance of the response
$response = new Response();

try {
    # Get request details
    $method = Petitions::getHttpMethod();
    $service = Petitions::getService();
    $parameters = Petitions::getParameters();

    # Initialize Router, validate the route and handle authentication
    $router = new Router($method, $service);
    $router->validateRoute();
    $router->handleAuthentication();

    # Execute the controller function
    $result = $router->executeController($parameters);

    # Return the response
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
} catch (Exception $e) {
    $response->setError($e->getMessage(), $e->getCode());
    echo json_encode($response->getResponse(), JSON_PRETTY_PRINT);
    exit;
}
