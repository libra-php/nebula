<?php

namespace Nebula\Backend;

use App\Models\Module as ModuleModel;
use Nebula\Alerts\Flash;
use Nebula\Database\QueryBuilder;
use Nebula\Traits\Http\Response;
use Nebula\Validation\Validate;
use Nebula\Backend\FormControls;
use PDO;

class Module
{
    use Response;

    /** Module */
    protected string $module_title = "";
    protected string $module_icon = "package";
    protected ?string $table_name = null;
    protected string $name_col = "name";
    protected string $key_col = "id";
    protected bool $table_create = true;
    protected bool $table_edit = true;
    protected bool $table_destroy = true;
    protected bool $export_csv = true;
    /** Form */
    protected bool $edit_view = true;
    protected bool $create_view = true;
    protected array $form_columns = [];
    protected array $form_defaults = [];
    protected array $form_data = [];
    protected array $form_actions = [];
    protected array $file_extensions = [
        ".txt",
        ".pdf",
        ".doc",
        ".docx",
        ".xls",
        ".xlsx",
        ".ppt",
        ".pptx",
        ".csv",
        ".zip",
        ".rar",
        ".7z",
        ".tar",
        ".gz",
        ".mp3",
        ".wav",
        ".mp4",
        ".mov",
        ".avi",
    ];
    protected array $image_extensions = [
        ".jpg",
        ".jpeg",
        ".png",
        ".gif",
        ".bmp",
        ".tif",
        ".tiff",
        ".webp",
        ".svg",
        ".ico",
    ];
    /** Controls */
    protected array $form_controls = [];
    protected array $select_options = [];
    /** Table */
    protected bool $table_view = true;
    protected array $joins = [];
    protected array $table_columns = [];
    protected array $table_data = [];
    protected bool $expand_filters = false;
    /** Format */
    protected array $table_format = [];
    // Columns that are searchable
    protected array $search = [];
    // Search term
    protected string $search_term = "";
    // Column to filter by datetime
    protected string $filter_datetime = "";
    // Datetime control from
    protected string $filter_date_from = "";
    // Datetime control to
    protected string $filter_date_to = "";
    // Filter links (shown above table)
    protected array $filter_links = [];
    protected string $filter_link = "";
    // Select filters (dropdown filters shown above table)
    protected array $filter_select = [];
    // Stores values of active select filters
    protected array $filter_selections = [];
    protected array $where = [];
    protected string $order_by = "";
    protected string $sort = "DESC";
    protected array $row_actions = [];
    /** Pagination */
    protected int $page = 1;
    protected int $total_results = 0;
    protected int $total_pages = 1;
    protected int $limit = 10;
    protected ?int $offset = null;
    /** Validation */
    protected array $validation = [];
    protected array $errors = [];

    public function __construct(protected string $module_name)
    {
        $module = ModuleModel::search(["module_name", $module_name]);
        $this->table_name = $module->module_table ?? "";
        $this->module_title = $module->module_title ?? "Unknown";
        $this->module_icon = $module->module_icon ?? "package";
    }

