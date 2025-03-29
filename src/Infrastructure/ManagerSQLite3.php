<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use SQLite3;
use Exception;

class ManagerSQLite3
{
    private string $database_path;
    public SQLite3 $connection;

    /**
     * Constructor de la clase
     * @param string $database_path Ruta de la base de datos
     */
    public function __construct(string $database_path)
    {
        $this->database_path = $database_path;
        $this->connection = $this->getConnection();
    }

    /**
     * Obtener la conexión a la base de datos
     * @return SQLite3
     */
    private function getConnection(): SQLite3
    {
        try {
            $database = new SQLite3($this->database_path);
            $database->enableExceptions(true);
            $database->exec('PRAGMA foreign_keys = ON;');
            return $database;
        } catch (Exception $e) {
            throw new Exception("Connection with database failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Destructor de la clase
     */
    public function __destruct()
    {
        $this->connection->close();
    }
}
