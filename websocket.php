<?php

use Backend\Infrastructure\WebSocketController;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Dotenv\Dotenv;

const ROOT_DIR = __DIR__;
const PORT = 8081;

require(ROOT_DIR . '/vendor/autoload.php');
require(ROOT_DIR . '/src/Router.php');

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

// Crear servidor WebSocket
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocketController($router)
        )
    ),
    PORT
);

echo "Servidor WebSocket: ws://localhost:" . PORT . "\n";
$server->run();
