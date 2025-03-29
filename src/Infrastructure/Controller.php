<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use DateTime;

class Controller extends ControllerBase
{
    /**
     * Controller constructor
     * @param array $parametros
     */
    public function __construct(array $parametros)
    {
        parent::__construct($parametros);
    }

    /**
     * Hello World
     * @return array
     */
    public function helloWorld(): array
    {
        if (!isset($this->parameters['ping'])) {
            $this->response->setError('Ping not provided', 400);
            return $this->response->getResponse();
        }

        $datetime = new DateTime();
        $this->response->setSuccess('Hello World');
        $this->response->setData([
            'pong' => $datetime->format('c'),
        ]);
        return $this->response->getResponse();
    }
}
