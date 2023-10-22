<?php

namespace Nebula\Database;

use Closure;
use Nebula\Interfaces\Database\QueryBuilder as QueryBuilderInterface;

/**
 * Class QueryBuilder
 * Creates a predictable query string for the database.
 */
class QueryBuilder implements QueryBuilderInterface
{
    private $mode = "select";
    private string $table_name = "";
    private array $values = [];
    private array $columns = [];
    private array $where = [];
    private array $having = [];
    private array $group_by = [];
    private array $order_by = [];
    private ?int $limit = null;
    private ?int $offset = null;

    /**
     * Return query values (for prepared statements)
     * @return array<int,mixed>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * @param array<int,mixed> $data
     * @param Closure(): void $fn
     * @param string $separator
     */
    private function mapToString(
        array $data,
        \Closure $fn,
        string $separator = ", "
    ): string {
        $columns = array_map($fn, $data);
        return implode($separator, $columns);
    }

    /**
     * Construct a select sql query
     * @param Model $model
     */
    public static function select(string $table_name): self
    {
        $qb = new QueryBuilder();
        $qb->table_name = $table_name;
        $qb->mode = "select";
        return $qb;
    }

    /**
     * Construct an insert sql query
     * @param Model $model
     */
    public static function insert(string $table_name): self
    {
        $qb = new QueryBuilder();
        $qb->table_name = $table_name;
        $qb->mode = "insert";
        return $qb;
    }

    /**
     * Construct an insert sql query
     * @param Model $model
     */
    public static function insertIgnore(string $table_name): self
    {
        $qb = new QueryBuilder();
        $qb->table_name = $table_name;
        $qb->mode = "insert_ignore";
        return $qb;
    }

    /**
     * Construct an update sql query
     * @param Model $model
     */
    public static function update(string $table_name): self
    {
        $qb = new QueryBuilder();
        $qb->table_name = $table_name;
        $qb->mode = "update";
        return $qb;
    }

    /**
     * Construct a delete sql query
     * @param Model $model
     */
    public static function delete(string $table_name): self
    {
        $qb = new QueryBuilder();
        $qb->table_name = $table_name;
        $qb->mode = "delete";
        return $qb;
    }

    /**
     * Add columns to the query
     * @param array<int,mixed> $columns
     */
    public function columns(array $columns): self
    {
        if (
            $this->mode === "insert" ||
            $this->mode === "insert_ignore" ||
            $this->mode === "update"
        ) {
            $this->values = array_merge($this->values, array_values($columns));
        }
        $this->columns = $columns;
        return $this;
    }

    /**
     * Add a where clause to the query
     */
    public function where(...$args): self
    {
        $this->where = $args;
        $this->extractValues($args);
        return $this;
    }

    /**
     * Add a group by clause to the query
     * @param array<int,mixed> $group_by
     */
    public function groupBy(array $group_by): self
    {
        $this->group_by = $group_by;
        return $this;
    }

    /**
     * Add a having by clause to the query
     */
    public function having(...$args): self
    {
        $this->having = $args;
        $this->extractValues($args);
        return $this;
    }

    /**
     * Add an order by clause to the query
     * @param array<int,mixed> $order_by
     * @return QueryBuilder
     */
    public function orderBy(array $order_by): self
    {
        $this->order_by = $order_by;
        return $this;
    }

    /**
     * Add a limit to the query
     * @param int|null $limit
     * @return QueryBuilder
     */
    public function limit(?int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Add an offset to the query
     * @param int|null $offset
     * @return QueryBuilder
     */
    public function offset(?int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    private function extractValues(array $clause): void
    {
        foreach ($clause as $parts) {
            if (count($parts) === 3) {
                [$left, $operator, $right] = $parts;
                $this->values[] = $right;
            } elseif (count($parts) === 2) {
                [$left, $right] = $parts;
                $this->values[] = $right;
            }
        }
    }

    private function formatOperation(array $clause): string
    {
        $split = [];
        foreach ($clause as $parts) {
            if (count($parts) === 3) {
                [$left, $operator, $right] = $parts;
                $split[] = "($left $operator ?)";
            } elseif (count($parts) === 2) {
                [$left, $right] = $parts;
                $split[] = "($left = ?)";
            } elseif (count($parts) === 1) {
                [$where] = $parts;
                $split[] = "($where)";
            }
        }
        return implode(" AND ", $split);
    }

    /**
     * Return the sql string
     * @return string
     */
    public function build(): string
    {
        $sql = match ($this->mode) {
            "select" => "SELECT * FROM $this->table_name ",
            "insert" => "INSERT INTO $this->table_name SET ",
            "insert_ignore" => "INSERT IGNORE INTO $this->table_name SET ",
            "update" => "UPDATE $this->table_name SET ",
            "delete" => "DELETE FROM $this->table_name ",
        };
        if ($this->mode === "select") {
            if (!empty($this->columns)) {
                $select_stmt = implode(
                    ", ",
                    array_map(function ($value) {
                        return "`$value`";
                    }, $this->columns)
                );
                $sql = str_replace("*", $select_stmt, $sql);
            }
            if (!empty($this->where)) {
                $where_clause = $this->formatOperation($this->where);
                $sql .= "WHERE $where_clause ";
            }
            if (!empty($this->group_by)) {
                $group_by_clause = $this->mapToString(
                    $this->group_by,
                    fn($column) => $column,
                    ", "
                );
                $sql .= "GROUP BY $group_by_clause ";
            }
            if (!empty($this->having)) {
                $having_clause = $this->formatOperation($this->having);
                $sql .= "HAVING $having_clause ";
            }
            if (!empty($this->order_by)) {
                $order_by_clause = implode(
                    ", ",
                    array_map(
                        fn($key, $value) => "$key $value",
                        array_keys($this->order_by),
                        array_values($this->order_by)
                    )
                );
                $sql .= "ORDER BY $order_by_clause ";
            }
            if (!is_null($this->offset)) {
                $sql .= "LIMIT $this->offset";
            }
            if (!is_null($this->limit)) {
                $sql .= ", $this->limit";
            }
        } else {
            if (!empty($this->columns)) {
                $columns = $this->mapToString(
                    array_keys($this->columns),
                    fn($key) => "$key = ?",
                    ", "
                );
                $sql .= "$columns ";
            }
        }
        if ($this->mode === "update" || $this->mode === "delete") {
            if (!empty($this->where)) {
                $where_clause = $this->formatOperation($this->where);
                $sql .= "WHERE $where_clause ";
            }
        }
        return trim($sql);
    }
}
