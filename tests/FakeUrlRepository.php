<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener\Tests;

use SamuelStidham\UrlShortener\Contracts\UrlRepository;

/**
 * Simple in-memory fake implementing UrlRepository.
 * Stores saved mappings in $saved for assertions.
 */
final class FakeUrlRepository implements UrlRepository
{
    /** @var array<string,string> */
    public array $saved = [];

    public function save(string $code, string $url): void
    {
        $this->saved[$code] = $url;
    }

    public function findByCode(string $code): ?string
    {
        return $this->saved[$code] ?? null;
    }

    public function incrementClicks(string $code): void
    {
        // No-op for test purposes
    }
}
