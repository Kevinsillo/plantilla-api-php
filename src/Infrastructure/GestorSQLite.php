<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use PDO;
use Exception;
use PDOException;

class GestorSQLite
{
    private string $sqlite_path;

    public function __construct()
    {
        $this->sqlite_path = "sqlite:" . ROOT_DIR . "/database/" . $_ENV['DATABASE_NAME'];
        return $this->init();
    }
    public function init()
    {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
            ];
            return new PDO($this->sqlite_path, null, null, $options);
        } catch (Exception $e) {
            throw new PDOException("Falló la conexión: " . $e->getMessage(), (int)$e->getCode());
        }
    }
}
