<?php

namespace Nebula\Framework\Database;

use Closure;
use Exception;

class Schema
{
    public static function create(string $table_name, Closure $callback): string
    {
        $blueprint = new Blueprint;
        $callback($blueprint);
        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS %s (%s)",
            $table_name,
            $blueprint->getDefinitions()
        );
        return $sql;
    }

    public static function drop(string $table_name): string
    {
        return sprintf("DROP TABLE IF EXISTS %s", $table_name);
    }

    public static function file(string $path): string
    {
        $migration_path = config("path.migrations") . $path;
        if (!file_exists($migration_path)) {
            throw new Exception(" Migration file doesn't exist: $migration_path");
        }
        $sql = file_get_contents($migration_path);
        return $sql;
    }

    private static function surround(array $data, string $char = "'"): string
    {
        return "$char" . implode("$char, $char", $data) . "$char";
    }

    public static function insert(string $table_name, array $columns, ...$data): string
    {
        $columns = self::surround($columns, "`");
        $sql = "INSERT INTO $table_name ($columns) VALUES ";
        $insert = [];
        foreach ($data as $values) {
            $values = array_map(fn($value) => !is_null($value) ? "'$value'" : "NULL", $values);
            $values = implode(", ", $values);
            $insert[] = "($values)";
        }
        $sql .= implode(", ", $insert);
        return $sql;
    }
}
