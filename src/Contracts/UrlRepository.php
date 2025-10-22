<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener\Contracts;

interface UrlRepository
{
    public function save(string $code, string $url): void;
    public function findByCode(string $code): ?string;
    public function incrementClicks(string $code): void;
}
