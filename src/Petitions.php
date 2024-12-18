<?php

declare(strict_types=1);

/**
 * Obtiene el método HTTP de la petición
 * @return string 
 * @throws Exception 
 */
function obtenerMetodoHTTP(): string
{
    $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
    $permited_methods = ['POST', 'GET', 'DELETE'];

    if (in_array($metodo, $permited_methods)) {
        return strtolower($metodo);
    }

    throw new Exception('Método HTTP no disponible: ' . $metodo);
}

/**
 * Obtiene el método de la petición WebSocket
 * @return string 
 */
function obtenerMetodoWebSocket(): string
{
    $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
    return $request_body['method'] ?? '';
}

/**
 * Obtiene el servicio de la petición
 * @return string 
 */
function obtenerServicio(): string
{
    $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
    return $_REQUEST['service'] ?? $request_body['service'] ?? '';
}

/**
 * Obtiene los parámetros de la petición
 * @return array 
 */
function obtenerParametros(): array
{
    $request_body = json_decode(file_get_contents('php://input'), true) ?? [];
    $parametros = array_merge($request_body, $_REQUEST);
    unset($parametros['service']);
    return $parametros;
}
