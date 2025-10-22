<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener\Tests;

use PHPUnit\Framework\TestCase;
use SamuelStidham\UrlShortener\Contracts\UrlRepository;
use SamuelStidham\UrlShortener\Exception\DatabaseException;
use SamuelStidham\UrlShortener\Exception\DuplicateCodeException;
use SamuelStidham\UrlShortener\Shortener;

/**
 * Test error propagation in the Shortener service.
 * @package SamuelStidham\UrlShortener\Tests
 */
final class ShortenerErrorPropagationTest extends TestCase
{
    public function testShortenBubblesDuplicateCodeException(): void
    {
        $repo = new class () implements UrlRepository {
            public function save(string $code, string $url): void
            {
                throw new DuplicateCodeException('dup');
            }
            public function findByCode(string $code): ?string
            {
                return null;
            }
            public function incrementClicks(string $code): void
            {
            }
        };

        $svc = new Shortener($repo);
        $this->expectException(DuplicateCodeException::class);
        $svc->shorten('https://example.com');
    }

    public function testShortenBubblesDatabaseException(): void
    {
        $repo = new class () implements UrlRepository {
            public function save(string $code, string $url): void
            {
                throw new DatabaseException('db');
            }
            public function findByCode(string $code): ?string
            {
                return null;
            }
            public function incrementClicks(string $code): void
            {
            }
        };

        $svc = new Shortener($repo);
        $this->expectException(DatabaseException::class);
        $svc->shorten('https://example.org');
    }
}
