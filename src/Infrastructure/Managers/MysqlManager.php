<?php

declare(strict_types=1);

namespace Backend\Infrastructure\Managers;

use PDO;
use PDOException;
use Backend\Domain\Managers\DatabaseManager;

class MysqlManager implements DatabaseManager
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
                # This option enables exceptions for database errors, which allows
                # for better error handling.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                # This option sets the default fetch mode to associative arrays
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                # This option disables emulated prepared statements, which can 
                # lead to better performance and security.
                PDO::ATTR_EMULATE_PREPARES => false,
                # This option ensures that numeric values are returned as 
                # their native types (e.g., integers, floats) instead of strings.
                PDO::ATTR_STRINGIFY_FETCHES => false,
                # Multi-statement support is required for running 
                # migration files that contain multiple SQL statements.
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ];
            $pdo = new PDO(
                "mysql:host={$this->db_host}; port={$this->db_port};dbname={$this->db_name}",
                $this->db_user,
                $this->db_pass,
                $options
            );
            return $pdo;
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
        $this->connection->exec("CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(255) UNIQUE, executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)");
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

    public function getLastExecutedAt(): ?string
    {
        $result = $this->connection->query("SELECT MAX(executed_at) FROM migrations")->fetchColumn();
        return $result ?: null;
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
