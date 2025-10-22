<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use SamuelStidham\UrlShortener\Database\SQLite\UrlRepo;
use SamuelStidham\UrlShortener\Shortener;

final class ShortenerWithSQLiteTest extends TestCase
{
    public function testEndToEndShortenAndExpand(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schema = \dirname(__DIR__, 2) . '/schema.sql';
        $pdo->exec(\file_get_contents($schema));

        $svc  = new Shortener(new UrlRepo($pdo));
        $code = $svc->shorten('https://example.net/hello');
        $this->assertSame('https://example.net/hello', (new UrlRepo($pdo))->findByCode($code));
    }
}
