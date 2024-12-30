<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use Backend\Domain\Response;

class Controller
{
    private array $parametros;
    private Response $response;

    public function __construct(array $parametros)
    {
        $this->parametros = $parametros;
        $this->response = new Response();
    }

    public function helloWorld(): array
    {
        $this->response->setSuccess('Hello World');
        return $this->response->getResponse();
    }
}
