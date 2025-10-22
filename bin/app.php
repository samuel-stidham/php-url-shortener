#!/usr/bin/env php
<?php

declare(strict_types=1);

use SamuelStidham\UrlShortener\Shortener;
use SamuelStidham\UrlShortener\Database\SQLite\UrlRepo;
use SamuelStidham\UrlShortener\Exception\DuplicateCodeException;
use SamuelStidham\UrlShortener\Exception\DatabaseException;

require __DIR__ . '/../vendor/autoload.php';

$projectRoot = __DIR__ . '/..';
$dbDir       = $projectRoot . '/database';
$dbPath      = $dbDir . '/urlshortener.db';
$schemaPath  = $projectRoot . '/schema.sql';

// Ensure database directory exists
if (!is_dir($dbDir)) {
    if (!mkdir($dbDir, 0777, true) && !is_dir($dbDir)) {
        fwrite(STDERR, "Error: Failed to create directory $dbDir\n");
        exit(1);
    }
}

// Initialize PDO and ensure schema
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ensureSchema($pdo, $schemaPath);
} catch (Throwable $e) {
    fwrite(STDERR, "Database init error: {$e->getMessage()}\n");
    exit(1);
}

$repo = new UrlRepo($pdo);
$svc  = new Shortener($repo);

$argv0    = $argv[0] ?? 'app.php';
$command  = $argv[1] ?? 'help';
$arg1     = $argv[2] ?? null;
$arg2     = $argv[3] ?? null;

switch ($command) {
    case 'shorten':
        if (!$arg1) {
            usage($argv0, "Missing URL.\n");
        }
        try {
            $code = $svc->shorten($arg1);
            echo "Short code: {$code}\n";
        } catch (InvalidArgumentException $e) {
            fwrite(STDERR, "Invalid URL: {$e->getMessage()}\n");
            exit(2);
        } catch (DuplicateCodeException $e) {
            echo "Short code already exists for this URL.\n";
            echo "Code: " . substr(sha1($arg1), 0, 6) . "\n";
            exit(0);
        } catch (DatabaseException $e) {
            fwrite(STDERR, "DB error: {$e->getMessage()}\n");
            exit(3);
        }
        break;

    case 'expand':
        if (!$arg1) {
            usage($argv0, "Missing short code.\n");
        }
        $url = $repo->findByCode($arg1);
        if ($url === null) {
            fwrite(STDERR, "Not found: {$arg1}\n");
            exit(4);
        }
        echo $url . "\n";
        if ($arg2 === '--click') {
            $repo->incrementClicks($arg1);
        }
        break;

    case 'click':
        if (!$arg1) {
            usage($argv0, "Missing short code.\n");
        }
        $repo->incrementClicks($arg1);
        echo "OK\n";
        break;

    case 'stats':
        if (!$arg1) {
            usage($argv0, "Missing short code.\n");
        }
        $row = fetchStats($pdo, $arg1);
        if (!$row) {
            fwrite(STDERR, "Not found: {$arg1}\n");
            exit(4);
        }
        echo "code:       {$arg1}\n";
        echo "url:        {$row['url']}\n";
        echo "created_at: {$row['created_at']}\n";
        echo "clicks:     {$row['clicks']}\n";
        break;

    case 'help':
    default:
        usage($argv0);
        break;
}

/**
 * Ensure the database schema exists, creating it if necessary.
 * @param PDO $pdo
 * @param string $schemaPath
 * @return void
 * @throws RuntimeException
 */
function ensureSchema(PDO $pdo, string $schemaPath): void
{
    // If DB file didn’t exist, it’s already created by PDO on connect.
    // Check if the urls table exists:
    $exists = (int)$pdo->query(
        "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='urls'"
    )->fetchColumn();

    if ($exists === 0) {
        if (!is_file($schemaPath)) {
            throw new RuntimeException("schema.sql not found at: {$schemaPath}");
        }
        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            throw new RuntimeException("Failed to read schema at: {$schemaPath}");
        }
        $pdo->exec($sql);
    }
}

/**
 * Fetch stats row for a code (url, created_at, clicks).
 * @param PDO $pdo
 * @param string $code
 * @return null|array
 */
function fetchStats(PDO $pdo, string $code): ?array
{
    $stmt = $pdo->prepare('SELECT url, created_at, clicks FROM urls WHERE code = :c LIMIT 1');
    $stmt->execute([':c' => $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Display usage information
 * @param string $argv0
 * @param string $msg
 * @return void
 */
function usage(string $argv0, string $msg = ''): void
{
    if ($msg !== '') {
        fwrite(STDERR, $msg);
    }
    $cmd = basename($argv0);
    echo <<<TXT
Usage:
  php {$cmd} shorten <url>            Shorten a URL and print the short code
  php {$cmd} expand  <code> [--click] Print original URL (optionally increment click count)
  php {$cmd} click   <code>           Increment click count for a code
  php {$cmd} stats   <code>           Show url, created_at, and clicks for a code
  php {$cmd} help                      Show this help

Examples:
  php {$cmd} shorten https://example.com/hello
  php {$cmd} expand abc123
  php {$cmd} expand abc123 --click
  php {$cmd} click abc123
  php {$cmd} stats abc123

TXT;
    exit($msg === '' ? 0 : 1);
}
