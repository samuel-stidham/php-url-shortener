<?php

declare(strict_types=1);

namespace SamuelStidham\UrlShortener\Database\SQLite;

use PDO;
use PDOException;
use SamuelStidham\UrlShortener\Contracts\UrlRepository;
use SamuelStidham\UrlShortener\Exception\DatabaseException;
use SamuelStidham\UrlShortener\Exception\DuplicateCodeException;

/**
 * URL Repository
 * @package SamuelStidham\UrlShortener\Database\SQLite
 */
final class UrlRepo implements UrlRepository
{
    /**
     * @param PDO $pdo
     * @return void
     */
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Save the shortened code and URL to the database.
     * @param string $code
     * @param string $url
     * @return void
     * @throws DuplicateCodeException
     * @throws DatabaseException
     */
    public function save(string $code, string $url): void
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO urls(code, url) VALUES(:c, :u)');
            $stmt->execute([':c' => $code, ':u' => $url]);
        } catch (PDOException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw new DuplicateCodeException('Short code already exists', previous: $e);
            }
            throw new DatabaseException('Database error while saving URL', previous: $e);
        }
    }

    /**
     * Find URL by shortened code.
     * @param string $code
     * @return null|string
     * @throws PDOException
     */
    public function findByCode(string $code): ?string
    {
        $stmt = $this->pdo->prepare('SELECT url FROM urls WHERE code = :c LIMIT 1');
        $stmt->execute([':c' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['url'] ?? null;
    }

    /**
     * Increment URL clicks.
     * @param string $code
     * @return void
     * @throws PDOException
     */
    public function incrementClicks(string $code): void
    {
        $this->pdo->prepare('UPDATE urls SET clicks = clicks + 1 WHERE code = :c')
                  ->execute([':c' => $code]);
    }

    /**
     * check to see if the returned error is a unique constratint violation.
     * @param PDOException $e
     * @return bool
     */
    private function isUniqueConstraintViolation(PDOException $e): bool
    {
        $info      = $e->errorInfo ?? [];
        $sqlState  = $info[0] ?? $e->getCode();  // '23000' for integrity violations
        $driverErr = $info[1] ?? null;           // 19 for SQLITE_CONSTRAINT
        $message   = strtolower($info[2] ?? $e->getMessage());

        return $sqlState === '23000'
            || $driverErr === 19
            || str_contains($message, 'unique constraint failed');
    }
}
