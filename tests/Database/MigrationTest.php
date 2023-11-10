<?php declare(strict_types=1);

namespace Nebula\Tests\Database;

use Nebula\Interfaces\Database\Migration;
use PHPUnit\Framework\TestCase;

final class MigrationTest extends TestCase
{
    protected Migration $migration;

    protected function setUp(): void
    {
        $migrations_path = config("paths.migrations");
        $this->migration = require $migrations_path .
            "1688141260_table_users.php";
    }

    public function test_migration_up_query(): void
    {
        $migration = $this->migration->up();
        $query = "CREATE TABLE IF NOT EXISTS users (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, uuid CHAR(36) NOT NULL DEFAULT (UUID()), user_type BIGINT UNSIGNED NOT NULL DEFAULT 2, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password BINARY(96) NOT NULL, two_fa_secret CHAR(16), remember_token CHAR(64), reset_token CHAR(64), reset_expires_at BIGINT UNSIGNED, failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, lock_expires_at BIGINT UNSIGNED, created_at DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY (email), PRIMARY KEY (id), FOREIGN KEY (user_type) REFERENCES user_types(id))";
        $this->assertSame($query, $migration);
    }

    public function test_migration_down_query(): void
    {
        $migration = $this->migration->down();
        $query = "DROP TABLE IF EXISTS users";
        $this->assertSame($query, $migration);
    }
}
