<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use PDO;
use Exception;
use PDOException;

class ManagerSQLite
{
    private string $database_path;
    public PDO $connection;

    /**
     * SQLiteManager constructor
     * @param string $database_path Path to SQLite database
     */
    public function __construct(string $database_path)
    {
        $this->database_path = $database_path;
        $this->connection = $this->getConnection();
    }

    /**
     * Get connection to SQLite database
     * @return PDO 
     * @throws PDOException 
     */
    public function getConnection(): PDO
    {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
            ];
            return new PDO($this->database_path, null, null, $options);
        } catch (Exception $e) {
            throw new PDOException("Connection with SQLite database failed: " . $e->getMessage(), 500);
        }
    }
}
