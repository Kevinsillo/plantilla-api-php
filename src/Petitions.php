<?php

declare(strict_types=1);

namespace Backend;

use Exception;

class Petitions
{
    /**
     * Get the HTTP method of the request
     * 
     * @return string
     * @throws Exception
     */
    public static function getHttpMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $permited_methods = ['POST', 'OPTIONS', 'GET', 'DELETE'];

        if (in_array($method, $permited_methods)) {
            return $method;
        }

        throw new Exception('HTTP method not available: ' . $method, 405);
    }

    /**
     * Get the WebSocket method of the request
     * 
     * @return string
     */
    public static function getWebSocketMethod(): string
    {
        $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
        return $request_body['method'] ?? '';
    }

    /**
     * Get the service of the request
     * 
     * @return string
     */
    public static function getService(): string
    {
        $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
        return $_REQUEST['service'] ?? $request_body['service'] ?? '';
    }

    /**
     * Get the parameters of the request
     * 
     * @return array
     */
    public static function getParameters(): array
    {
        $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
        $parameters = array_merge($request_body, $_REQUEST);
        if (!empty($_FILES)) {
            $parameters = array_merge($parameters, $_FILES);
        }
        unset($parameters['service']);
        return $parameters;
    }
}
