<?php

namespace Nebula\Framework\Admin;

use App\Models\Session;
use Exception;
use Carbon\Carbon;
use Nebula\Framework\Alerts\Flash;
use Nebula\Framework\Controller\Controller;
use PDO;

class Module
{
    // Route path
    protected string $path = "";
    // SQL table
    protected string $sql_table = "";
    // Primary key of SQL table
    protected string $primary_key = "";
    // Module title
    protected string $title = "";
    // Edit mode enabled
    protected bool $edit = true;
    // Create mode enabled
    protected bool $create = true;
    // Delete mode enabled
    protected bool $delete = true;
    // Validation rules
    protected array $validation_rules = [];

    // Link column
    protected string $link_column = "";
    // SQL columns
    protected array $table_columns = [];
    // GROUP BY clause
    protected string $table_group_by = "";
    // ORDER BY clause
    protected string $table_order_by = "";
    // Sort order
    protected string $table_sort = "DESC";
    // Table column format
    protected array $table_format = [];
    // Number of pagination side links
    protected int $side_links = 1;
    // WHERE clause conditions/params
    private array $table_where = [];
    // HAVING clause conditions/params
    private array $table_having = [];
    // OFFSET clause
    private int $page = 1;
    // LIMIT clause
    private int $per_page = 10;
    // Total number of pages
    private int $total_pages = 1;
    // Total row count
    private int $total_results = 0;
    // Per page option values
    private array $per_page_options = [
        10,
        20,
        50,
        100,
        200,
        500,
        750,
        1000,
        2000,
        5000,
    ];
    // Show table inline row action
    protected bool $row_actions = true;
    // Show table export CSV action
    protected bool $export_csv = true;

    // Table filter links
    protected array $filter_links = [];
    // Searchable columns
    protected array $search_columns = [];

    // Form columns
    protected array $form_columns = [];
    // Form controls
    protected array $form_controls = [];
    // Form columns that are readonly
    protected array $form_readonly = [];
    // Form columns that are disabled
    protected array $form_disabled = [];
    // Select control options
    protected array $select_options = [];

    public function __construct(
        private object $config,
        private Controller $controller,
        protected ?string $id = null
    ) {
        $this->title = $config->title;
        $this->path = $config->path;
        $this->sql_table = $config->sql_table;
        $this->primary_key = $config->primary_key ?? "id";
        // Check Administrator for insecure password
        $this->checkInvalidPassword();
        // Init child module
        $this->init();
    }

    /**
     * Child module initialization
     */
    public function init(): void
    {
    }

    /**
     * Set a warning message if the current user
     * is using an insecure password (default).
     */
    private function checkInvalidPassword()
    {
        $user = user();
        if (password_verify("admin2024!", $user->password)) {
            $link = sprintf("<a href='%s' hx-indicator='#request-progress' hx-boost='true' hx-select='#view' hx-target='#view' hx-swap='outerHTML show:no-scroll'>%s</a>", "/admin/profile/{$user->id}", "update");
            Flash::add("warning", "Your current password is not secure.<br>Please $link your password immediately.");
        }
    }

    /**
     * Set session helper
     */
    public function setSession(string $key, mixed $value): void
    {
        session()->set($this->path . "_$key", $value);
    }

    /**
     * Get session helper
     */
    public function getSession(string $key): mixed
    {
        return session()->get($this->path . "_$key");
    }

    /**
     * Delete session helper
     */
    public function deleteSession(string $key): void
    {
        session()->delete($this->path . "_$key");
    }

    /**
     * Render a GET method view
     */
    public function render(string $type, ?string $id = null): string
    {
        $content = match ($type) {
            "index" => $this->viewIndex(),
            "create" => $this->viewCreate(),
            "edit" => $this->viewEdit($id),
        };
        return $this->controller->render("layout/admin.php", [
            "breadcrumbs" => $this->getBreadcrumbs($id),
            "navbar" => $this->getNavbar(),
            "title" => $this->getPageTitle($type, $id),
            "module_title" => $this->getModuleTitle(),
            "sidebar" => $this->getSidebar(),
            "content" => $content,
        ]);
    }

