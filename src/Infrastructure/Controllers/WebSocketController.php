<?php

declare(strict_types=1);

namespace Backend\Infrastructure\Controllers;

use Backend\Router;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

class WebSocketController implements MessageComponentInterface
{
    protected $clients;
    protected $router;

    public function __construct(Router $router)
    {
        $this->clients = new SplObjectStorage;
        $this->router = $router;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->offsetSet($conn);
        /** @var \Ratchet\ConnectionInterface&object{resourceId: int} $conn */
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        // Maneja el servicio solicitado
        $servicio = $data['service'];
        unset($data['service']);
        $parametros = $data;

        // Comprueba si el servicio solicitado existe
        if (!isset($this->router['WS'][$servicio])) {
            echo "Servicio no disponible: ['WS'] => [$servicio]\n";
            return;
        }

        // Montar el controlador
        $gestorEnpoint = $this->router['WS'][$servicio];
        $controlador = $gestorEnpoint['controlador'];
        $funcion = $gestorEnpoint['funcion'];

        // Llamada a la función del controlador
        $gestorControlador = new $controlador($parametros);
        $datos = $gestorControlador->$funcion();

        // Agregar el tipo de mensaje
        $datos['ws_type'] = $servicio;

        // Devuelve los datos a todos los clientes
        foreach ($this->clients as $client) {
            $client->send(json_encode($datos));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->offsetUnset($conn);
        /** @var \Ratchet\ConnectionInterface&object{resourceId: int} $conn */
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
