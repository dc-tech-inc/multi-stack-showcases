<?php

declare(strict_types=1);

namespace DcTech\RealEstate;

use PDO;

final class Database
{
    public static function fromEnv(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'realestate';
        $user = getenv('DB_USER') ?: 'realestate';
        $pass = getenv('DB_PASSWORD') ?: '';

        return new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /** Drops and recreates the schema, then loads the fictional seed data (used by tests). */
    public static function reset(PDO $pdo, string $dbDir): void
    {
        foreach (['schema.sql', 'seed.sql'] as $file) {
            $sql = file_get_contents($dbDir . '/' . $file);
            if ($sql === false) {
                throw new \RuntimeException("Cannot read {$file}");
            }
            // Statements end with ";" at end of line; no seed value contains that sequence.
            foreach (preg_split('/;\s*\n/', $sql) ?: [] as $statement) {
                $statement = trim((string) preg_replace('/^--.*$/m', '', $statement));
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }
        }
    }
}
