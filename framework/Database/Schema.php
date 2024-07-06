<?php

namespace Nebula\Framework\Database;

use Closure;
use Exception;

class Schema
{
    public static function create(string $table_name, Closure $callback)
    {
        $blueprint = new Blueprint;
        $callback($blueprint);
        return sprintf(
            "CREATE TABLE IF NOT EXISTS %s (%s)",
            $table_name,
            $blueprint->getDefinitions()
        );
    }

    public static function drop(string $table_name)
    {
        return sprintf("DROP TABLE IF EXISTS %s", $table_name);
    }

    public static function file(string $path)
    {
        $migration_path = config("path.migrations") . $path;
        if (!file_exists($migration_path)) {
            throw new Exception("migration file doesn't exist");
        }
        return file_get_contents($migration_path);
    }
}
