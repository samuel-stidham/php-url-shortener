<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener;

use InvalidArgumentException;
use SamuelStidham\UrlShortener\Contracts\UrlRepository;

/**
 * URL Shortener Service
 * @package UrlShortener
 */
final class Shortener
{
    /**
     *
     * @param UrlRepository $repository
     * @return void
     */
    public function __construct(private UrlRepository $repository)
    {
        // Intentionally left blank
    }

    /**
     *
     * @param string $url
     * @return string
     * @throws InvalidArgumentException
     */
    public function shorten(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL.');
        }

        // Simple hash-based code generation (not collision-resistant)
        $code = substr(sha1($url), 0, 6);
        // Save the mapping to the repository
        $this->repository->save($code, $url);

        return $code;
    }
}
