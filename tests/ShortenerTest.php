<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SamuelStidham\UrlShortener\Shortener;

/**
 * Test the URL Shortener
 * @package SamuelStidham\UrlShortener\Tests
 */
final class ShortenerTest extends TestCase
{
    public function testShortenReturnsDeterministicCode(): void
    {
        $repo = new FakeUrlRepository();
        $svc  = new Shortener($repo);

        $url  = 'https://example.com/path?q=1';
        $a    = $svc->shorten($url);
        $b    = $svc->shorten($url);

        $this->assertSame($a, $b);
        $this->assertSame(6, strlen($a));
    }

    public function testShortenSavesMappingToRepository(): void
    {
        $repo = new FakeUrlRepository();
        $svc  = new Shortener($repo);

        $url  = 'https://example.org/hello';
        $code = $svc->shorten($url);

        $this->assertArrayHasKey($code, $repo->saved);
        $this->assertSame($url, $repo->saved[$code]);
    }

    public function testInvalidUrlThrows(): void
    {
        $repo = new FakeUrlRepository();
        $svc  = new Shortener($repo);

        $this->expectException(InvalidArgumentException::class);
        $svc->shorten('not-a-url');
    }
}
