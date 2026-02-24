<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use PDO;
use PDOException;
use Backend\Domain\ManagerDatabase;

class ManagerMysql implements ManagerDatabase
{
    private string $db_host;
    private string $db_name;
    private string $db_user;
    private string $db_pass;
    private string $db_port;
    public PDO $connection;

    /**
     * MysqlManager constructor
     * @param string $db_host 
     * @param string $db_name 
     * @param string $db_user 
     * @param string $db_pass 
     * @param string $db_port 
     * @return void 
     * @throws PDOException 
     */
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
     * Get connection to database
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
            throw new PDOException("Connection with database failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Creates the `migrations` tracking table if it doesn't exist.
     * @return void
     */
    public function setup(): void
    {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(255) UNIQUE)");
    }

    /**
     * Fetches the list of executed migrations.
     * @return array List of migration names.
     */
    public function getExecutedMigrations(): array
    {
        return $this->connection
            ->query("SELECT name FROM migrations")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Logs a migration as executed in the database.
     * @param string $name Name of the migration file.
     * @return void
     */
    public function logMigration(string $name): void
    {
        $stmt = $this->connection->prepare("INSERT INTO migrations (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);
    }

    /**
     * Executes a migration SQL file.
     * @param string $filePath Path to the migration file.
     * @return void
     * @throws PDOException
     */
    public function runMigration(string $filePath): void
    {
        $query = file_get_contents($filePath);
        $this->connection->exec($query);
    }

    /**
     * Drops and recreates the database.
     * @return void
     */
    public function resetDatabase(): void
    {
        $this->connection->exec("DROP DATABASE IF EXISTS " . $this->db_name);
        $this->connection->exec("CREATE DATABASE " . $this->db_name);
        $this->connection->exec("USE " . $this->db_name);
    }
}
