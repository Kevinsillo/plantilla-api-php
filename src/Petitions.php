<?php

declare(strict_types=1);

namespace Backend;

use Exception;

class Petitions
{
    private static ?array $requestBody = null;

    /**
     * Get the HTTP method of the request
     *
     * @return string
     * @throws Exception
     */
    public static function getHttpMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $permitted_methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

        if (in_array($method, $permitted_methods)) {
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
        return self::getRequestBody()['method'] ?? '';
    }

    /**
     * Get the service of the request
     *
     * @return string
     */
    public static function getService(): string
    {
        return $_GET['service'] ?? self::getRequestBody()['service'] ?? '';
    }

    /**
     * Get the parameters of the request
     *
     * @return array
     */
    public static function getParameters(): array
    {
        $parameters = array_merge(self::getRequestBody(), $_GET);
        if (!empty($_FILES)) {
            $parameters = array_merge($parameters, $_FILES);
        }
        unset($parameters['service']);
        return $parameters;
    }

    /**
     * Get the request body as an associative array
     * @return array 
     */
    private static function getRequestBody(): array
    {
        if (self::$requestBody === null) {
            self::$requestBody = json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return self::$requestBody;
    }
}
