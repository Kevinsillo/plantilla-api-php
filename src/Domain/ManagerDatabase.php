<?php

declare(strict_types=1);

namespace Backend\Domain;

interface ManagerDatabase
{
    /**
     * Creates the `migrations` tracking table if it doesn't exist.
     */
    public function setup(): void;

    /**
     * Fetches the list of executed migrations.
     *
     * @return array List of migration names.
     */
    public function getExecutedMigrations(): array;

    /**
     * Logs a migration as executed in the database.
     *
     * @param string $name Name of the migration file.
     */
    public function logMigration(string $name): void;

    /**
     * Executes a migration SQL file.
     *
     * @param string $filePath Path to the migration file.
     */
    public function runMigration(string $filePath): void;

    /**
     * Resets the database (drops all tables or recreates the database).
     */
    public function resetDatabase(): void;
}
