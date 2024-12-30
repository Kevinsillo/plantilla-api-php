<?php

declare(strict_types=1);

namespace Backend;

use Backend\Infrastructure\Controller;

$router = [
    'WS' => [],
    'GET' => [
        'hello_world' => [
            'controller' => Controller::class,
            'function' => 'helloWorld'
        ],
    ],
    'POST' => []
];
