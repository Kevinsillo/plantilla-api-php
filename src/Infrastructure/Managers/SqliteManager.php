<?php

declare(strict_types=1);

namespace Backend\Infrastructure\Managers;

use PDO;
use Exception;
use PDOException;
use Backend\Domain\Managers\DatabaseManager;

class SqliteManager implements DatabaseManager
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
            ];
            return new PDO('sqlite:' . $this->database_path, null, null, $options);
        } catch (Exception $e) {
            throw new PDOException("Connection with SQLite database failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Creates the `migrations` tracking table if it doesn't exist.
     * @return void
     */
    public function setup(): void
    {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS migrations (name TEXT UNIQUE, executed_at TEXT NOT NULL DEFAULT (datetime('now')))");
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
     * Drops all tables in the SQLite database.
     * @return void
     */
    public function resetDatabase(): void
    {
        $tables = $this->connection
            ->query("SELECT name FROM sqlite_master WHERE type='table'")
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            if ($table !== 'sqlite_sequence') {
                $this->connection->exec("DROP TABLE IF EXISTS " . $table);
            }
        }
    }
}
