<?php

declare(strict_types=1);

use Backend\Domain\Response;

// Show errors
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

const ROOT_DIR = __DIR__;

require(ROOT_DIR . '/vendor/autoload.php');
require(ROOT_DIR . '/src/Router.php');
require(ROOT_DIR . '/src/Petitions.php');

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

// Headers for CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
header('Content-Type: application/json');

// Control that the request is HTTP (avoids CORS problems)
if (!isset($_REQUEST) or !isset($_SERVER['REQUEST_METHOD'])) {
    $response = new Response();
    $response->setError("Invalid HTTP request", 400);
    die(json_encode($response->getResponse()));
}

// Router system to manage the requests
$method = getHttpMethod();
$service = getService();
$parameters = getParameters();

// Check if the service is available
if (!isset($router[$method][$service])) {
    $response = new Response();
    $response->setError("Service not available: [$method][$service]", 404);
    die(json_encode($response->getResponse(), JSON_PRETTY_PRINT));
}

// Mount the controller and function
$endpoint_manager = $router[$method][$service];
$controller = $endpoint_manager['controller'];
$function = $endpoint_manager['function'];

// Execute a controller function
$controller_manager = new $controller($parameters);
$result = $controller_manager->$function();

// Return the response
die(json_encode($result, JSON_PRETTY_PRINT));
