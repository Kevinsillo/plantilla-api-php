<?php

declare(strict_types=1);

/**
 * Get the HTTP method of the request
 * @return string 
 * @throws Exception 
 */
function getHttpMethod(): string
{
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    $permited_methods = ['POST', 'GET', 'DELETE'];

    if (in_array($method, $permited_methods)) {
        return strtolower($method);
    }

    throw new Exception('HTTP method not available: ' . $method);
}

/**
 * Get the WebSocket method of the request
 * @return string 
 */
function getWebSocketMethod(): string
{
    $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
    return $request_body['method'] ?? '';
}

/**
 * Get the service of the request
 * @return string 
 */
function getService(): string
{
    $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
    return $_REQUEST['service'] ?? $request_body['service'] ?? '';
}

/**
 * Get the parameters of the request
 * @return array 
 */
function getParameters(): array
{
    $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
    $parameters = array_merge($request_body, $_REQUEST);
    unset($parameters['service']);
    return $parameters;
}
