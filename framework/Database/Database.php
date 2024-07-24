<?php

namespace Nebula\Framework\Database;

use Lunar\Connection\MySQL;
use Lunar\Connection\SQLite;
use Lunar\Interface\DB;
use Exception;
use Nebula\Framework\Traits\Singleton;

class Database
{
    use Singleton;

    private ?DB $db = null;

    public function db(): DB
    {
        return $this->db;
    }

    public function init(array $config)
    {
        if (is_null($this->db)) {
            if (!$config["enabled"]) {
                return null;
            }
            $this->db = match ($config["type"]) {
                "mysql" => new MySQL(
                    $config["dbname"],
                    $config["username"],
                    $config["password"],
                    $config["host"],
                    $config["port"],
                    $config["charset"],
                    $config["options"]
                ),
                "sqlite" => new SQLite($config["path"], $config["options"]),
                default => throw new Exception("unknown database driver"),
            };
        }
        return $this;
    }
}
