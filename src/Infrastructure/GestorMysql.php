<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use PDO;
use PDOException;

class GestorMysql
{
    private string $db_host;
    private string $db_name;
    private string $db_user;
    private string $db_pass;
    private string $db_port;
    public $connection;

    public function __construct(string $db_host, string $db_name, string $db_user, string $db_pass, string $db_port)
    {
        $this->db_host = $db_host;
        $this->db_name = $db_name;
        $this->db_user = $db_user;
        $this->db_pass = $db_pass;
        $this->db_port = $db_port;
        $this->connection = $this->getConnection();
    }

    /**
     * Devuelve la conexión a la base de datos
     * @return PDO 
     * @throws PDOException 
     */
    private function getConnection(): PDO
    {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ];
            return new PDO("mysql:host={$this->db_host}; port={$this->db_port};dbname={$this->db_name}", $this->db_user, $this->db_pass, $options);
        } catch (PDOException $e) {
            throw new PDOException("Error en la conexión con la base de datos: {$e->getMessage()}");
        }
    }
}