    /**
     * Process a module request
     * Setting page number, search term, other filters, etc
     * @param array $request the validated request
     */
    public function processRequest(array $request)
    {
        if (isset($request["order"]) && isset($request["sort"])) {
            $this->setOrderSort($request["order"], $request["sort"]);
        }
        if (isset($request["page"])) {
            $this->setPage(intval($request["page"]));
        }
        if (isset($request["per_page"])) {
            $this->setPerPage(intval($request["per_page"]));
        }
        if (isset($request["term"])) {
            $this->setSearch($request["term"]);
        }
        if (isset($request["filter_link"])) {
            $this->setPage(1);
            $this->setFilterLink(intval($request["filter_link"]));
        }
        if (isset($request["export_csv"])) {
            $this->exportCsv();
        }
        if (isset($request["filter_count"])) {
            $count = $this->getFilterLinkCount(
                intval($request["filter_count"])
            );
            return $count > 1000 ? "1000+" : $count;
        }
    }

    /**
     * Export table view to CSV format
     */
    protected function exportCsv(): void
    {
        $this->filters();
        header("Content-Type: text/csv");
        header('Content-Disposition: attachment; filename="csv_export.csv"');
        $fp = fopen("php://output", "wb");
        $titles = array_keys($this->table_columns);
        fputcsv($fp, $titles);
        $this->per_page = 1000;
        $this->page = 1;
        $this->total_results = $this->getTotalCount();
        $this->total_pages = ceil($this->total_results / $this->per_page);
        while ($this->page <= $this->total_pages) {
            $data = $this->getTableData();
            foreach ($data as $item) {
                $this->tableValueOverride($item);
                $values = array_values((array) $item);
                fputcsv($fp, $values);
            }
            $this->page++;
        }
        fclose($fp);
        exit();
    }

    /**
     * Record a user session
     */
    public function recordSession(): void
    {
        Session::new([
            "request_uri" => $_SERVER["REQUEST_URI"],
            "ip" => ip2long($this->controller->userIp()),
            "user_id" => user()->id,
            "module_id" => $this->controller->moduleID(),
        ]);
    }

    /**
     * Format the table query condtions as 'AND' delimited
     * @param array $conditions where or having clause
     */
    protected function formatAnd(array $conditions): string
    {
        $out = [];
        foreach ($conditions as $item) {
            [$clause, $params] = $item;
            // Add parens to clause for order of ops
            $out[] = "(" . $clause . ")";
        }
        return sprintf("%s", implode(" AND ", $out));
    }

    /**
     * Format the query condtions as ',' delimited
     * @param array $conditions
     */
    protected function formatComma(array $conditions): string
    {
        return sprintf("%s", implode(", ", $conditions));
    }

    /**
     * Get extract query params
     * These are replacement values for the '?' in the query
     * @param array $target condition array (for where and having)
     * @return array|<missing>
     */
    private function getParams(array $target): array
    {
        if (!$target) {
            return [];
        }
        $params = [];
        foreach ($target as $item) {
            [$clause, $param_array] = $item;
            $params = [...$params, ...$param_array];
        }
        return $param_array;
    }

    /**
     * Return the form column array
     */
    public function getFormColumns(): array
    {
        return $this->form_columns;
    }

    /**
     * Handle all the filters, which add where clauses to the main query
     */
    private function filters(bool $filter_links = true): void
    {
        if ($filter_links) {
            $this->handleFilterLinks();
        }
        $this->handleSearch();
        $this->handlePerPage();
        $this->handlePage();
        $this->handleOrderSort();
    }

    /**
     * Does this module have DELETE permission
     * Delete button appears inline table row action
     */
    public function hasDeletePermission(string $id): bool
    {
        return $this->delete;
    }

    /**
     * Does this module have EDIT permission
     * Edit button appears inline table row action
     */
    public function hasEditPermission(string $id): bool
    {
        return $this->edit && !empty($this->form_columns);
    }

    /**
     * Does this module have CREATE permission
     * Create button appears at top table actions
     */
    public function hasCreatePermission(): bool
    {
        return $this->create && !empty($this->form_columns);
    }

    /**
     * Get the module path
     * How the module route is resolved (ie, /admin/users)
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the module title
     * The title at the top of a module
     */
    public function getModuleTitle(): string
    {
        return $this->title;
    }

    public function getPageTitle(string $type, ?string $id = null)
    {
        $title = config("application.name") . " [Admin] > " . $this->getModuleTitle();
        if ($type === "create") $title .= " > Create";
        if ($type === "edit") $title .= " > Edit";
        if (!is_null($id)) $title .= " > " . $id;
        return $title;
    }

    /**
     * Return module validation rule array
     */
    public function getValidationRules(): array
    {
        return $this->validation_rules;
    }

