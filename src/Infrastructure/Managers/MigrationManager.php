<?php

declare(strict_types=1);

namespace Backend\Infrastructure\Managers;

use Deckr\Domain\DatabaseManager;
use Exception;

class MigrationManager
{
    private DatabaseManager $db;
    private string $migrationsFolder;
    /** @var string[] */
    private array $logs = [];

    /**
     * @throws Exception If the migrations folder cannot be resolved or the DB connection fails.
     */
    public function __construct(
        private readonly string $driver,
        string $rootDir,
    ) {
        $this->db = $this->createDatabaseManager($driver);
        $this->db->setup();

        $resolved = realpath($rootDir . '/' . $_ENV['MIGRATIONS_FOLDER']);
        if ($resolved === false) {
            throw new Exception("Migrations folder not found: " . $_ENV['MIGRATIONS_FOLDER']);
        }
        $this->migrationsFolder = $resolved;
    }

    /**
     * Handles POST action dispatching with internal error catching.
     */
    public function handlePostAction(array $postData): void
    {
        try {
            if (isset($postData['run_migrations'])) {
                $this->runPendingMigrations();
            }

            if (isset($postData['reset_migrations'])) {
                $this->resetDatabase();
            }
        } catch (Exception $e) {
            $this->logs[] = "Error: " . $e->getMessage();
        }
    }

    /**
     * Returns the complete view state needed by the HTML template.
     *
     * @return array{
     *     logs: string[],
     *     allMigrations: string[],
     *     executedMigrations: string[],
     *     outdatedMigrations: string[],
     *     dbInfo: array<string, mixed>,
     * }
     */
    public function getViewState(): array
    {
        $executedMigrations = $this->db->getExecutedMigrations();
        $allMigrations = $this->getAllMigrations();

        return [
            'logs' => $this->logs,
            'allMigrations' => $allMigrations,
            'executedMigrations' => $executedMigrations,
            'outdatedMigrations' => $this->getOutdatedMigrations($executedMigrations),
            'dbInfo' => $this->buildConnectionInfo($executedMigrations, $allMigrations),
        ];
    }

    /**
     * Returns an empty view state for error fallback scenarios.
     *
     * @return array{
     *     logs: string[],
     *     allMigrations: string[],
     *     executedMigrations: string[],
     *     outdatedMigrations: string[],
     *     dbInfo: null,
     * }
     */
    public static function emptyState(): array
    {
        return [
            'logs' => [],
            'allMigrations' => [],
            'executedMigrations' => [],
            'outdatedMigrations' => [],
            'dbInfo' => null,
        ];
    }

    // ── Private methods ──────────────────────────────────────────────

    private function createDatabaseManager(string $driver): DatabaseManager
    {
        return match ($driver) {
            'mysql' => new MysqlManager(
                $_ENV['DB_HOST'],
                $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                $_ENV['DB_PORT'],
            ),
            'sqlite' => new SqliteManager($_ENV['SQLITE_DB_PATH']),
            'sqlite3' => new Sqlite3Manager($_ENV['SQLITE_DB_PATH']),
            default => throw new Exception("Unsupported DB_DRIVER: $driver"),
        };
    }

    /**
     * @return string[]
     */
    private function getAllMigrations(): array
    {
        $files = scandir($this->migrationsFolder);
        if ($files === false) {
            return [];
        }

        return array_values(array_filter(
            $files,
            fn(string $file) => pathinfo($file, PATHINFO_EXTENSION) === 'sql'
        ));
    }

    /**
     * @param string[] $executedMigrations
     * @return string[]
     */
    private function getPendingMigrations(array $executedMigrations): array
    {
        return array_values(array_diff(
            $this->getAllMigrations(),
            $executedMigrations,
        ));
    }

    /**
     * Detects executed migrations whose file was modified after
     * the last migration execution timestamp.
     *
     * @param string[] $executedMigrations
     * @return string[]
     */
    private function getOutdatedMigrations(array $executedMigrations): array
    {
        if (empty($executedMigrations)) {
            return [];
        }

        $lastExecutedAt = $this->db->getLastExecutedAt();
        if ($lastExecutedAt === null) {
            return [];
        }

        $baselineTime = strtotime($lastExecutedAt);
        $outdated = [];

        foreach ($executedMigrations as $migration) {
            $path = $this->migrationsFolder . '/' . $migration;
            if (file_exists($path) && filemtime($path) > $baselineTime) {
                $outdated[] = $migration;
            }
        }

        return $outdated;
    }

    private function runPendingMigrations(): void
    {
        $pending = $this->getPendingMigrations($this->db->getExecutedMigrations());

        foreach ($pending as $migration) {
            $this->db->runMigration($this->migrationsFolder . '/' . $migration);
            $this->db->logMigration($migration);
            $this->logs[] = "Migration executed: $migration";
        }
    }

    private function resetDatabase(): void
    {
        $this->db->resetDatabase();
        $this->db->setup();
        $this->logs[] = "Database reset successfully.";
    }

    /**
     * @param string[] $executedMigrations
     * @param string[] $allMigrations
     * @return array<string, mixed>
     */
    private function buildConnectionInfo(array $executedMigrations, array $allMigrations): array
    {
        $info = [
            'driver' => $this->driver,
            'executed' => count($executedMigrations),
            'total' => count($allMigrations),
            'last_migration' => !empty($executedMigrations) ? end($executedMigrations) : null,
        ];

        if ($this->driver === 'mysql') {
            $info['host'] = $_ENV['DB_HOST'] . ':' . $_ENV['DB_PORT'];
            $info['database'] = $_ENV['DB_NAME'];
            $info['user'] = $_ENV['DB_USER'];
        } else {
            $info['path'] = $_ENV['SQLITE_DB_PATH'];
        }

        return $info;
    }
}
