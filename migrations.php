<?php

declare(strict_types=1);

use Dotenv\Dotenv;

const ROOT_DIR = __DIR__;
require(ROOT_DIR . '/vendor/autoload.php');

# Load environment variables
$dotenv = Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

if (!isset($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['MIGRATIONS_FOLDER'])) {
    throw new Exception("Please set the required environment variables.");
}

/**
 * Handles database migrations.
 */
class DatabaseManager
{
    private PDO $pdo;

    /**
     * Initializes the database connection.
     *
     * @param string $host Database host.
     * @param string $dbName Database name.
     * @param string $user Database user.
     * @param string $pass Database password.
     * @throws PDOException
     */
    public function __construct(string $host, string $dbName, string $user, string $pass)
    {
        $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }

    /**
     * Creates the `migrations` table if it doesn't exist.
     */
    public function setup(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(255) UNIQUE)");
    }

    /**
     * Fetches the list of executed migrations.
     *
     * @return array List of migration names.
     */
    public function getExecutedMigrations(): array
    {
        return $this->pdo
            ->query("SELECT name FROM migrations")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Logs a migration in the database.
     *
     * @param string $name Name of the migration file.
     */
    public function logMigration(string $name): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);
    }

    /**
     * Executes a migration SQL file.
     *
     * @param string $filePath Path to the migration file.
     * @throws PDOException
     */
    public function runMigration(string $filePath): void
    {
        $query = file_get_contents($filePath);
        $this->pdo->exec($query);
    }

    /**
     * Drops the entire database.
     */
    public function resetDatabase(): void
    {
        $this->pdo->exec("DROP DATABASE IF EXISTS " . $_ENV['DB_NAME']);
        $this->pdo->exec("CREATE DATABASE " . $_ENV['DB_NAME']);
        $this->pdo->exec("USE " . $_ENV['DB_NAME']);
    }
}

/**
 * Fetches the pending migrations.
 *
 * @param array $executedMigrations List of already executed migrations.
 * @param string $migrationsFolder Path to the migrations folder.
 * @return array List of pending migration files.
 */
function getPendingMigrations(array $executedMigrations, string $migrationsFolder): array
{
    $allMigrations = array_filter(
        scandir($migrationsFolder),
        fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'sql'
    );

    return array_diff($allMigrations, $executedMigrations);
}

// Main Logic
$logs = [];

try {
    $db = new DatabaseManager($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $db->setup();

    $executedMigrations = $db->getExecutedMigrations();
    $pendingMigrations = getPendingMigrations($executedMigrations, $_ENV['MIGRATIONS_FOLDER']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['run_migrations'])) {
            foreach ($pendingMigrations as $migration) {
                $db->runMigration($_ENV['MIGRATIONS_FOLDER'] . '/' . $migration);
                $db->logMigration($migration);
                $logs[] = "Migration executed: $migration";
            }
        }

        if (isset($_POST['reset_migrations'])) {
            $db->resetDatabase();
            $logs[] = "Database reset successfully.";
        }
    }
} catch (PDOException $e) {
    $logs[] = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1a202c;
        }

        .dark-mode {
            background-color: #1a202c;
            color: #f7fafc;
        }
    </style>
</head>

<body class="p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Migration Manager</h1>
        <form method="POST" class="space-y-4">
            <div class="bg-white shadow p-6 rounded">
                <h2 class="text-xl font-semibold mb-2">Actions</h2>
                <div class="flex space-x-4">
                    <button type="submit" name="run_migrations"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Run Migrations
                    </button>
                    <button type="submit" name="reset_migrations"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                        Reset Database
                    </button>
                </div>
            </div>
            <div class="bg-white shadow p-6 rounded">
                <h2 class="text-xl font-semibold mb-2">Pending Migrations</h2>
                <ul>
                    <?php foreach ($pendingMigrations as $migration): ?>
                        <li><?= htmlspecialchars($migration) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="bg-white shadow p-6 rounded">
                <h2 class="text-xl font-semibold mb-2">Logs</h2>
                <pre class="bg-gray-100 p-4 rounded"><?= implode("\n", $logs) ?></pre>
            </div>
        </form>
    </div>
</body>

</html>