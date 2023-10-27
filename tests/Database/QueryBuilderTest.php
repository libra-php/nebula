<?php declare(strict_types=1);

namespace Nebula\Tests\Database;

use PHPUnit\Framework\TestCase;
use Nebula\Database\QueryBuilder;

final class QueryBuilderTest extends TestCase
{
    public function test_where_params(): void
    {
        $qb = QueryBuilder::select("users")
        ->columns(["id", "email", "name"])
        ->where(["id IS NOT NULL"]);
        $this->assertSame(
            "SELECT `id`, `email`, `name` FROM users WHERE (id IS NOT NULL)",
            $qb->build()
        );
        $qb = QueryBuilder::select("users")
        ->columns(["id", "email", "name"])
        ->where(["id", 3]);
        $this->assertSame(
            "SELECT `id`, `email`, `name` FROM users WHERE (id = ?)",
            $qb->build()
        );
        $qb = QueryBuilder::select("users")
        ->columns(["id", "email", "name"])
        ->where(["id", ">", 3]);
        $this->assertSame(
            "SELECT `id`, `email`, `name` FROM users WHERE (id > ?)",
            $qb->build()
        );
    }

    public function test_where_operators(): void
    {
        $qb = QueryBuilder::select("users")
        ->columns(["id", "email", "name"])
        ->where(["id", ">", 3], ["email", "IS NOT", "NULL"], ["name", "!=", "bacon"]);
        $this->assertSame(
            "SELECT `id`, `email`, `name` FROM users WHERE (id > ?) AND (email IS NOT ?) AND (name != ?)",
            $qb->build()
        );
    }

    public function test_select_query(): void
    {
        $qb = QueryBuilder::select("users")
            ->columns(["id", "email", "name"])
            ->where(["id", "=", 1], ["name", "=", "test"])
            ->groupBy(["id", "name"])
            ->having(["id", "=", 2], ["name", "=", "blue"])
            ->orderBy(["id" => "ASC", "name" => "DESC"])
            ->limit(1)
            ->offset(2);
        $this->assertSame($qb->values(), [1, "test", 2, "blue"]);
        $this->assertSame(
            "SELECT `id`, `email`, `name` FROM users WHERE (id = ?) AND (name = ?) GROUP BY id, name HAVING (id = ?) AND (name = ?) ORDER BY id ASC, name DESC LIMIT 2, 1",
            $qb->build()
        );
    }

    public function test_insert_query(): void
    {
        $qb = QueryBuilder::insert("users")->columns([
            "name" => "test",
            "email" => "test@test.com",
        ]);
        $this->assertSame($qb->values(), ["test", "test@test.com"]);
        $this->assertSame(
            "INSERT INTO users SET name = ?, email = ?",
            $qb->build()
        );
    }

    public function test_update_query(): void
    {
        $qb = QueryBuilder::update("users")
            ->columns(["name" => "test", "email" => "test@test.com"])
            ->where(["id", "=", 1]);
        $this->assertSame($qb->values(), ["test", "test@test.com", 1]);
        $this->assertSame(
            "UPDATE users SET name = ?, email = ? WHERE (id = ?)",
            $qb->build()
        );
    }

    public function test_delete_query(): void
    {
        $qb = QueryBuilder::delete("users")->where(["id", "=", 1]);
        $this->assertSame($qb->values(), [1]);
        $this->assertSame("DELETE FROM users WHERE (id = ?)", $qb->build());
    }
}
