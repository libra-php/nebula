<?php

namespace Nebula\Traits\Admin;

use Nebula\Admin\FormControls;
use Nebula\Admin\TableFormat;
use Nebula\Database\QueryBuilder;

trait ModuleTable
{
    // Index  settings
    /** Create record enabled */
    protected bool $table_create = true;
    /** Edit record enabled */
    protected bool $table_edit = true;
    /** Destroy record enabled */
    protected bool $table_destroy = true;
    /** Name column (used for table link to edit) */
    protected string $name_col = "name";
    /** Export CSV enabled */
    protected bool $export_csv = true;
    /** Table query columns */
    protected array $table_columns = [];
    /** Table data array */
    protected array $table_data = [];
    /** Table formatting options */
    protected array $table_format = [];
    /** Table row actions */
    protected array $row_actions = [];
    /** Table query joins */
    protected array $joins = [];
    /** Table query where clause */
    protected array $where = [];
    /** Table query order by clause */
    protected string $order_by = "";
    /** Table query sort direction */
    protected string $sort = "DESC";
    /** Current page */
    protected int $page = 1;
    /** Total result count */
    protected int $total_results = 0;
    /** Total pages count */
    protected int $total_pages = 1;
    /** Table query limit clause */
    protected int $limit = 10;
    /** Table query offset */
    protected ?int $offset = null;

    // Filters
    /** Filter accordion expanded on render flag */
    protected bool $expand_filters = false;
    /** Search columns array */
    protected array $search = [];
    /** Current search term */
    protected string $search_term = "";
    /** Dropdown options array */
    protected array $select_options = [];
    /** Dropdown filter */
    protected array $filter_select = [];
    /** Holds filter selections */
    protected array $filter_selections = [];
    /** Filter datetime column */
    protected string $filter_datetime = "";
    /** Current ilter datetime (from) */
    protected string $filter_date_from = "";
    /** Current ilter datetime (to) */
    protected string $filter_date_to = "";
    /** Filter link query array */
    protected array $filter_links = [];
    /** Current filter link (active) */
    protected string $filter_link = "";

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
     * Add a row action to the table
     */
    protected function addRowAction(
        string $name,
        string $title,
        string $label,
        ?string $confirm = null,
        string $class = "primary",
        ...$attrs
    ): void {
        $this->row_actions[] = [
            "name" => $name,
            "title" => $title,
            "label" => $label,
            "confirm" => $confirm,
            "class" => $class,
            "attrs" => $attrs
        ];
    }

    /**
     * Handle exporting to csv
     */
    protected function handleExportCsv(): void
    {
        if (request()->has("export_csv")) {
            $name = sprintf("%s_export_%s.csv", $this->module_name, time());
            header("Content-Type: text/csv");
            header(
                sprintf('Content-Disposition: attachment; filename="%s"', $name)
            );
            $fp = fopen("php://output", "wb");
            $csv_headers = $skip = [];
            $columns = $this->columnAlias($this->table_columns);
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
     * Does the current user have delete permission?
     * This method is overrideable
     */
    protected function hasDeletePermission(string $id): bool
    {
        return $this->table_destroy;
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
     * Override method for rendering custom content
     * Custom content is rendered if table_name is null
     */
    protected function customContent(): string
    {
        return "";
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
    protected function columnAlias(array $columns): array
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
            fn ($value) => !is_null($value)
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

        $has_delete_permission = fn (string $id) => $this->hasDeletePermission(
            $id
        );
        $has_edit_permission = fn (string $id) => $this->hasEditPermission($id);
        $has_create_permission = fn () => $this->hasCreatePermission();
        $has_row_action_permission = fn (
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
                "columns" => $this->columnAlias($this->table_columns),
                "col_span" => count($this->table_columns) + 1,
                "row_actions" => $this->row_actions,
                "has_row_action_permission" => $has_row_action_permission,
            ],
        ];
    }

}
