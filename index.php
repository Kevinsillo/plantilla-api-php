<?php

// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

const ROOT_DIR = __DIR__;

require(ROOT_DIR . '/vendor/autoload.php');
require(ROOT_DIR . '/src/Router.php');
require(ROOT_DIR . '/src/Petitions.php');

// Cargar el fichero de variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

// Reglas del CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
header('Content-Type: application/json');

// Controla que la petición sea HTTP (evita problemas de CORS)
if (!isset($_REQUEST) or !isset($_SERVER['REQUEST_METHOD'])) {
    http_response_code(400);
    die(json_encode([
        'error' => "Petición HTTP invalida",
    ]));
}

// Sistema de enrutamiento
$metodo = obtenerMetodoHTTP();
$servicio = obtenerServicio();
$parametros = obtenerParametros();

// Comprueba si el servicio solicitado existe
if (!isset($router[$metodo][$servicio])) {
    http_response_code(404);
    die(json_encode([
        'error' => "Servicio no disponible: [$metodo][$servicio]",
        'metodo' => $metodo,
        'servicio' => $servicio,
        'parametros' => $parametros
    ]));
}

// Montar el controlador
$gestorEnpoint = $router[$metodo][$servicio];
$controlador = $gestorEnpoint['controlador'];
$funcion = $gestorEnpoint['funcion'];

// Llamada a la función del controlador
$gestorControlador = new $controlador($parametros);
$datos = $gestorControlador->$funcion();

// Devuelve los datos y corta la ejecución
die(json_encode($datos, true));
