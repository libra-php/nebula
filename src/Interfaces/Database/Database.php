<?php
namespace Nebula\Interfaces\Database;

use PDOStatement;

interface Database
{
    public function connect(array $config): void;
    public function run(string $sql, array $params = []): ?PDOStatement;
    public function select(string $sql, ...$params): mixed;
    public function selectAll(string $sql, ...$params): ?array;
    public function query(string $sql, ...$params): ?PDOStatement;
    public function beginTransaction(): bool;
    public function rollBack(): bool;
    public function commit(): bool;
    public function lastInsertId(): string|false;
}
