<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener\Tests\Database\SQLite;

use PDO;
use PDOException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SamuelStidham\UrlShortener\Database\SQLite\UrlRepo;
use SamuelStidham\UrlShortener\Exception\DuplicateCodeException;
use SamuelStidham\UrlShortener\Exception\DatabaseException;

/**
 * Test edge cases for the SQLite UrlRepo.
 * @package SamuelStidham\UrlShortener\Tests\Database\SQLite
 */
final class UrlRepoEdgeCasesTest extends TestCase
{
    private PDO $pdo;
    private UrlRepo $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schema = \dirname(__DIR__) . '/schema.sql';
        $this->assertFileExists($schema, 'schema.sql not found at project root');
        $sql = \file_get_contents($schema);
        $this->assertNotFalse($sql, 'Failed to read schema.sql');

        $this->pdo->exec($sql);

        $this->repo = new UrlRepo($this->pdo);
    }

    public function testIncrementClicksOnMissingCodeIsNoop(): void
    {
        $this->repo->incrementClicks('does-not-exist');

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='urls'");
        $this->assertSame(1, (int) $stmt->fetchColumn(), 'urls table should still exist');
    }

    public function testUnicodeAndLongUrlRoundTrip(): void
    {
        $code = 'uni001';
        $url  = 'https://example.com/über?emoji=😀&q=' . str_repeat('a', 500);
        $this->repo->save($code, $url);

        $this->assertSame($url, $this->repo->findByCode($code));
    }
}
