<?php

declare(strict_types=1);

namespace Backend\Domain;

class Response
{
    private string $status;
    private string $message;
    private array $data;

    public function __construct()
    {
        $this->status = '';
        $this->message = '';
        $this->data = [];
    }

    public function setSuccess(string $message, int $code = 200)
    {
        http_response_code($code);
        $this->status = 'success';
        $this->message = $message;
    }

    public function setError(string $error, int $code = 400)
    {
        http_response_code($code);
        $this->status = 'error';
        $this->message = $error;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getResponse()
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'timestamp' => date('c'),
            'data' => $this->data
        ];
    }
}