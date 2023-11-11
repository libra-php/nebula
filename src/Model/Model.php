<?php

namespace Nebula\Model;

use Nebula\Database\QueryBuilder;
use Nebula\Interfaces\Model\Model as NebulaModel;
use Nebula\Traits\Property\ProtectedData;
use PDO;

class Model implements NebulaModel
{
    use ProtectedData;

    public string $table_name;
    public string $primary_key;
    public array $table_columns;
    protected array $guarded = ["id", "created_at", "updated_at"];

    /**
     * Return the static class
     * @return self
     * @throws \Error if table_name or primary_key is not defined
     */
    private static function staticClass(): self
    {
        // Get the static class (e.g. User)
        $class = app()->get(static::class);
        // Table name and primary key should be defined, otherwise throw error
        if (!property_exists($class, "table_name")) {
            throw new \Error("table_name must be defined for " . static::class);
        }
        if (!property_exists($class, "primary_key")) {
            throw new \Error(
                "primary_key must be defined for " . static::class
            );
        }
        return $class;
    }

    /**
     * Get the table columns
     * @return array
     */
    private function getTableColumns(): array
    {
        if (!isset($this->table_columns)) {
            $this->table_columns = db()
                ->query("DESCRIBE $this->table_name")
                ->fetchAll(PDO::FETCH_COLUMN);
        }
        return $this->table_columns;
    }

    /**
     * Find a model from the database
     * @param array $data
     * @return self|null
     */
    public static function find(mixed $id): ?self
    {
        $model = app()->get(static::class);
        return self::search([$model->primary_key, $id]);
    }

    /**
     * Find a model by an attribute
     */
    public static function search(...$args): mixed
    {
        $model = self::staticClass();
        // Build the sql query
        $qb = QueryBuilder::select($model->table_name)
            ->columns($model->getTableColumns())
            ->where(...$args);
        // Select one item from the db
        $result = db()
            ->run($qb->build(), $qb->values())
            ->fetchAll(PDO::FETCH_ASSOC);
        // Bail if it is bunk
        if (!$result) {
            return null;
        }
        // If there is only one result, return a model
        if (count($result) === 1) {
            $result = $result[0];
            // Create a model and load result
            $model = new $model($result[$model->primary_key]);
            $model->load($result);
            return $model;
        }
        // If there are multiple results, return an array of models
        $models = [];
        foreach ($result as $data) {
            $model = new $model($data[$model->primary_key]);
            $model->load($data);
            $models[] = $model;
        }
        return $models;
    }

    /**
     * Setup the model with initial table columns
     * @return void
     */
    private function setup(): void
    {
        // A new model will not have data, so we
        // will fill it in with table columns
        $table_columns = $this->getTableColumns($this->table_name);
        $diff_columns = array_diff($table_columns, $this->guarded);
        $update_columns = [];
        foreach ($diff_columns as $column) {
            $update_columns[$column] = $this->$column ?? null;
        }
        // Load the data into the model
        $this->load($update_columns);
    }

    public function refresh(): void
    {
        $model = self::find($this->id);
        $this->load($model->data());
    }

    /**
     * Save model state
     * If $this->id is set, then update.
     * Otherwise insert new record
     * Returns null if failure, id if insert, bool if update
     * @return mixed
     */
    public function save(): mixed
    {
        $result = $this->id
            ? $this->update($this->data())
            : $this->insert($this->data());
        return $result;
    }

    /**
     * Create a new model
     * Save data to database
     * @return self|null
     */
    public function insert(array $data, $ignore = false): mixed
    {
        $qb = $ignore
            ? QueryBuilder::insertIgnore($this->table_name)->columns($data)
            : QueryBuilder::insert($this->table_name)->columns($data);
        $result = db()->run($qb->build(), $qb->values());
        if (!$result) {
            return null;
        }
        // Set the id and reload the model
        $id = db()->lastInsertId();
        return $id;
    }

    /**
     * Update a model
     * Save data to database
     */
    public function update(array $data): bool
    {
        $model = self::staticClass();
        $model->setup();
        // Note: we use $this->id to refer to the current model
        $qb = QueryBuilder::update($model->table_name)
            ->columns($data)
            ->where([$model->primary_key, "=", $this->id]);
        $result = db()->run($qb->build(), $qb->values());
        return $result !== null;
    }

    /**
     * Delete a model
     */
    public function delete(): bool
    {
        $model = self::staticClass();
        $model->setup();
        $qb = QueryBuilder::delete($model->table_name)->where([
            $model->primary_key,
            $this->id,
        ]);
        $result = db()->run($qb->build(), $qb->values());
        return $result !== null;
    }
}
