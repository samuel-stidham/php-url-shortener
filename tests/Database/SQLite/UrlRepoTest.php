<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener\Tests\Database\SQLite;

use PDO;
use PDOException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SamuelStidham\UrlShortener\Database\SQLite\UrlRepo;
use SamuelStidham\UrlShortener\Exception\DatabaseException;
use SamuelStidham\UrlShortener\Exception\DuplicateCodeException;

/**
 * URL Repository Test
 * @package SamuelStidham\UrlShortener\Tests\Database\SQLite
 */
final class UrlRepoTest extends TestCase
{
    private PDO $pdo;
    private UrlRepo $repo;

    /**
     * Setup in-memory SQLite and UrlRepo before each test.
     * @return void
     * @throws PDOException
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schema = \dirname(__DIR__, 3) . '/schema.sql';
        $this->assertFileExists($schema, 'schema.sql not found at project root');

        $sql = \file_get_contents($schema);
        $this->assertNotFalse($sql, 'Failed to read schema.sql');

        $this->pdo->exec($sql);

        $this->repo = new UrlRepo($this->pdo);
    }

    /**
     * Test saving and finding by code.
     * @return void
     * @throws DuplicateCodeException
     * @throws DatabaseException
     */
    public function testSaveAndFindByCode(): void
    {
        $this->repo->save('abc123', 'https://example.com');
        $found = $this->repo->findByCode('abc123');

        $this->assertSame('https://example.com', $found);
        $this->assertNull($this->repo->findByCode('nope'));
    }

    /**
     * Test for duplicate code exception.
     * @return void
     * @throws DuplicateCodeException
     * @throws DatabaseException
     */
    public function testDuplicateCodeThrowsDuplicateCodeException(): void
    {
        $this->repo->save('dup001', 'https://a.test');
        $this->expectException(DuplicateCodeException::class);
        $this->repo->save('dup001', 'https://b.test');
    }

    /**
     * Test incremental clicks.
     * @return void
     * @throws DuplicateCodeException
     * @throws DatabaseException
     * @throws PDOException
     * @throws ExpectationFailedException
     */
    public function testIncrementClicks(): void
    {
        $this->repo->save('clk001', 'https://click.me');

        // 2 clicks
        $this->repo->incrementClicks('clk001');
        $this->repo->incrementClicks('clk001');

        $stmt = $this->pdo->prepare('SELECT clicks FROM urls WHERE code = :c');
        $stmt->execute([':c' => 'clk001']);
        $this->assertSame(2, (int) $stmt->fetchColumn());
    }

    /**
     * Test other database exceptions.
     * @return void
     * @throws DuplicateCodeException
     * @throws DatabaseException
     */
    public function testDatabaseExceptionOnOtherErrors(): void
    {
        $this->pdo->exec('DROP TABLE urls');
        $this->expectException(DatabaseException::class);
        $this->repo->save('bad001', 'https://nowhere');
    }
}
