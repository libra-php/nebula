<?php

namespace Nebula\Traits\Admin;

use Nebula\Alerts\Flash;
use Nebula\Model\Model;
use Nebula\Validation\Validate;
use PDO;

trait ModuleCommon
{
    // Module settings
    /** The module name (used as the route uri) */
    protected string $module_name = '';
    /** The module table <h1> */
    protected string $module_title = "";
    /** Icon: boostrap-icons */
    protected string $module_icon = "bi bi-box";
    /** Query table name */
    protected ?string $table_name = null;
    /** Query primary key */
    protected string $key_col = "id";
    /** Validation rule array */
    protected array $validation = [];

    public function init(Model $module)
    {
        $this->module_name = $module->module_name;
        $this->module_title = $module->module_title;
        $this->module_icon = $module->module_icon;
        $this->table_name = $module->module_table;
    }

    /**
     * Return the module name
     */
    public function getModuleName(): string
    {
        return $this->module_name;
    }

    /**
     * Return the table name
     */
    public function getTableName(): string
    {
        return $this->table_name;
    }

    /**
     * Return module not found response
     */
    public function moduleNotFound(bool $partial = false): never
    {
        Flash::addFlash(
            "warning",
            "Oops! The requested module could not be found"
        );
        $is_part = $partial ? "content" : null;
        $response = $this->response(
            404,
            latte($this->getCustomIndex(), $this->getIndexData(), $is_part)
        );
        echo $response->send();
        exit();
    }

    /**
     * Return permission denied response
     */
    public function permissionDenied(bool $partial = false): never
    {
        Flash::addFlash("error", "Permission denied");
        $is_part = $partial ? "content" : null;
        $response = $this->response(
            403,
            latte($this->getCustomIndex(), $this->getIndexData(), $is_part)
        );
        echo $response->send();
        exit();
    }

    /**
     * Record the active user session
     */
    protected function handleSession(): void
    {
        db()->query(
            "INSERT INTO sessions SET user_id = ?, method = ?, uri = ?",
            user()->id,
            request()->getMethod(),
            request()->getUri()
        );
    }

    /**
     * Get the module links for navbar and sidebar
     * Home should appear first
     */
    protected function getModuleLinks()
    {
        return db()
            ->run(
                "SELECT module_name as name, module_title as title, module_icon as icon
      FROM modules
      WHERE user_type >= ?
      ORDER BY name = 'home' DESC",
                [user()->user_type]
            )
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add a row to the audit table
     */
    protected function audit(
        string $user_id,
        string $table_name,
        string $table_id,
        string $field,
        ?string $value = null,
        string $message = ""
    ): void {
        $old = db()->select(
            "SELECT new_value
            FROM audit
            WHERE table_name = ? AND
            table_id = ? AND
            field = ?
            ORDER BY created_at DESC
            LIMIT 1",
            $table_name,
            $table_id,
            $field
        );
        if (db()->statement->rowCount() == 0 || $old->new_value !== $value) {
            db()->run(
                "INSERT INTO audit SET
                user_id = ?,
                table_name = ?,
                table_id = ?,
                field = ?,
                old_value = ?,
                new_value = ?,
                message = ?,
                created_at = NOW()",
                [
                    $user_id,
                    $table_name,
                    $table_id,
                    $field,
                    $old->new_value ?? "NULL",
                    $value,
                    $message,
                ]
            );
        }
    }

    /**
     * Audit table columns
     * @param array<int,mixed> $columns
     */
    protected function auditColumns(
        array $columns,
        string $id,
        string $message = ""
    ): void {
        foreach ($columns as $column => $value) {
            $this->audit(
                user()->id,
                $this->table_name,
                $id,
                $column,
                $value,
                $message
            );
        }
    }

    /**
     * Validate request based on user-supplied rules
     * @param array<int,mixed> $rules
     */
    protected function validate(array &$rules): bool
    {
        // Auto-fix the validation titles
        foreach ($rules as $column => $rule) {
            $title = $this->form_columns[$column] ?? $column;
            $rules[$column] = [$title => $rule];
        }
        $result = Validate::request($rules);
        return $result;
    }

    /**
     * Convert value (size) to unit
     */
    function convert($size): string
    {
        $units = ["b", "kb", "mb", "gb", "tb", "pb"];
        $value = round($size / pow(1024, $i = floor(log($size, 1024))), 2);
        $unit = $units[$i];
        return sprintf("%s%s", $value, $unit);
    }

    /**
     * Return the profiler array used in all views
     */
    protected function profiler(): array
    {
        global $global_memory;
        $slow_traces = [];
        $total = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        $db_total = db()->total_time ?? 0;
        $php_total = $total - $db_total;
        $memory = memory_get_usage() - $global_memory;
        $memory_total = $this->convert($memory);
        foreach (["Slow DB:" => db()->trace_counts] as $title => $traces) {
            if ($traces) {
                uasort($traces, fn ($a, $b) => $b["time"] <=> $a["time"]);
                $i = 0;

                foreach ($traces as $key => $value) {
                    $i++;
                    if ($i > 10) {
                        break;
                    }
                    $pct =
                        number_format(($value["time"] / $db_total) * 100, 2) .
                        "%";
                    $slow_traces[] = [
                        "file" => $key,
                        "count" => $value["count"],
                        "time" => $value["time"],
                        "pct" => $pct,
                    ];
                }
            }
        }
        return [
            "show_profiler" => config("database.show_profiler"),
            "global_start" => $_SERVER["REQUEST_TIME_FLOAT"],
            "total_memory" => $memory_total,
            "total_time" => number_format($total, 6),
            "db_total_time" => number_format($db_total, 6),
            "php_total_time" => number_format($php_total, 6),
            "db_num_queries" => db()->num_queries ?? 0,
            "slow_traces" => $slow_traces ?? [],
        ];
    }

    /**
     * Returns common data array used in all views
     */
    public function commonData(): array
    {
        $buildRoute = function (string $name, ...$replacements) {
            return buildRoute($name, ...$replacements);
        };
        $route = function (string $route_name, ?string $id = null) {
            return moduleRoute($route_name, $this->module_name, $id);
        };
        $moduleRoute = function (
            string $route_name,
            string $module_name,
            ?string $id = null
        ) {
            return moduleRoute($route_name, $module_name, $id);
        };
        $gravatar = fn (string $str) => md5(strtolower(trim($str)));
        $singular = function (string $str) {
            return substr($str, -1) === "s" ? rtrim($str, "s") : $str;
        };
        $request = fn (string $column) => request()->has($column)
            ? request()->$column
            : "";
        $session = fn (string $column) => session()->has($column)
            ? session()->get($column)
            : "";
        return [
            "profiler" => $this->profiler(),
            "has_flash" => Flash::hasFlash(),
            "request" => $request,
            "session" => $session,
            "gravatar" => $gravatar,
            "route" => $route,
            "moduleRoute" => $moduleRoute,
            "buildRoute" => $buildRoute,
            "route_name" => request()->route->getName(),
            "module_icon" => $this->module_icon,
            "module_name" => $this->module_name,
            "module_title" => $this->module_title,
            "module_title_singular" => $singular($this->module_title),
            "module_links" => $this->getModuleLinks(),
            "key_col" => $this->key_col,
            "name_col" => $this->name_col,
        ];
    }
}
