<?php

declare(strict_types=1);

namespace Backend\Domain;

class Response
{
    private string $status;
    private string $message;
    private array $data;

    /**
     * Constructor
     * @return void 
     */
    public function __construct()
    {
        $this->status = '';
        $this->message = '';
        $this->data = [];
    }

    /**
     * Set success response
     * @param string $message 
     * @param int $code 
     * @return void 
     */
    public function setSuccess(string $message, int $code = 200)
    {
        http_response_code($code);
        $this->status = 'success';
        $this->message = $message;
    }

    /**
     * Set error response
     * @param string $error 
     * @param int $code 
     * @return void 
     */
    public function setError(string $error, int $code = 400)
    {
        http_response_code($code);
        $this->status = 'error';
        $this->message = $error;
    }

    /**
     * Set data response
     * @param mixed $data 
     * @return void 
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get response
     * @return array
     */
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
