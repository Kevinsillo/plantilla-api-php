<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use SQLite3;
use Exception;
use Backend\Domain\ManagerDatabase;

class ManagerSQLite3 implements ManagerDatabase
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

    /**
     * Creates the `migrations` tracking table if it doesn't exist.
     * @return void
     */
    public function setup(): void
    {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS migrations (name TEXT UNIQUE)");
    }

    /**
     * Fetches the list of executed migrations.
     * @return array List of migration names.
     */
    public function getExecutedMigrations(): array
    {
        $results = $this->connection->query("SELECT name FROM migrations");
        $migrations = [];
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $migrations[] = $row['name'];
        }
        return $migrations;
    }

    /**
     * Logs a migration as executed in the database.
     * @param string $name Name of the migration file.
     * @return void
     */
    public function logMigration(string $name): void
    {
        $stmt = $this->connection->prepare("INSERT INTO migrations (name) VALUES (:name)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->execute();
    }

    /**
     * Executes a migration SQL file.
     * @param string $filePath Path to the migration file.
     * @return void
     * @throws Exception
     */
    public function runMigration(string $filePath): void
    {
        $query = file_get_contents($filePath);
        $this->connection->exec($query);
    }

    /**
     * Drops all tables in the SQLite database.
     * @return void
     */
    public function resetDatabase(): void
    {
        $results = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table'");
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            if ($row['name'] !== 'sqlite_sequence') {
                $this->connection->exec("DROP TABLE IF EXISTS " . $row['name']);
            }
        }
    }
}
