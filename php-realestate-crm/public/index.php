<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DcTech\RealEstate\App;
use DcTech\RealEstate\Database;

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$body = [];
if ($raw !== false && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['error' => 'body must be a JSON object']);
        exit;
    }
    /** @var array<string, mixed> $decoded */
    $body = $decoded;
}

/** @var array<string, string> $query */
$query = array_filter($_GET, 'is_string');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

try {
    [$status, $payload] = (new App(Database::fromEnv()))->handle(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        is_string($path) ? $path : '/',
        $query,
        $body
    );
} catch (Throwable $e) {
    error_log($e->getMessage());
    $status = 500;
    $payload = ['error' => 'internal error'];
}

http_response_code($status);
echo json_encode($payload, JSON_UNESCAPED_SLASHES);
