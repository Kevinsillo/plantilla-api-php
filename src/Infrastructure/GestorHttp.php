<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use Exception;

class GestorHttp
{
    /**
     * @return string
     */
    public function get(string $url, array $parametros): string
    {
        $queryString = http_build_query($parametros);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $queryString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);

        return $data;
    }

    public function post(string $url, array $parametros): string
    {
        $queryString = http_build_query($parametros);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);

        return $data;
    }
}
