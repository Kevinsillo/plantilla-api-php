<?php

declare(strict_types=1);

namespace Backend;

use Dotenv\Dotenv;
use Exception;
use RuntimeException;

class Envs
{
    /**
     * @param string $root_dir Root directory where the .env file is located.
     * @throws RuntimeException If the .env file cannot be loaded.
     */
    /** @var string[] Boolean environment variable names. */
    private const BOOLEAN_VARS = [
        'DEV_MODE',
        'COOKIE_SECURE',
    ];

    /**
     * @param string $root_dir Root directory where the .env file is located.
     * @throws RuntimeException If the .env file cannot be loaded or has invalid values.
     */
    public function __construct(string $root_dir)
    {
        try {
            $dotenv = Dotenv::createImmutable($root_dir);
            $dotenv->load();
            $dotenv->ifPresent(self::BOOLEAN_VARS)->isBoolean();
        } catch (Exception $e) {
            throw new RuntimeException('Environment error: ' . $e->getMessage());
        }
    }

    /**
     * Check that all required environment variables are defined and not empty.
     *
     * @param string[] $required List of required environment variable names.
     * @return void
     * @throws RuntimeException If any variable is missing or empty.
     */
    public static function checkRequiredEnvVars(): void
    {
        $required = [
            'DEV_MODE',
            'COOKIE_SECURE',
            'CORS_ORIGIN',
        ];

        $missing = [];

        foreach ($required as $var) {
            if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            throw new RuntimeException(
                'Missing required environment variables: ' . implode(', ', $missing)
            );
        }
    }
}
