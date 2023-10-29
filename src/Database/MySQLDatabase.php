<?php

namespace Nebula\Database;

use Nebula\Interfaces\Database\Database;
use Nebula\Traits\Instance\Singleton;
use PDO;
use PDOStatement;
use stdClass;

/**
 * MySQL Database
 */
class MySQLDatabase implements Database
{
    use Singleton;

    public PDOStatement $statement;
    public $query_time = 0;
    public $num_queries = 0;
    public $total_time = 0;
    public $trace_counts = [];

    /**
     * Database connection
     * @var PDO
     */
    private PDO $connection;
    /**
     * PDO options
     * @var array
     */
    private array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    /**
     * Fetch type
     * @var int
     */
    public $fetch_type = PDO::FETCH_OBJ;

    /**
     * Connect to the database
     * @param array $config Database configuration
     */
    public function connect(array $config): void
    {
        // Extract the database configuration
        extract($config);
        $dsn = "mysql:host={$host};port={$port};dbname={$name}";
        $this->connection = new PDO($dsn, $username, $password, $this->options);
    }

    /**
     * Check if the database is connected
     * @return bool
     */
    public function isConnected(): bool
    {
        return isset($this->connection);
    }

    /**
     * Is the script being run from cli?
     */
    private function isCommandLineInterface(): bool
    {
        return php_sapi_name() === "cli";
    }

    /**
     * Run a query
     * @param string $sql SQL query
     * @param array $params SQL query parameters
     * @return PDOStatement|null
     */
    public function run(string $sql, array $params = []): ?PDOStatement
    {
        $enabled = config("database.enabled");
        if (!$enabled) {
            throw new \Exception("Database is not enabled");
        }
        $this->query_time = microtime(true);
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $this->query_time = microtime(true) - $this->query_time;
        if ($this->query_time > 1) {
            $msg = sprintf(
                "DB: slow query took %s: %s",
                $this->query_time,
                $sql
            );
            error_log($msg);
            logger("debug", $msg);
        }
        $this->num_queries++;
        $this->total_time += $this->query_time;
        if (
            config("database.show_profiler") &&
            !$this->isCommandLineInterface()
        ) {
            $traces = debug_backtrace(0);
            $i = 0;
            while ($traces[$i]["class"] == "Nebula\Database\MySQLDatabase") {
                $i++;
            }
            $key =
                $traces[$i]["file"] .
                " @ " .
                $traces[$i]["line"] .
                " (" .
                $traces[$i]["function"] .
                ")";

            if (!isset($this->trace_counts[$key]["count"])) {
                $this->trace_counts[$key]["count"] = 1;
            } else {
                $this->trace_counts[$key]["count"]++;
            }

            if (!isset($this->trace_counts[$key]["time"])) {
                $this->trace_counts[$key]["time"] = $this->query_time;
            } else {
                $this->trace_counts[$key]["time"] += $this->query_time;
            }
        }
        $this->statement = $statement;
        return $statement;
    }

    /**
     * Select all rows
     * @param string $sql SQL query
     * @param array $params SQL query parameters
     * @return array|null
     */
    public function selectAll(string $sql, ...$params): ?array
    {
        $result = $this->run($sql, $params);
        return $result?->fetchAll($this->fetch_type);
    }

    /**
     * Select a single row
     * @param string $sql SQL query
     * @param array $params SQL query parameters
     * @return stdClass|null
     */
    public function select(string $sql, ...$params): mixed
    {
        $result = $this->run($sql, $params);
        return $result?->fetch($this->fetch_type);
    }

    /**
     * Run a query
     * @param string $sql SQL query
     * @pearam array $params SQL query parameters
     */
    public function query(string $sql, ...$params): ?PDOStatement
    {
        $statement = $this->run($sql, $params);
        return $statement;
    }

    /**
     * Begin SQL transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit SQL transaction
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Roll back SQL transaction
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    public function lastInsertId(): string|false
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Call PDO methods
     * @param string $method PDO method
     * @param array $args PDO method arguments
     */
    public function __call($method, $args)
    {
        return $this->connection->$method(...$args);
    }
}