    /**
     * Return module info used in links
     */
    public function getLinkInfo()
    {
        return [$this->module_name, $this->module_title, $this->module_icon];
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
     * Process request for index view
     */
    protected function processTableRequest(): void
    {
        $this->handleSelectFilter();
        $this->handleSearch();
        $this->handleDateTime();
        $this->handleOrdering();
        $this->handleFilterCount();
        $this->handleFilterLinks();
        $this->handlePagination();
        $this->handleExportCsv();
        $this->handleSession();
    }

    /**
     * Process request for edit / create views
     */
    protected function processFormRequest(?string $id = null): void
    {
        $this->handleDeleteFile($id);
        $this->handleSession();
    }

    /**
     * Add a row action to the table
     */
    protected function addRowAction(
        string $name,
        string $title,
        ?string $confirm = null,
        string $class = "primary"
    ): void {
        $this->row_actions[] = [
            "name" => $name,
            "title" => $title,
            "confirm" => $confirm,
            "class" => $class,
        ];
    }

    /**
     * Add an action to the form
     */
    protected function addFormAction(
        string $name,
        string $title,
        ?string $confirm = null,
        string $class = "primary"
    ): void {
        $this->form_actions[] = [
            "name" => $name,
            "title" => $title,
            "confirm" => $confirm,
            "class" => $class,
        ];
    }

    /**
     * Record the active user session
     */
    protected function handleSession()
    {
        db()->query(
            "INSERT INTO sessions SET user_id = ?, method = ?, uri = ?",
            user()->id,
            request()->getMethod(),
            request()->getUri()
        );
    }

    /**
     * Handle exporting to csv
     */
    protected function handleExportCsv()
    {
        if (request()->has("export_csv")) {
            $name = sprintf("%s_export_%s.csv", $this->module_name, time());
            header("Content-Type: text/csv");
            header(
                sprintf('Content-Disposition: attachment; filename="%s"', $name)
            );
            $fp = fopen("php://output", "wb");
            $csv_headers = $skip = [];
            $columns = $this->tableAlias($this->table_columns);
            foreach ($columns as $column => $title) {
                if (is_null($title) || trim($title) == "") {
                    $skip[] = $column;
                    continue;
                }
                $csv_headers[] = $title;
            }
            fputcsv($fp, $csv_headers);
            $this->limit = 10_000;
            $this->page = 1;
            while ($this->page <= $this->total_pages) {
                $data = $this->getTableData();
                foreach ($data as $item) {
                    fputcsv($fp, $item);
                }
                $this->page++;
            }
            fclose($fp);
            exit();
        }
    }

    /**
     * Handle uploading a file
     */
    protected function handleUpload(string $id): bool
    {
        foreach (request()->files() as $column => $file) {
            $timestamp = time();
            $random = md5(uniqid());
            $filename = $file["name"];
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $new_filename = sprintf(
                "%s_%s.%s",
                $timestamp,
                $random,
                $extension
            );
            $uploads_path = config("paths.uploads");
            $target_path = $uploads_path . $new_filename;
            if (
                !file_exists($target_path) &&
                move_uploaded_file($file["tmp_name"], $target_path)
            ) {
                $this->deleteColumnFile($column, $id);
                $qb = QueryBuilder::update($this->table_name)
                    ->columns([$column => $target_path])
                    ->where([$this->key_col, $id]);
                if (is_null(db()->run($qb->build(), $qb->values()))) {
                    return false;
                }
                $this->auditColumns([$column => $target_path], $id, "UPLOAD");
            }
        }
        return true;
    }

    /**
     * Handle deleting an uploaded file
     */
    protected function handleDeleteFile(?string $id): void
    {
        if (request()->has("delete_file")) {
            $column = request()->delete_file;
            $this->deleteColumnFile($column, $id);
            $qb = QueryBuilder::update($this->table_name)
                ->columns([$column => null])
                ->where([$this->key_col, $id]);
            if (is_null(db()->run($qb->build(), $qb->values()))) {
                Flash::addFlash(
                    "danger",
                    "Oops! An unknown issue occurred while deleting file"
                );
            }
            redirectModule("module.edit", $this->module_name, $id);
        }
    }

    /**
     * Handle deleting a file from a column
     */
    protected function deleteColumnFile(string $column, string $id): bool
    {
        $row = db()->select(
            "SELECT $column FROM $this->table_name WHERE $this->key_col = ?",
            $id
        );
        if (
            $row &&
            !is_null($row->$column) &&
            trim($row->$column) != "" &&
            file_exists($row->$column)
        ) {
            return unlink($row->$column);
        }
        return false;
    }

    /**
     * Handle filter count request
     */
    protected function handleFilterCount(): void
    {
        if (request()->has("filter_count")) {
            $idx = request()->filter_count;
            if (isset($this->filter_links[$idx])) {
                $this->where[] = [$this->filter_links[$idx]];
                $count = $this->getTotalResults(1001);
                echo $count > 1000 ? "+1000" : $count;
            }
            exit();
        }
    }

    /**
     * Handle filter link request
     */
    protected function handleFilterLinks(): void
    {
        if (request()->has("filter_link")) {
            $idx = request()->filter_link;
            if (isset($this->filter_links[$idx])) {
                session()->set(
                    $this->module_name . "_filter_link",
                    $this->filter_links[$idx]
                );
                session()->set($this->module_name . "_page", 1);
            }
        }

        if (session()->has($this->module_name . "_filter_link")) {
            $filter = session()->get($this->module_name . "_filter_link");
            // Store the title (key) as the active filter_link
            $this->filter_link = array_search($filter, $this->filter_links);
            $this->where[] = [$filter];
        } elseif (!empty($this->filter_links)) {
            // Use the first filter_links as the active filter
            $filter = array_key_first($this->filter_links);
            $this->filter_link = $filter;
            $this->where[] = [$this->filter_links[$filter]];
        }
    }

    /**
     * Handle order by & sort request
     */
    protected function handleOrdering(): void
    {
        if (request()->has("order_by") && request()->has("sort")) {
            session()->set(
                $this->module_name . "_order_by",
                request()->order_by
            );
            session()->set($this->module_name . "_sort", request()->sort);
        }

        if (
            session()->has($this->module_name . "_order_by") &&
            $this->module_name . "_sort"
        ) {
            $this->order_by = session()->get($this->module_name . "_order_by");
            $this->sort = session()->get($this->module_name . "_sort");
        }
    }

    /**
     * Handle pagination request
     */
    protected function handlePagination(): void
    {
        if (request()->has("page")) {
            session()->set(
                $this->module_name . "_page",
                intval(request()->page)
            );
        }
        if (request()->has("limit")) {
            session()->set(
                $this->module_name . "_limit",
                intval(request()->limit)
            );
            session()->set($this->module_name . "_page", 1);
        }

        if (session()->has($this->module_name . "_page")) {
            $this->page = session()->get($this->module_name . "_page");
        }
        if (session()->has($this->module_name . "_limit")) {
            $this->limit = session()->get($this->module_name . "_limit");
        }
    }

    /**
     * Handle select filters (dropdowns)
     */
    protected function handleSelectFilter(): void
    {
        if (request()->has("filter_select")) {
            // filter_select is an array of select controls
            foreach (request()->filter_select as $column => $value) {
                $filter_options = [
                    "column" => $column,
                    "value" => $value,
                ];
                $session = session()->get(
                    $this->module_name . "_filter_select"
                );
                $session[$column] = $filter_options;
                session()->set($this->module_name . "_filter_select", $session);
            }
        }

        if (session()->has($this->module_name . "_filter_select")) {
            $filters = session()->get($this->module_name . "_filter_select");
            foreach ($filters as $filter) {
                if ($filter["value"] !== "NULL") {
                    $this->expand_filters = true;
                    // Add the select filter where clause
                    $this->where[] = [$filter["column"], $filter["value"]];
                    // Remember the selection for the view
                    $this->filter_selections[$filter["column"]] =
                        $filter["value"];
                } else {
                    session()->remove($this->module_name . "_filter_select");
                }
            }
        }
    }

    /**
     * Handle search request
     */
    protected function handleSearch(): void
    {
        $where = [];
        if (request()->has("search") && trim(request()->search) != "") {
            $term = trim(request()->search);
            foreach ($this->search as $column) {
                // Search where column like search term
                $where[] = "($column LIKE '%$term%')";
            }
            session()->set($this->module_name . "_term", $term);
            session()->set($this->module_name . "_search", [
                implode(" OR ", $where),
            ]);
            session()->set($this->module_name . "_page", 1);
        } elseif (request()->has("search") && trim(request()->search) == "") {
            session()->remove($this->module_name . "_term");
            session()->remove($this->module_name . "_search");
            session()->remove($this->module_name . "_page");
        }

        if (session()->has($this->module_name . "_search")) {
            $this->expand_filters = true;
            $this->search_term = session()->get($this->module_name . "_term");
            $this->where[] = session()->get($this->module_name . "_search");
        }
    }

    /**
     * Handle datetime filter
     */
    protected function handleDateTime(): void
    {
        if (request()->has("date_from")) {
            session()->set(
                $this->module_name . "_date_from",
                request()->date_from
            );
            $this->filter_date_from = request()->date_from;
            session()->set($this->module_name . "_page", 1);
        }
        if (request()->has("date_to")) {
            session()->set($this->module_name . "_date_to", request()->date_to);
            $this->filter_date_to = request()->date_to;
            session()->set($this->module_name . "_page", 1);
        }

        if (
            $this->filter_datetime &&
            session()->has($this->module_name . "_date_from")
        ) {
            $this->filter_date_from = session()->get(
                $this->module_name . "_date_from"
            );
            if ($this->filter_date_from != "") {
                $this->expand_filters = true;
                $this->where[] = [
                    $this->table_name . "." . $this->filter_datetime,
                    ">=",
                    $this->filter_date_from,
                ];
            }
        }
        if (
            $this->filter_datetime &&
            session()->has($this->module_name . "_date_to")
        ) {
            $this->filter_date_to = session()->get(
                $this->module_name . "_date_to"
            );
            if ($this->filter_date_to != "") {
                $this->expand_filters = true;
                $this->where[] = [
                    $this->table_name . "." . $this->filter_datetime,
                    "<=",
                    $this->filter_date_to,
                ];
            }
        }
    }

    /**
     * Does the current user have row action permission?
     * This method is overrideable
     */
    protected function hasRowActionPermission(string $name, string $id): bool
    {
        return true;
    }

    /**
     * Does the current user have index permission?
     * This method is overrideable
     */
    protected function hasIndexPermission(): bool
    {
        return true;
    }

    /**
     * Does the current user have edit permission?
     * This method is overrideable
     */
    protected function hasEditPermission(string $id): bool
    {
        return $this->table_edit && !empty($this->form_columns);
    }

    /**
     * Does the current user have create permission?
     * This method is overrideable
     */
    protected function hasCreatePermission(): bool
    {
        return $this->table_create && !empty($this->form_columns);
    }

    /**
     * Does the current user have delete permission?
     * This method is overrideable
     */
    protected function hasDeletePermission(string $id): bool
    {
        return $this->table_destroy;
    }

    /**
     * Return module not found response
     */
    public function moduleNotFound($partial = false): never
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
     * Return fatal error response
     */
    public function fatalError(bool $partial = false): never
    {
        Flash::addFlash("error", "Fatal error");
        $is_part = $partial ? "content" : null;
        $response = $this->response(
            200,
            latte($this->getCustomIndex(), $this->getIndexData(), $is_part)
        );
        echo $response->send();
        exit();
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
     * Return the template used for index
     */
    protected function getIndexTemplate(): string
    {
        return "backend/index.latte";
    }

    /**
     * Return the template used for custom index
     */
    protected function getCustomIndex(): string
    {
        return "backend/custom.latte";
    }

    /**
     * Return the template used for edit
     */
    protected function getEditTemplate(): string
    {
        return "backend/edit.latte";
    }

    /**
     * Return the template used for create
     */
    protected function getCreateTemplate(): string
    {
        return "backend/create.latte";
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
        if (db()->statement->rowCount() == 0 || $old->new_value != $value) {
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
     * Filter out columns that should not be used in the
     * request for creation of / updating a record.
     */
    protected function getFilteredFormColumns(): array
    {
        $filtered_controls = ["upload", "image"];
        $data = request()->data();
        // Only columns defined in form_columns are valid
        $data = array_filter(
            $data,
            function ($value, $key) {
                $table_columns = $this->tableAlias($this->form_columns);
                $columns = array_keys($table_columns);
                return in_array($key, $columns);
            },
            ARRAY_FILTER_USE_BOTH
        );
        // Deal with "null" string
        array_walk(
            $data,
            fn(&$value, $key) => ($value =
                is_string($value) && strtolower($value) === "null"
                    ? null
                    : $value)
        );
        return array_filter(
            $data,
            fn($key) => !in_array(
                $this->form_controls[$key],
                $filtered_controls
            ),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Validate request based on user-supplied rules
     * @param array<int,mixed> $rules
     */
    protected function validate(array $rules): bool
    {
        $result = Validate::request($rules);
        $this->errors = Validate::$errors;
        return $result;
    }

    /**
     * Return the index QueryBuilder
     */
    protected function getIndexQuery(): ?QueryBuilder
    {
        if (is_null($this->table_name)) {
            return null;
        }
        $qb = QueryBuilder::select($this->table_name);

        if (!empty($this->joins)) {
            $qb->join($this->joins);
        }
        if (!empty($this->table_columns)) {
            $qb->columns(array_keys($this->table_columns));
        }
        if (!empty($this->where)) {
            $qb->where(...$this->where);
        }
        if ($this->order_by == "") {
            $this->order_by = $this->key_col;
        }
        $qb->orderBy([$this->order_by => $this->sort]);
        if (!is_null($this->offset)) {
            $qb->limit($this->limit)->offset($this->offset);
        }
        return $qb;
    }

    /**
     * Return the edit QueryBuilder
     */
    protected function getEditQuery(string $id): QueryBuilder
    {
        $qb = QueryBuilder::select($this->table_name)
            ->columns(array_keys($this->form_columns))
            ->where([$this->key_col, $id]);

        return $qb;
    }

    /**
     * Override method for rendering custom content
     * Custom content is rendered if table_name is null
     */
    protected function customContent(): string
    {
        return "";
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
                uasort($traces, fn($a, $b) => $b["time"] <=> $a["time"]);
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
        $gravatar = fn(string $str) => md5(strtolower(trim($str)));
        $singular = function (string $str) {
            return substr($str, -1) === "s" ? rtrim($str, "s") : $str;
        };
        $request = fn(string $column) => request()->has($column)
            ? request()->$column
            : "";
        $session = fn(string $column) => session()->has($column)
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

    /**
     * Return a form control closure used edit / create views
     * There are a couple of different ways to render a form control:
     * 1. Define a function callback that will render a control manually
     * 2. Use a pre-defined control type, which will render a control automatically
     */
    protected function formControls(?string $id = null): \Closure
    {
        $controls = function ($column, $value, ...$args) use ($id) {
            $fc = new FormControls($id);
            if (!isset($this->form_controls[$column])) {
                return $fc->plain($column, $value);
            }
            if (is_callable($this->form_controls[$column])) {
                return $this->form_controls[$column]($column, $value, ...$args);
            }
            return match ($this->form_controls[$column]) {
                "input" => $fc->input($column, $value, "text"),
                "textarea" => $fc->textarea($column, $value),
                "editor" => $fc->editor($column, $value),
                "disabled" => $fc->input(
                    $column,
                    $value,
                    "text",
                    attrs: "disabled=true"
                ),
                "readonly" => $fc->input(
                    $column,
                    $value,
                    "text",
                    attrs: "readonly"
                ),
                "plain" => $fc->plain($column, $value),
                "select" => $fc->select(
                    $column,
                    $value,
                    isset($this->select_options[$column])
                        ? $this->select_options[$column]
                        : []
                ),
                "nselect" => $fc->nselect(
                    $column,
                    $value,
                    isset($this->select_options[$column])
                        ? $this->select_options[$column]
                        : []
                ),
                "number" => $fc->input($column, $value, "number"),
                "color" => $fc->input($column, $value, "color"),
                "upload" => $fc->file(
                    $column,
                    $value,
                    sprintf(
                        'accept="%s"',
                        implode(", ", $this->file_extensions)
                    )
                ),
                "image" => $fc->image(
                    $column,
                    $value,
                    sprintf(
                        'accept="%s"',
                        implode(", ", $this->image_extensions)
                    )
                ),
                "checkbox" => $fc->checkbox($column, $value ?? 0),
                "switch" => $fc->switch($column, $value ?? 0),
                "datetime" => $fc->datetime($column, $value),
                default => $fc->plain($column, $value),
            };
        };
        return $controls;
    }

    /**
     * Get the total number of results from the index query
     */
    protected function getTotalResults(?int $limit = null): int
    {
        if (empty($this->table_columns)) {
            return 0;
        }
        if ($limit) {
            $this->limit = $limit;
            $this->offset = 0;
        }
        $qb = $this->getIndexQuery();
        $stmt = db()->run($qb->build(), $qb->values());
        return $stmt?->rowCount() ?? 0;
    }

    /**
     * Returns an array of data used for the table view
     */
    protected function getTableData(): array|bool
    {
        if (is_null($this->table_name) || empty($this->table_columns)) {
            return false;
        }
        $data = [];
        $this->total_results = $this->getTotalResults();
        $this->total_pages = ceil($this->total_results / $this->limit);
        if ($this->page > $this->total_pages) {
            $this->page = $this->total_pages;
        }
        if ($this->page < 1) {
            $this->page = 1;
        }
        $this->offset = ($this->page - 1) * $this->limit;
        $qb = $this->getIndexQuery();
        $data = db()
            ->run($qb->build(), $qb->values())
            ->fetchAll();
        return $data;
    }

    /**
     * If the subquery has an alias, then we want return an updated
     * table columns where we only use the alias name as the table
     * columns key.
     * This will also remove the table prefix, if it was provided
     * @param array<int,mixed> $columns
     */
    protected function tableAlias(array $columns): array
    {
        $filtered = array_map(function ($column) {
            $lower = strtolower($column);
            if (preg_match("/( as )/", $lower)) {
                $split = explode(" as ", $lower);
                return end($split);
            }
            $split = explode(".", $lower);
            return end($split);
        }, array_keys($columns));
        $filtered_table_columns = [];
        $idx = 0;
        foreach ($columns as $_ => $value) {
            $filtered_table_columns[$filtered[$idx]] = $value;
            $idx++;
        }
        // Finally, columns with null values shouldn't be rendered
        return array_filter(
            $filtered_table_columns,
            fn($value) => !is_null($value)
        );
    }

    /**
     * Apply a format function for a table value
     */
    protected function tableFormat(array &$data): void
    {
        foreach ($data as &$datum) {
            foreach ($datum as $column => $value) {
                $tf = new TableFormat();
                if (isset($this->table_format[$column])) {
                    if (is_callable($this->table_format[$column])) {
                        $datum[$column] = $this->table_format[$column](
                            (object) $datum,
                            $column
                        );
                        continue;
                    }
                    $datum[$column] = match ($this->table_format[$column]) {
                        "dollar" => $tf->dollar($column, $value),
                        default => $tf->text($column, $value),
                    };
                } else {
                    $datum[$column] = $tf->text($column, $value);
                }
            }
        }
    }

    /**
     * Returns an array of all form_columns that are required
     */
    protected function getRequiredForm(): array
    {
        return array_keys(
            array_filter(
                $this->validation,
                fn($rules) => in_array("required", $rules)
            )
        );
    }

    /**
     * Return data used for index view
     * @return array<string,mixed>
     */
    protected function getIndexData(): array
    {
        $this->processTableRequest();
        $data = $this->getTableData();
        if (!empty($data)) {
            $this->tableFormat($data);
        }

        $has_delete_permission = fn(string $id) => $this->hasDeletePermission(
            $id
        );
        $has_edit_permission = fn(string $id) => $this->hasEditPermission($id);
        $has_create_permission = fn() => $this->hasCreatePermission();
        $has_row_action_permission = fn(
            string $name,
            string $id
        ) => $this->hasRowActionPermission($name, $id);
        // Renders a select filter
        $filter_select_control = function (string $name, ?string $value) {
            $options = $this->select_options[$name];
            $fc = new FormControls(null);
            $name = "filter_select[$name]";
            $control = $fc->nselect(
                $name,
                $value,
                $options,
                "form-select form-select-sm filter-select",
                "hx-get='" .
                    moduleRoute("module.index.part", $this->module_name) .
                    "'",
                "hx-target='#module'",
                "hx-trigger='change'"
            );
            return $control;
        };

        $breadcrumbs = [
            "Home" => moduleRoute("module.index.part", "home"),
            $this->module_title => moduleRoute(
                "module.index.part",
                $this->module_name
            ),
        ];

        return [
            ...$this->commonData(),
            "breadcrumbs" => $breadcrumbs,
            "custom_content" => $this->customContent(),
            "has_delete_permission" => $has_delete_permission,
            "has_edit_permission" => $has_edit_permission,
            "has_create_permission" => $has_create_permission,
            "has_filter_search" => !empty($this->search),
            "has_filter_links" => !empty($this->filter_links),
            "has_filter_datetime" => $this->filter_datetime != "",
            "has_filter_select" => !empty($this->filter_select),
            "filter_search_term" => $this->search_term,
            "expand_filters" => $this->expand_filters,
            "filter_select" => $this->filter_select,
            "filter_links" => $this->filter_links,
            "filter_link" => $this->filter_link,
            "filter_date_from" => $this->filter_date_from,
            "filter_date_to" => $this->filter_date_to,
            "filter_select_control" => $filter_select_control,
            "filter_selections" => $this->filter_selections,
            "table" => [
                "export_csv" => $this->export_csv,
                "create" => $this->table_create,
                "edit" => $this->table_edit,
                "destroy" => $this->table_destroy,
                "order_by" => $this->order_by,
                "sort" => $this->sort,
                "total_results" => $this->total_results,
                "total_pages" => $this->total_pages,
                "per_page" => $this->limit,
                "per_page_options" => [5, 10, 15, 25, 50, 100, 200, 500, 1000],
                "page" => $this->page,
                "data" => $data,
                "columns" => $this->tableAlias($this->table_columns),
                "col_span" => count($this->table_columns) + 1,
                "row_actions" => $this->row_actions,
                "has_row_action_permission" => $has_row_action_permission,
            ],
        ];
    }

    /**
     * Return data used for create view
     * @return array<string,mixed>
     */
    protected function getCreateData(): array
    {
        $this->processFormRequest();
        $fc = $this->formControls();
        $breadcrumbs = [
            "Home" => moduleRoute("module.index.part", "home"),
            $this->module_title => moduleRoute(
                "module.index.part",
                $this->module_name
            ),
            "Create" => moduleRoute("module.create.part", $this->module_name),
        ];
        return [
            ...$this->commonData(),
            "breadcrumbs" => $breadcrumbs,
            "controls" => $fc,
            "form" => [
                "data" => [],
                "defaults" => $this->form_defaults,
                "required" => $this->getRequiredForm(),
                "columns" => $this->form_columns,
            ],
        ];
    }

    /**
     * Return data used for edit view
     * @return array<string,mixed>
     */
    protected function getEditData(string $id): array
    {
        $this->processFormRequest($id);
        $qb = $this->getEditQuery($id);
        $data = null;
        $data = !is_null($qb)
            ? db()
                ->run($qb->build(), $qb->values())
                ->fetch()
            : [];
        if (!$data) {
            $this->moduleNotFound();
        }
        $fc = $this->formControls($id);
        $name = $data[$this->name_col] ?? $id;
        $breadcrumbs = [
            "Home" => moduleRoute("module.index.part", "home"),
            $this->module_title => moduleRoute(
                "module.index.part",
                $this->module_name
            ),
            "Edit ({$name})" => moduleRoute(
                "module.edit.part",
                $this->module_name,
                $id
            ),
        ];
        return [
            ...$this->commonData(),
            "title_name" => $data[$this->name_col] ?? $id,
            "breadcrumbs" => $breadcrumbs,
            "id" => $id,
            "controls" => $fc,
            "form" => [
                "actions" => $this->form_actions,
                "data" => $data,
                "required" => $this->getRequiredForm(),
                "columns" => $this->form_columns,
            ],
        ];
    }

    /**-------- ENDPOINTS -----------------------------------------------*/
    /* Endpoints are called from ModuleController */
    public function index(): string
    {
        if (!$this->hasIndexPermission()) {
            $this->permissionDenied();
        }
        $template =
            !is_null($this->table_name) && trim($this->table_name) != ""
                ? $this->getIndexTemplate()
                : $this->getCustomIndex();
        return latte($template, $this->getIndexData());
    }

    public function indexPartial(): string
    {
        if (!$this->hasIndexPermission()) {
            $this->permissionDenied();
        }
        $template =
            !is_null($this->table_name) && trim($this->table_name) != ""
                ? $this->getIndexTemplate()
                : $this->getCustomIndex();
        return latte($template, $this->getIndexData(), "content");
    }

    public function edit(string $id): string
    {
        if (!$this->hasEditPermission($id)) {
            $this->permissionDenied();
        }
        return latte($this->getEditTemplate(), $this->getEditData($id));
    }

    public function editPartial(string $id): string
    {
        if (!$this->hasEditPermission($id)) {
            $this->permissionDenied();
        }
        return latte(
            $this->getEditTemplate(),
            $this->getEditData($id),
            "content"
        );
    }

    public function create(): string
    {
        if (!$this->hasCreatePermission()) {
            $this->permissionDenied();
        }
        return latte($this->getCreateTemplate(), $this->getCreateData());
    }

    public function createPartial(): string
    {
        if (!$this->hasCreatePermission()) {
            $this->permissionDenied();
        }
        return latte(
            $this->getCreateTemplate(),
            $this->getCreateData(),
            "content"
        );
    }

    public function store(): string
    {
        if (!$this->hasCreatePermission()) {
            $this->permissionDenied();
        }
        if ($this->validate($this->validation) && $this->table_create) {
            $columns = $this->getFilteredFormColumns();
            $qb = QueryBuilder::insert($this->table_name)->columns($columns);
            $result = (bool) db()->run($qb->build(), $qb->values());
            $id = db()->lastInsertId();
            if ($id && request()->files()) {
                $result &= $this->handleUpload($id);
            }
            if ($result) {
                $this->auditColumns($columns, $id, "INSERT");
                Flash::addFlash("success", "Record created successfully");
            } else {
                Flash::addFlash(
                    "danger",
                    "Oops! An unknown issue occurred while creating new record"
                );
            }
        }
        return $this->createPartial();
    }

    public function update(string $id): string
    {
        if (!$this->hasEditPermission($id)) {
            $this->permissionDenied();
        }
        if ($this->validate($this->validation) && $this->table_edit) {
            $columns = $this->getFilteredFormColumns();
            $qb = QueryBuilder::update($this->table_name)
                ->columns($columns)
                ->where(["id", $id]);
            $result = (bool) db()->run($qb->build(), $qb->values());
            if ($result && request()->files()) {
                $result &= $this->handleUpload($id);
            }
            if ($result) {
                $this->auditColumns($columns, $id, "UPDATE");
                Flash::addFlash("success", "Record updated successfully");
            } else {
                Flash::addFlash(
                    "danger",
                    "Oops! An unknown issue occurred while updating record"
                );
            }
        }
        return $this->editPartial($id);
    }

    public function destroy(string $id): string
    {
        if (!$this->hasDeletePermission($id)) {
            $this->permissionDenied();
        }
        if ($this->table_destroy) {
            $qb = QueryBuilder::delete($this->table_name)->where([
                $this->key_col,
                $id,
            ]);
            $result = db()->run($qb->build(), $qb->values());
            if ($result) {
                $this->auditColumns([$this->key_col => "NULL"], $id, "DELETE");
                Flash::addFlash("success", "Record deleted successfully");
            } else {
                Flash::addFlash(
                    "danger",
                    "Oops! An unknown issue occurred while deleting record"
                );
            }
        }
        return $this->indexPartial();
    }
}
