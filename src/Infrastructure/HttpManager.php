<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use Exception;

class HttpManager
{
    /**
     * Get method for HTTP requests
     * @param string $url 
     * @param array $parameters 
     * @return string 
     * @throws Exception 
     */
    public function get(string $url, array $parameters): string
    {
        $query_string = http_build_query($parameters);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $query_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);

        return $data;
    }

    /**
     * Post method for HTTP requests
     * @param string $url 
     * @param array $parameters 
     * @return string 
     * @throws Exception 
     */
    public function post(string $url, array $parameters): string
    {
        $query_string = http_build_query($parameters);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);

        return $data;
    }
}
