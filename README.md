# PHP URL Shortener

A minimal, test-driven URL Shortener built with PHP 8, SQLite, and PSR-4 autoloading.
This project demonstrates clean architecture, dependency inversion (via an interface-based repository), and PHPUnit testing — all without a web framework.

## Features

- Shortens URLs to unique 6-character codes (sha1 hash).
- Expands codes back to original URLs.
- Tracks creation date and click counts.
- Uses SQLite for reliable persistence.
- Organized with PSR-4 autoloading (src/ and tests/).
- Fully unit tested with PHPUnit 12.

## Project Structure

```bash
tree -I vendor/ -I reports/
.
├── bin
│   └── app.php
├── composer.json
├── composer.lock
├── database
│   └── urlshortener.db
├── phpunit.xml
├── README.md
├── schema.sql
├── src
│   ├── Contracts
│   │   └── UrlRepository.php
│   ├── Database
│   │   └── SQLite
│   │       └── UrlRepo.php
│   ├── Exception
│   │   ├── DatabaseException.php
│   │   └── DuplicateCodeException.php
│   └── Shortener.php
└── tests
    ├── Database
    │   └── SQLite
    │       └── UrlRepoTest.php
    ├── Integration
    │   └── ShortenerWithSQLiteTest.php
    ├── ShortenerErrorPropagationTest.php
    ├── ShortenerTest.php
    └── UrlRepoEdgeCasesTest.php

12 directories, 17 files
```

## Requirements

- PHP ≥ 8.1
- Composer
- SQLite 3
- (optional) PHPUnit 12 globally installed, or via Composer dev dependencies

## Database Setup

```bash
mkdir -p database
touch database/.gitkeep
```

### Create the schema file (if not already present)

```sql
CREATE TABLE IF NOT EXISTS urls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    url TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    clicks INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_urls_code ON urls(code);
```

### Initialize the database

```bash
sqlite3 database/urlshortener.db < schema.sql
```

### Verify

```bash
sqlite3 database/urlshortener.db ".tables"
```

You should see

```
urls
```

## Usage Example

```php
# example.php
<?php
require __DIR__ . '/vendor/autoload.php';

use SamuelStidham\Shortener;
use SamuelStidham\Infra\SQLiteUrlRepo;

$pdo  = new PDO('sqlite:' . __DIR__ . '/database/urlshortener.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$repo = new SQLiteUrlRepo($pdo);
$svc  = new Shortener($repo);

$code = $svc->shorten('https://example.com/hello');
echo "Short code: $code\n";

$url = $svc->expand($code);
echo "Expanded URL: $url\n";
```

Run the example:

```bash
php example.php
```

## Running Tests

Initialize test dependencies:

```bash
composer install
```

Run the suite:

```bash
composer test
```

or:

```bash
vendor/bin/phpunit
```

## Key Classes

| Class                     | Purpose                                                  |
| ------------------------- | -------------------------------------------------------- |
| `App\Shortener`           | High-level service handling URL shortening and expansion |
| `App\UrlRepo`             | Interface defining persistence behavior                  |
| `App\Infra\SQLiteUrlRepo` | SQLite implementation of `UrlRepo`                       |
| `tests\ShortenerTest`     | PHPUnit suite verifying functionality                    |

## Design Notes

- Follows PSR-4 autoloading and Single Responsibility Principle.
- Storage is abstracted behind an interface — easily swappable for MySQL, Redis, or JSON.
- No framework dependencies — pure PHP.
- SQLite chosen for simplicity, portability, and ACID safety.

## Future Enhancements

- CLI or web front-end for shortening links.
- Expiry/TTL on short URLs.
- Click analytics.
- Optional JSON fallback repository.

## CLI Application (`bin/app.php`)

This repository includes a simple CLI interface for working directly with the URL shortener database.
It allows you to shorten URLs, expand them, track click counts, and view statistics — all from your terminal.

### Features

- Automatic SQLite database creation (`/database/urlshortener.db`)
- Schema auto-migration from schema.sql
- Deterministic SHA-based short codes (6-character)
- Graceful error handling for invalid, duplicate, and database failures
- Click tracking and simple stats view

### Usage

```bash
php bin/app.php <command> [arguments]
```

| Command                 | Description                                                   | Example                                       |
| ----------------------- | ------------------------------------------------------------- | --------------------------------------------- |
| `shorten <url>`         | Shorten a URL and store it in the database                    | `php bin/app.php shorten https://example.com` |
| `expand <code>`         | Retrieve the original URL by its short code                   | `php bin/app.php expand abc123`               |
| `expand <code> --click` | Retrieve and increment the click counter                      | `php bin/app.php expand abc123 --click`       |
| `click <code>`          | Increment the click counter manually                          | `php bin/app.php click abc123`                |
| `stats <code>`          | Show statistics for a short code (URL, creation time, clicks) | `php bin/app.php stats abc123`                |
| `help`                  | Display available commands                                    | `php bin/app.php help`                        |

### Database Layout

The database is automatically created at:

```bash
/database/urlshortener.db
```

and uses the schema defined in `schema.sql`:

```sql
CREATE TABLE IF NOT EXISTS urls (
    code TEXT PRIMARY KEY,
    url TEXT NOT NULL,
    clicks INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Example Workflow

```bash
# Shorten a URL
php bin/app.php shorten https://example.com/blog/post

# Expand it back
php bin/app.php expand e3b0c4

# Simulate a click
php bin/app.php click e3b0c4

# View statistics
php bin/app.php stats e3b0c4
```

### Output Example

```bash
code:       e3b0c4
url:        https://example.com/blog/post
created_at: 2025-10-22 03:41:00
clicks:     1
```

### Notes

- `bin/app.php` will auto-create `/database/` if missing and load `schema.sql` automatically.
- All repository operations are handled through `src/Database/SQLite/UrlRepo.php`.`
- Run unit tests with `composer test` — coverage reports are generated under `/reports/coverage`.

## Author

**Samuel Stidham**
Software Engineer · Full-Stack Developer · CS Undergraduate
