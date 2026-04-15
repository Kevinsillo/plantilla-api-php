<?php

declare(strict_types=1);

namespace Backend;

use Dotenv\Dotenv;
use Exception;
use RuntimeException;

class Envs
{
    /** @var array<array{var: string, type: string}> */
    private const REQUIRED_VARIABLES = [
        ["var" => 'DEV_MODE', "type" => "boolean"],
        ["var" => 'CORS_ORIGIN', "type" => "string"],
        ["var" => 'IS_COOKIE_SECURE', "type" => "boolean"],
        ["var" => 'DB_DRIVER', "type" => 'string']
    ];
    private Dotenv $dotenv;

    /**
     * @param string $root_dir Root directory where the .env file is located.
     * @throws RuntimeException If the .env file cannot be loaded or has invalid values.
     */
    public function __construct(string $root_dir)
    {
        try {
            $this->dotenv = Dotenv::createImmutable($root_dir);
            $this->dotenv->load();
            $this->checkRequiredEnvVars();
            $this->filterAppropriateTypes();
        } catch (Exception $e) {
            throw new RuntimeException('Environment error: ' . $e->getMessage());
        }
    }

    /**
     * Validate and convert environment variables to their
     * appropriate types.
     * @return void
     */
    private function checkRequiredEnvVars(): void
    {
        $missings = [];
        foreach (self::REQUIRED_VARIABLES as ['var' => $var, 'type' => $type]) {
            if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
                $missings[] = $var;
            }
        }

        if (!empty($missings)) {
            throw new RuntimeException('Missing or empty required environment variables: ' . implode(', ', $missings));
        }
    }

    /**
     * Convert environment variables to their appropriate types.
     * @return void
     * @throws RuntimeException If any variable has an invalid value.
     */
    private function filterAppropriateTypes(): void
    {
        foreach (self::REQUIRED_VARIABLES as ['var' => $var, 'type' => $type]) {
            switch ($type) {
                case 'boolean':
                    $_ENV[$var] = $this->validateBoolean($_ENV[$var], $var);
                    break;
                case 'array':
                    $_ENV[$var] = $this->validateArray($_ENV[$var], $var);
                    break;
                case 'string':
                    $_ENV[$var] = trim($_ENV[$var]);
                    break;
                default:
                    throw new RuntimeException("Unsupported type '$type' for variable '$var'.");
            }
        }
    }

    /**
     * @throws RuntimeException If the value is not a valid boolean string.
     */
    private function validateBoolean(string $value, string $var): bool
    {
        $allowed = ['true', 'false', '1', '0'];
        if (!in_array(strtolower($value), $allowed, true)) {
            throw new RuntimeException("Variable '$var' must be a boolean (true/false, 1/0). Got: '$value'");
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return string[]
     * @throws RuntimeException If the value is not a valid JSON array of strings.
     */
    private function validateArray(string $value, string $var): array
    {
        $items = json_decode($value, true);
        if (!is_array($items) || array_values($items) !== $items) {
            throw new RuntimeException("Variable '$var' must be a JSON array (e.g. [\"a\", \"b\"]). Got: '$value'");
        }
        foreach ($items as $item) {
            if (!is_string($item) || trim($item) === '') {
                throw new RuntimeException("Variable '$var' must contain only non-empty strings. Got: '$value'");
            }
        }
        return $items;
    }
}
