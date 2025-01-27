<?php

use Backend\Infrastructure\ControllerWebSocket;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Dotenv\Dotenv;

const ROOT_DIR = __DIR__;
require(ROOT_DIR . '/vendor/autoload.php');
require(ROOT_DIR . '/src/Router.php');

# Load environment variables
$dotenv = Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

# Error for missing PORT environment variable
if (!isset($_ENV['PORT'])) {
    throw new Exception('PORT environment variable is missing');
}

# Instance websocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ControllerWebSocket($router)
        )
    ),
    $_ENV['PORT']
);

echo "Server running at ws://localhost:" . $_ENV['PORT'] . "\n";
$server->run();