    /**
     * Recursively build sidebar data struct
     * @return array<int,array<string,mixed>>
     */
    private function buildLinks(?int $parent_module_id = null): array
    {
        $user = user();
        if (is_null($parent_module_id)) {
            $modules = db()->fetchAll("SELECT *
				FROM modules
				WHERE parent_module_id IS NULL
                AND enabled = 1
				ORDER BY item_order");
        } else {
            $modules = db()->fetchAll(
                "SELECT *
				FROM modules
				WHERE parent_module_id = ?
                AND enabled = 1
				ORDER BY item_order",
                $parent_module_id
            );
        }
        $sidebar_links = [];
        foreach ($modules as $module) {
            // Skip the modules that the user doesn't have permission to
            if (
                !is_null($module->max_permission_level) &&
                $user->type()->permission_level > $module->max_permission_level
            ) {
                continue;
            }
            $link = [
                "id" => $module->id,
                "label" => $module->title,
                "link" => "/admin/{$module->path}",
                "children" => $this->buildLinks($module->id),
            ];
            $sidebar_links[] = $link;
        }
        // Add sign out link
        if ($parent_module_id == 2) {
            $link = [
                "id" => null,
                "label" => "Sign Out",
                "link" => "/sign-out",
                "children" => [],
            ];
            $sidebar_links[] = $link;
        }
        return $sidebar_links;
    }

    private function mostVisited(): array
    {
        $most_visited = db()->fetchAll("SELECT modules.path, modules.title, COUNT(sessions.id)  as count
            FROM sessions
            INNER JOIN modules ON module_id = modules.id
            WHERE user_id = ?
            GROUP BY module_id
            ORDER BY count DESC
            LIMIT 10", user()->id);
        return $most_visited;
    }

    /**
     * Recursively build the breadcrumb links
     */
    private function buildBreadcrumbs(string $module_id, $breadcrumbs = [])
    {
        $module = db()->fetch(
            "SELECT * FROM modules WHERE id = ? AND enabled = 1",
            $module_id
        );
        $breadcrumbs[] = $module;
        if (intval($module->parent_module_id) > 0) {
            return $this->buildBreadcrumbs(
                $module->parent_module_id,
                $breadcrumbs
            );
        }
        return array_reverse($breadcrumbs);
    }

    /**
     * Get the breadcrumbs
     */
    public function getBreadcrumbs(?string $id): string
    {
        $path = $this->getPath();
        $breadcrumbs = $this->buildBreadcrumbs($this->config->id);
        $route_name = $this->controller->request()->get("route")->getName();
        if ($route_name === "module.create") {
            $breadcrumbs[] = (object) [
                "path" => "$path/create",
                "title" => "Create",
            ];
        } else if (!is_null($id)) {
            $breadcrumbs[] = (object) [
                "path" => "$path/$id",
                "title" => "Edit $id",
            ];
        }
        return template("layout/breadcrumbs.php", [
            "breadcrumbs" => $breadcrumbs,
        ]);
    }

    /**
     * Get the navbar template
     */
    public function getNavbar(): string
    {
        $links = $this->buildLinks();
        return template("layout/navbar.php", [
            "app_name" => config("application.name"),
            "links" => $links,
        ]);
    }

    /**
     * Get the sidebar template
     */
    public function getSidebar(): string
    {
        $links = $this->buildLinks();
        $most_visited = $this->mostVisited();
        return template("layout/sidebar.php", [
            "most_visited" => $most_visited,
            "links" => $links
        ]);
    }

    /**
     * Get the filter link row count
     */
    public function getFilterLinkCount(int $index): int
    {
        // Get 0-indexed array
        $filters = array_values($this->filter_links);
        // Set the filter according to the index
        $filter = $filters[$index];
        // Add the filter having clause (aliases work)
        $this->addHaving($filter);
        // Update filters for proper counts
        $this->filters(false);
        // Get the rowCount
        $this->page = 1;
        $this->per_page = 1001;

        $sql = $this->getIndexQuery();
        $where_params = $this->getParams($this->table_where);
        $having_params = $this->getParams($this->table_having);
        $params = [...$where_params, ...$having_params];
        $stmt = db()->run($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Handle the current page
     */
    private function handlePage(): void
    {
        $this->total_results = $this->getTotalCount();
        $this->total_pages = ceil($this->total_results / $this->per_page);
        $page = intval($this->getSession("page"));
        if ($page > 0 && $page <= $this->total_pages) {
            $this->page = $page;
        } else {
            // Set to valid page
            if ($page < 1) {
                $this->page = 1;
            } elseif ($page > $this->total_pages) {
                $this->page = $this->total_pages;
            }
            $this->setPage($this->page);
        }
    }

    /**
     * Handle the table order_by / sorting
     */
    private function handleOrderSort(): void
    {
        $order_by = $this->getSession("order_by");
        $sort = $this->getSession("sort");
        if ($order_by && $sort) {
            $this->table_order_by = $order_by;
            $this->table_sort = $sort;
        } else {
            if (!$this->table_order_by) {
                $this->table_order_by = $this->primary_key;
                $this->table_sort = "DESC";
            }
        }
    }

    /**
     * Handle the results per page
     */
    private function handlePerPage(): void
    {
        $per_page = intval($this->getSession("per_page"));
        if ($per_page > 0) {
            $this->per_page = $per_page;
        }
    }

    /**
     * Handle the search term, add where clause
     */
    private function handleSearch(): void
    {
        $search_term = $this->getSession("search_term");
        if ($search_term) {
            $conditions = array_map(
                fn ($column) => "($column LIKE ?)",
                $this->search_columns
            );
            $this->addHaving(
                implode(" OR ", $conditions),
                ...array_fill(0, count($this->search_columns), "%$search_term%")
            );
        }
    }

    /**
     * Handle the filter links, add where clause
     */
    private function handleFilterLinks(): void
    {
        if (count($this->filter_links) === 0) {
            return;
        }
        $index = $this->getSession("filter_link");
        // The first filter link is the default
        if (is_null($index)) {
            $index = 0;
            $this->setFilterLink($index);
        }
        $filters = array_values($this->filter_links);
        $filter = $filters[$index];
        // Use having so that aliases work
        $this->addHaving($filter);
    }

    /**
     * Set the session page
     */
    private function setPage(int $page): void
    {
        $this->setSession("page", $page);
    }

    /**
     * Set the session order_by and sort
     */
    private function setOrderSort(string $order_by, string $sort): void
    {
        $columns = $this->normalizeTableColumns();
        if (in_array($order_by, $columns)) {
            $this->setSession("order_by", $order_by);
            $this->setSession("sort", $sort);
        }
    }

    /**
     * Set the results per page
     */
    private function setPerPage(int $per_page): void
    {
        $this->setSession("per_page", $per_page);
        $this->setSession("page", 1);
        $this->per_page = $per_page;
        $this->page = 1;
    }

    /**
     * Set the session search term
     */
    private function setSearch(string $term): void
    {
        if (trim($term) !== "") {
            $this->setSession("search_term", $term);
        } else {
            $this->deleteSession("search_term");
        }
    }

    /**
     * Set the session search term
     */
    private function setFilterLink(int $index): void
    {
        $this->setSession("filter_link", $index);
    }

    /**
     * Get the table query
     * This is the query for module.index
     * @param bool $limit_query there exists a limit, offset clause
     */
    private function getIndexQuery(bool $limit_query = true): string
    {
        // Setup the index view columns
        $columns = $this->table_columns
            ? implode(", ", $this->table_columns)
            : "*";

        // Setup where/group_by/having clauses
        $where = $this->table_where
            ? "WHERE " . $this->formatAnd($this->table_where)
            : "";
        $group_by = $this->table_group_by
            ? "GROUP BY " . $this->table_group_by
            : "";
        $having = $this->table_having
            ? "HAVING " . $this->formatAnd($this->table_having)
            : "";

        // We may want to limit or get the full result set
        if (!$this->table_order_by) {
            $this->table_order_by = $this->primary_key;
        }
        $order_by =
            "ORDER BY " . $this->table_order_by . " " . $this->table_sort;
        $page = max(($this->page - 1) * $this->per_page, 0);
        $limit = $limit_query ? "LIMIT " . $page . ", " . $this->per_page : "";
        return sprintf(
            "SELECT %s FROM %s %s %s %s %s %s",
            ...[
                $columns,
                $this->sql_table,
                $where,
                $group_by,
                $having,
                $order_by,
                $limit,
            ]
        );
    }

    /**
     * Return module table columns
     */
    private function getTableColumns()
    {
        $columns = db()->run("DESCRIBE $this->sql_table");
        return $columns->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get the edit query
     * This is the query for module.edit
     */
    private function getEditQuery(): string
    {
        // Filter form_columns (only table columns are valid)
        $table_columns = $this->getTableColumns();
        $form_columns = array_filter($this->form_columns, fn ($column) => in_array($column, $table_columns));
        $columns = $form_columns
            ? implode(", ", $form_columns)
            : "*";
        $where = $this->table_where
            ? "WHERE " . $this->formatAnd($this->table_where)
            : "";
        return sprintf(
            "SELECT %s FROM %s %s",
            ...[$columns, $this->sql_table, $where]
        );
    }

    /**
     * Get the update query
     * This is the query for module.update
     */
    private function getUpdateQuery(array &$request): string
    {
        // Filter request (only form_columns are valid)
        $request = array_filter(
            $request,
            fn ($key) => in_array($key, $this->form_columns),
            ARRAY_FILTER_USE_KEY
        );
        // Filter request (only table columns are valid)
        $table_columns = $this->getTableColumns();
        $request = array_filter($request, fn ($column) => in_array($column, $table_columns), ARRAY_FILTER_USE_KEY);
        $map = array_map(fn ($column) => "$column = ?", array_keys($request));
        $set_stmt = "SET " . $this->formatComma($map);
        return sprintf(
            "UPDATE %s %s WHERE %s = ?",
            ...[$this->sql_table, $set_stmt, $this->primary_key]
        );
    }

    /**
     * Get the update query
     * This is the query for module.update
     */
    private function getDeleteQuery(): string
    {
        return sprintf(
            "DELETE FROM %s WHERE %s = ?",
            ...[$this->sql_table, $this->primary_key]
        );
    }

    /**
     * Get the create query
     * This is the query for module.update
     */
    private function getCreateQuery(array &$request): string
    {
        // Filter request (only form_columns are valid)
        $request = array_filter(
            $request,
            fn ($key) => in_array($key, $this->form_columns),
            ARRAY_FILTER_USE_KEY
        );
        // Filter request (only table columns are valid)
        $table_columns = $this->getTableColumns();
        $request = array_filter($request, fn ($column) => in_array($column, $table_columns), ARRAY_FILTER_USE_KEY);
        $map = array_map(fn ($column) => "$column = ?", array_keys($request));
        $set_stmt = "SET " . $this->formatComma($map);
        return sprintf("INSERT INTO %s %s", ...[$this->sql_table, $set_stmt]);
    }

    /**
     * Add a table where clause
     * Aliases cannot be used here
     */
    protected function addWhere(
        string $clause,
        int|string ...$replacements
    ): void {
        $this->table_where[] = [$clause, [...$replacements]];
    }

    /**
     * Add a table having clause
     * Aliases can be used here
     */
    protected function addHaving(
        string $clause,
        int|string ...$replacements
    ): void {
        $this->table_having[] = [$clause, [...$replacements]];
    }

    /**
     * Override a table value
     * This overrides the value BEFORE the format method
     * @param &$row current data table row.
     */
    protected function tableValueOverride(object &$row): void
    {
    }

    /**
     * Override a form value
     * This overrides the value BEFORE the control method
     * @param &$row current data table row.
     */
    protected function editValueOverride(object &$row): void
    {
    }

    /**
     * Return the column to the alias name
     */
    private function getAlias(string $column): mixed
    {
        $arr = explode(" as ", $column);
        return end($arr);
    }

    /**
     * Normalize table columns
     * to alias name
     * @return array<<missing>,mixed>
     */
    private function normalizeTableColumns(): array
    {
        $columns = [];
        foreach ($this->table_columns as $title => $column) {
            $columns[$title] = $this->getAlias($column);
        }
        return $columns;
    }

    /**
     * Return column corresponding title
     */
    private function getColumnTitle(string $column): int|string|bool
    {
        // This is annoying, but we must deal with aliases here
        $column = $this->getAlias($column);
        return array_search($column, $this->normalizeTableColumns());
    }

    /**
     * Form control method
     * Return a from control template
     */
    protected function control(string $column, mixed $value)
    {
        // Deal with form control
        if (isset($this->form_controls[$column])) {
            $control = $this->form_controls[$column];
            // Using a formatting callback
            if (is_callable($control)) {
                return $control($column, $value);
            }
            // Using a pre-defused control method (ie, controlName)
            $method_name = "control$control";
            if (method_exists($this, $method_name)) {
                return $this->$method_name($column, $value);
            }
        }
        return template("control/input.php", [
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
        ]);
    }

    /**
     * Get options for a select control
     */
    protected function getSelectOptions(string $column, mixed $value): array
    {
        return key_exists($column, $this->select_options)
            ? $this->select_options[$column]
            : [];
    }

    /**
     * Checkbox control
     */
    protected function controlCheckbox(string $column, mixed $value): string
    {
        return template("control/checkbox.php", [
            "checked" => intval($value) === 1 ? "checked" : "",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Switch (fancy checkbox) control
     */
    protected function controlSwitch(string $column, mixed $value)
    {
        return template("control/checkbox.php", [
            "checked" => intval($value) === 1 ? "checked" : "",
            "class" => "form-switch",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Select (dropdown) control
     */
    protected function controlSelect(string $column, mixed $value): string
    {
        return template("control/select.php", [
            "column" => $column,
            "value" => $value,
            "options" => $this->getSelectOptions($column, $value),
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Number control
     */
    protected function controlNumber(string $column, mixed $value): string
    {
        return template("control/input.php", [
            "type" => "number",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Date control
     */
    protected function controlDate(string $column, mixed $value): string
    {
        return template("control/input.php", [
            "type" => "date",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Time control
     */
    protected function controlTime(string $column, mixed $value): string
    {
        return template("control/input.php", [
            "type" => "time",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Date/Time control
     */
    protected function controlDateTime(string $column, mixed $value): string
    {
        return template("control/input.php", [
            "type" => "datetime-local",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Email control
     */
    protected function controlEmail(string $column, mixed $value): string
    {
        return template("control/input.php", [
            "type" => "email",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Password control
     */
    protected function controlPassword(string $column, mixed $value): string
    {
        return template("control/input.php", [
            "type" => "password",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Telephone control
     */
    protected function controlTelephone(string $column, mixed $value): string
    {
        return template("control/input.php", [
            "type" => "tel",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * URL control
     */
    protected function controlUrl(string $column, mixed $value): string
    {
        return template("control/input.php", [
            "type" => "url",
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
            "disabled" => in_array($column, $this->form_disabled),
            "readonly" => in_array($column, $this->form_readonly),
        ]);
    }

    /**
     * Template formatting function
     */
    protected function format(string $column, mixed $value): mixed
    {
        if (is_null($value)) {
            return "";
        }

        // Deal with table formatting
        if (isset($this->table_format[$column])) {
            $format = $this->table_format[$column];
            // Using a formatting callback
            if (is_callable($format)) {
                return $format($column, $value);
            }
            // Using a pre-defined format method (ie, formatName)
            $method_name = "format$format";
            if (method_exists($this, $method_name)) {
                return $this->$method_name($column, $value);
            }
        }
        return template("format/span.php", [
            "column" => $column,
            "value" => $value,
            "title" => $this->getColumnTitle($column),
        ]);
    }

    /**
     * Format IP value
     */
    protected function formatIP(string $column, mixed $value): mixed
    {
        $value = long2ip(intval($value));
        return template("format/span.php", [
            "column" => $column,
            "value" => $value,
            "title" => "IP",
        ]);
    }

    /**
     * Format check value
     */
    protected function formatCheck(string $column, mixed $value): string
    {
        return intval($value) === 1
            ? "<img class='checkmark' src='/img/green_check.png' alt='check' />"
            : "<img class='checkmark' src='/img/red_x.png' alt='red x' />";
    }

    /**
     * Format human readable timestamp as ago diff
     */
    protected function formatAgo(string $column, mixed $value): string
    {
        $carbon = Carbon::parse($value)->diffForHumans();
        return template("format/span.php", [
            "column" => $column,
            "value" => $carbon,
            "title" => $value,
        ]);
    }

    /**
     * Print a nice error to logs
     * @param array<int,mixed> $params
     */
    private function pdoException(
        string $sql,
        array $params,
        Exception $ex
    ): void {
        $out = print_r(
            [
                "sql" => $sql,
                "params" => $params,
                "message" => $ex->getMessage(),
                "file" => $ex->getFile() . ":" . $ex->getLine(),
            ],
            true
        );
        error_log($out);
    }

    /**
     * Get the total results count, without a limit or offset
     */
    private function getTotalCount(): int
    {
        if (!$this->sql_table || !$this->table_columns) {
            return 0;
        }
        $sql = $this->getIndexQuery(false);
        $where_params = $this->getParams($this->table_where);
        $having_params = $this->getParams($this->table_having);
        $params = [...$where_params, ...$having_params];
        try {
            $stmt = db()->run($sql, $params);
            return $stmt->rowCount();
        } catch (Exception $ex) {
            $this->pdoException($sql, $params, $ex);
            return 0;
        }
    }

    /**
     * Return a module record
     * @param string $id record ID
     * @return record or false
     */
    protected function getRecord(string $id)
    {
        return db()->fetch("SELECT * FROM $this->sql_table WHERE $this->primary_key = ?", $id);
    }

    /**
     * Update a record
     * @param string $id record ID
     * @param array $request validated request
     */
    public function processUpdate(string $id, array $request): mixed
    {
        if (!$this->sql_table || !$this->form_columns) {
            return [];
        }
        $sql = $this->getUpdateQuery($request);
        // Check for empty request
        if (empty($request)) {
            // There is nothing to update, so great
            return true;
        }
        // Empty string is null
        $mapped = array_map(fn ($r) => trim($r) === "" ? null : $r, $request);
        // "NULL" is null
        $mapped = array_map(fn ($r) => $r === "NULL" ? null : $r, $mapped);
        $params = [...array_values($mapped), $id];
        try {
            $old = $this->getRecord($id);
            $result = db()->query($sql, ...$params);
            if ($result) {
                // Audit the updated record
                foreach ($mapped as $field => $value) {
                    if ($old->$field != $value) {
                        audit($this->sql_table, $id, $field, user(), $old->$field, $value, "UPDATE");
                    }
                }
            }
            return $result;
        } catch (Exception $ex) {
            $this->pdoException($sql, $params, $ex);
            return null;
        }
    }

    /**
     * Create a new record
     * @param array $request validated request
     */
    public function processCreate(array $request): mixed
    {
        if (!$this->sql_table || !$this->form_columns) {
            return [];
        }
        $sql = $this->getCreateQuery($request);
        // Empty string is null
        $mapped = array_map(fn ($r) => trim($r) === "" ? null : $r, $request);
        // "NULL" is null
        $mapped = array_map(fn ($r) => $r === "NULL" ? null : $r, $mapped);
        $params = array_values($mapped);
        try {
            $result = db()->query($sql, ...$params);
            $new_id = $result ? db()->lastInsertId() : null;
            if ($result) {
                // Audit the newly created record
                foreach ($mapped as $field => $value) {
                    audit($this->sql_table, $new_id, $field, user(), null, $value, "CREATE");
                }
            }
            return $new_id;
        } catch (Exception $ex) {
            $this->pdoException($sql, $params, $ex);
            return null;
        }
    }

    /**
     * Delete a new record
     * @param array $request validated request
     */
    public function processDestroy(string $id): mixed
    {
        if (!$this->sql_table || !$this->form_columns) {
            return [];
        }
        $sql = $this->getDeleteQuery();
        $params = [$id];
        try {
            $result = db()->query($sql, ...$params);
            if ($result) {
                // Audit the deleted record
                audit($this->sql_table, $id, $this->primary_key, user(), $id, null, "DELETE");
            }
            return $result;
        } catch (Exception $ex) {
            $this->pdoException($sql, $params, $ex);
            return null;
        }
    }

    /**
     * Run the index query and return the table dataset
     * This dataset is for module.index
     */
    protected function getTableData(): array|bool
    {
        if (!$this->sql_table || !$this->table_columns) {
            return [];
        }
        $sql = $this->getIndexQuery();
        $where_params = $this->getParams($this->table_where);
        $having_params = $this->getParams($this->table_having);
        $params = [...$where_params, ...$having_params];
        try {
            $stmt = db()->run($sql, $params);
            $results = $stmt->fetchAll();
            foreach ($results as $data) {
                $this->tableValueOverride($data);
            }
            return $results;
        } catch (Exception $ex) {
            $this->pdoException($sql, $params, $ex);
            return [];
        }
    }

    /**
     * Return data for edit view
     */
    protected function getEditFormData(string $id): ?array
    {
        if (!$this->sql_table || !$this->form_columns) {
            return [];
        }
        $this->addWhere("{$this->primary_key} = ?", $id);
        $sql = $this->getEditQuery();
        $params = $this->getParams($this->table_where);
        try {
            $stmt = db()->run($sql, $params);
            $result = $stmt->fetch();
            $map = array_map(
                function ($title, $column, $value) {
                    $row = (object) [
                        "title" => $title,
                        "column" => $column,
                        "value" => $value,
                    ];
                    $this->editValueOverride($row);
                    return $row;
                },
                array_keys($this->form_columns),
                array_values($this->form_columns),
                (array) $result
            );
            return $map;
        } catch (Exception $ex) {
            $this->pdoException($sql, $params, $ex);
            return null;
        }
    }

    /**
     * Return data for create view
     */
    protected function getCreateFormData(): ?array
    {
        if (!$this->sql_table || !$this->form_columns) {
            return [];
        }
        $sql = $this->getEditQuery();
        $params = $this->getParams($this->table_where);
        try {
            $stmt = db()->run($sql, $params);
            $result = $stmt->fetch();
            $map = array_map(
                function ($title, $column, $value) {
                    $row = (object) [
                        "title" => $title,
                        "column" => $column,
                        "value" => $value,
                    ];
                    $this->editValueOverride($row);
                    return $row;
                },
                array_keys($this->form_columns),
                array_values($this->form_columns),
                (array) $result
            );
            return $map;
        } catch (Exception $ex) {
            $this->pdoException($sql, $params, $ex);
            return null;
        }
    }

    /**
     * Return index template path
     * Child module may override
     */
    protected function getIndexTemplate(): string
    {
        return "module/index/index.php";
    }

    /**
     * Return index template data
     */
    protected function getIndexData(): array
    {
        $path = $this->path;
        $format = function (string $column, mixed $value) {
            return $this->format($column, $value);
        };
        $has_row_edit = function (string $id) {
            return $this->hasEditPermission($id);
        };
        $has_row_delete = function (string $id) {
            return $this->hasDeletePermission($id);
        };
        $search_term = $this->getSession("search_term");
        $filter_link = $this->getSession("filter_link");
        return [
            "module" => $path,
            "actions" => [
                "show_create_action" => $this->hasCreatePermission(),
                "show_export_action" => $this->export_csv,
            ],
            "filters" => [
                "search" => template("module/index/search.php", [
                    "show" => !empty($this->search_columns),
                    "term" => $search_term,
                ]),
                "link" => template("module/index/filter_links.php", [
                    "action" => "/admin/$path/link-count",
                    "show" => !empty($this->filter_links),
                    "current" => $filter_link,
                    "links" => array_keys($this->filter_links),
                ]),
            ],
            "table" => template("module/index/table.php", [
                "module" => $path,
                "primary_key" => $this->primary_key,
                "link_column" => $this->link_column,
                "columns" => $this->normalizeTableColumns(),
                "data" => $this->getTableData(),
                "order_by" => $this->table_order_by,
                "sort" => $this->table_sort,
                "show_row_actions" => $this->row_actions,
                "show_row_edit" => $has_row_edit,
                "show_row_delete" => $has_row_delete,
                "format" => $format,
            ]),
            "pagination" => template("module/index/pagination.php", [
                "show" =>
                $this->per_page > $this->total_results ||
                    $this->total_pages > 1,
                "current_page" => $this->page,
                "total_results" => $this->total_results,
                "total_pages" => $this->total_pages,
                "per_page" => $this->per_page,
                "per_page_options" => array_filter(
                    $this->per_page_options,
                    fn ($value) => $value <= $this->total_results
                ),
                "side_links" => $this->side_links,
            ]),
        ];
    }

    /**
     * Return edit template path
     * Child module may override
     */
    protected function getEditTemplate(): string
    {
        return "module/edit/index.php";
    }

    /**
     * Return edit template data
     */
    protected function getEditData(string $id): array
    {
        $path = $this->path;
        $request_errors = fn (
            string $field
        ) => $this->controller->getRequestError($field);
        $has_errors = fn (string $field) => $this->controller->hasRequestError(
            $field
        );
        $control = function (string $column, mixed $value) {
            return $this->control($column, $value);
        };
        $old = fn (string $column, mixed $value) => $this->controller->request(
            $column,
            $value
        );
        return [
            "id" => $id,
            "form" => template("module/edit/form.php", [
                "control" => $control,
                "data" => $this->getEditFormData($id),
                "module" => $path,
                "request_errors" => $request_errors,
                "has_errors" => $has_errors,
                "old" => $old,
            ]),
        ];
    }

    /**
     * Return create template path
     * Child module may override
     */
    protected function getCreateTemplate(): string
    {
        return "module/create/index.php";
    }

    /**
     * Return create template data
     */
    protected function getCreateData(): array
    {
        $path = $this->path;
        $request_errors = fn (
            string $field
        ) => $this->controller->getRequestError($field);
        $has_errors = fn (string $field) => $this->controller->hasRequestError(
            $field
        );
        $control = function (string $column, mixed $value) {
            return $this->control($column, $value);
        };
        $old = fn (string $column, mixed $value) => $this->controller->request(
            $column,
            $value
        );
        return [
            "form" => template("module/create/form.php", [
                "control" => $control,
                "data" => $this->getCreateFormData(),
                "module" => $path,
                "request_errors" => $request_errors,
                "has_errors" => $has_errors,
                "old" => $old,
            ]),
        ];
    }

    /**
     * Render the view module.index
     */
    public function viewIndex(): string
    {
        $this->filters();
        return template($this->getIndexTemplate(), $this->getIndexData());
    }

    /**
     * Render the view module.edit
     * @param string $id record ID
     */
    public function viewEdit(string $id): string
    {
        return template($this->getEditTemplate(), $this->getEditData($id));
    }

    /**
     * Render the view module.create
     */
    public function viewCreate(): string
    {
        return template($this->getCreateTemplate(), $this->getCreateData());
    }
}
