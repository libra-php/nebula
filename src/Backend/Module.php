<?php

namespace Nebula\Backend;

use Nebula\Alerts\Flash;
use Nebula\Database\QueryBuilder;
use Nebula\Traits\Http\Response;
use Nebula\Validation\Validate;
use Nebula\Backend\FormControls;
use PDOException;

class Module
{
    use Response;

    /** Module */
    protected string $module_name;
    protected string $module_title;
    protected ?string $table_name;
    /** Form */
    protected bool $edit_view = true;
    protected bool $create_view = true;
    protected array $form_columns = [];
    protected array $form_data = [];
    protected array $file_extensions = [".pdf", ".doc", ".docx"];
    /** Controls **/
    protected array $form_controls = [];
    protected array $select_options = [];
    /** Table */
    protected bool $table_view = true;
    protected array $table_columns = [];
    protected array $table_data = [];
    protected array $search = [];
    protected array $where = [];
    /** Pagination */
    protected int $page = 1;
    protected int $total_results = 0;
    protected int $total_pages = 1;
    protected int $limit = 10;
    protected ?int $offset = null;
    /** Validation */
    protected array $validation = [];
    protected array $errors = [];

    public function __construct(string $module_name, ?string $table_name = null)
    {
        $this->module_name = $module_name;
        $this->module_title = ucfirst($module_name);
        $this->table_name = $table_name;
    }

    protected function processTableRequest()
    {
        $this->pagination();
        $this->search();
    }

    protected function processFormRequest(?string $id = null)
    {
        $this->handleDeleteFile($id);
    }

    protected function handleUpload(string $id): bool
    {
        foreach (request()->files() as $column => $file) {
            $timestamp = time();
            $random = md5(uniqid());
            $filename = $file['name'];
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $new_filename = sprintf("%s_%s.%s", $timestamp, $random, $extension);
            $uploads_path = config("paths.uploads");
            $target_path = $uploads_path . $new_filename;
            if (!file_exists($target_path) && move_uploaded_file($file['tmp_name'], $target_path)) {
                $this->deleteColumnFile($column, $id);
                $qb = QueryBuilder::update($this->table_name)
                    ->columns([$column => $target_path])
                    ->where(["id", $id]);
                if (is_null(db()->run($qb->build(), $qb->values()))) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function handleDeleteFile(?string $id)
    {
        if (request()->has("delete_file")) {
            $column = request()->delete_file;
            $this->deleteColumnFile($column, $id);
            $qb = QueryBuilder::update($this->table_name)
                ->columns([$column => null])
                ->where(["id", $id]);
            if (is_null(db()->run($qb->build(), $qb->values()))) {
                Flash::addFlash(
                    "danger",
                    "Oops! An unknown issue occurred while deleting file"
                );
            }
            redirectModule("module.edit", $this->module_name, $id);
        }
    }

    protected function deleteColumnFile($column, $id)
    {
        $row = db()->select("SELECT $column FROM $this->table_name WHERE id = ?", $id);
        if ($row->$column && file_exists($row->$column)) {
            unlink($row->$column);
        }
    }

    protected function pagination(): void
    {
        $page = $this->page;
        if (request()->has("page")) {
            session()->set($this->module_name . "_page", intval(request()->page));
        }

        $this->page = session()->get($this->module_name . "_page") ?? $page;
    }

    protected function search(): void
    {
        $where = [];
        if (request()->has('search') && trim(request()->search) != '') {
            $term = trim(request()->search);
            foreach ($this->search as $column) {
                // Search where column like search term
                $where[] = "($column LIKE '$term%')";
            }
            session()->set($this->module_name . "_term", $term);
            session()->set($this->module_name . "_search", [implode(" OR ", $where)]);
            session()->set($this->module_name . "_page", 1);
        } else if (request()->has('search') && trim(request()->search) == '') {
            session()->remove($this->module_name . "_term");
            session()->remove($this->module_name . "_search");
            session()->remove($this->module_name . "_page");
        }

        if (session()->has($this->module_name . "_search")) {
            $this->where[] = session()->get($this->module_name . "_search");
        }
    }

    public function getModuleName(): string
    {
        return $this->module_name;
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }

    public function moduleNotFound(): never
    {
        Flash::addFlash(
            "warning",
            "Oops! The requested record could not be found"
        );
        echo $this->indexPartial();
        exit();
    }

    protected function getIndexTemplate(): string
    {
        return "backend/index.latte";
    }

    protected function getEditTemplate(): string
    {
        return "backend/edit.latte";
    }

    protected function getCreateTemplate(): string
    {
        return "backend/create.latte";
    }

    public function index(): string
    {
        return latte($this->getIndexTemplate(), $this->getIndexData());
    }

    public function indexPartial(): string
    {
        return latte(
            $this->getIndexTemplate(),
            $this->getIndexData(),
            "content"
        );
    }

    public function edit(string $id): string
    {
        return latte($this->getEditTemplate(), $this->getEditData($id));
    }

    public function editPartial(string $id): string
    {
        return latte(
            $this->getEditTemplate(),
            $this->getEditData($id),
            "content"
        );
    }

    public function create(): string
    {
        return latte($this->getCreateTemplate(), $this->getCreateData());
    }

    public function createPartial(): string
    {
        return latte(
            $this->getCreateTemplate(),
            $this->getCreateData(),
            "content"
        );
    }

    protected function handleDatabaseException(PDOException $ex)
    {
        if (config('app.debug')) {
            Flash::addFlash(
                "database",
                $ex->getMessage()
            );
        } else {
            logger('error', $ex->getMessage(), sprintf("%s[%s]", $ex->getFile(), $ex->getLine()));
            Flash::addFlash(
                "database",
                "Oops! A database error occurred"
            );
        }
        echo $this->indexPartial();
        exit();
    }

    protected function getFilteredFormColumns(): array
    {
        $filtered_controls = ["upload"];
        return array_filter(request()->data(), fn ($value, $key) => $key != 'csrf_token' && !in_array($this->form_controls[$key], $filtered_controls), ARRAY_FILTER_USE_BOTH);
    }

    public function store(): string
    {
        if ($this->validate($this->validation)) {
            $columns = $this->getFilteredFormColumns();
            $qb = QueryBuilder::insert($this->table_name)->columns(
                $columns
            );
            try {
                $result = (bool)db()->run($qb->build(), $qb->values());
                $id = db()->lastInsertId();
                if (request()->files()) {
                    $result &= $this->handleUpload($id);
                }
                if ($result) {
                    Flash::addFlash("success", "Record created successfully");
                } else {
                    Flash::addFlash(
                        "danger",
                        "Oops! An unknown issue occurred while creating new record"
                    );
                }
            } catch (PDOException $ex) {
                $this->handleDatabaseException($ex);
            }
        }
        return $this->createPartial();
    }

    public function update(string $id): string
    {
        if ($this->validate($this->validation)) {
            $columns = $this->getFilteredFormColumns();
            $qb = QueryBuilder::update($this->table_name)
                ->columns($columns)
                ->where(["id", $id]);
            try {
                $result = (bool)db()->run($qb->build(), $qb->values());
                if (request()->files()) {
                    $result &= $this->handleUpload($id);
                }
                if ($result) {
                    Flash::addFlash("success", "Record updated successfully");
                } else {
                    Flash::addFlash(
                        "danger",
                        "Oops! An unknown issue occurred while updating record"
                    );
                }
            } catch (PDOException $ex) {
                $this->handleDatabaseException($ex);
            }
        }
        return $this->editPartial($id);
    }

    public function destroy(string $id): string
    {
        $qb = QueryBuilder::delete($this->table_name)->where(["id", $id]);
        try {
            $result = db()->run($qb->build(), $qb->values());
            if ($result) {
                Flash::addFlash("success", "Record deleted successfully");
            } else {
                Flash::addFlash(
                    "danger",
                    "Oops! An unknown issue occurred while deleting record"
                );
            }
        } catch (PDOException $ex) {
            $this->handleDatabaseException($ex);
        }
        return $this->indexPartial();
    }

    /**
     * @param array<int,mixed> $rules
     */
    protected function validate(array $rules): bool
    {
        $result = Validate::request($rules);
        $this->errors = Validate::$errors;
        return $result;
    }

    protected function getIndexQuery(): ?QueryBuilder
    {
        if (is_null($this->table_name)) {
            return null;
        }
        $qb = QueryBuilder::select($this->table_name)->columns(
            array_keys($this->table_columns)
        );
        if (!empty($this->where)) {
            $qb->where(...$this->where);
        }
        if (!is_null($this->offset)) {
            $qb->limit($this->limit)->offset($this->offset);
        }
        return $qb;
    }

    protected function getEditQuery(string $id): QueryBuilder
    {
        $qb = QueryBuilder::select($this->table_name)
            ->columns(array_keys($this->form_columns))
            ->where(["id", $id]);

        return $qb;
    }

    public function commonData(): array
    {
        $route = function (string $route_name, ?string $id = null) {
            return moduleRoute($route_name, $this->module_name, $id);
        };
        $moduleRoute = function (string $route_name, string $module_name, ?string $id = null) {
            return moduleRoute($route_name, $module_name, $id);
        };
        $gravatar = fn (string $str) => md5(strtolower(trim($str)));;
        $singular = function (string $str) {
            return substr($str, -1) === 's'
                ? rtrim($str, 's')
                : $str;
        };
        $request = fn (string $column) => request()->has($column) ? request()->$column : '';
        $session = fn (string $column) => session()->has($column) ? session()->get($column) : '';
        return [
            "has_flash" => Flash::hasFlash(),
            "request" => $request,
            "session" => $session,
            "gravatar" => $gravatar,
            "route" => $route,
            "moduleRoute" => $moduleRoute,
            "route_name" => request()->route->getName(),
            "module_name" => $this->module_name,
            "module_title" => $this->module_title,
            "module_title_singular" => $singular($this->module_title),
        ];
    }

    protected function formControls($id = null)
    {
        $controls = function ($name, $value, ...$args) use ($id) {
            $fc = new FormControls();
            if (!isset($this->form_controls[$name])) {
                return $fc->plain($name, $value);
            }
            if (is_callable($this->form_controls[$name])) {
                return $this->form_controls[$name]($name, $value, ...$args);
            }
            return match ($this->form_controls[$name]) {
                "text" => $fc->input($name, $value, 'text'),
                "textarea" => $fc->textarea($name, $value),
                "disabled" => $fc->input($name, $value, 'text', 'disabled=true'),
                "readonly" => $fc->input($name, $value, 'text', 'readonly'),
                "plain" => $fc->plain($name, $value),
                "select" => $fc->select($name, $value, isset($this->select_options[$name]) ? $this->select_options[$name] : []),
                "number" => $fc->input($name, $value, 'number'),
                "color" => $fc->input($name, $value, 'color'),
                "upload" => $fc->file($name, $value, sprintf('accept="%s"', implode(", ", $this->file_extensions))),
                default => $fc->plain($name, $value),
            };
        };
        return $controls;
    }

    protected function tableData()
    {
        $data = [];
        $qb = $this->getIndexQuery();
        if (!is_null($qb)) {
            $stmt = db()->run($qb->build(), $qb->values());
            $this->total_results = $stmt?->rowCount() ?? 0;
            $this->total_pages = ceil($this->total_results / $this->limit);
            if ($this->page > $this->total_pages) {
                $this->page = $this->total_pages;
            }
            if ($this->page < 1) {
                $this->page = 1;
            }
            $this->offset = ($this->page - 1) * $this->limit;
            $qb = $this->getIndexQuery();
            $data = db()->run($qb->build(), $qb->values())->fetchAll();
        }
        return $data;
    }


    /**
     * @return array<string,mixed>
     */
    protected function getIndexData(): array
    {
        $this->processTableRequest();
        try {
            $data = $this->tableData();
        } catch (PDOException $ex) {
            $this->handleDatabaseException($ex);
        }

        return [
            ...$this->commonData(),
            "has_search" => !empty($this->search),
            "table" => [
                "total_results" => $this->total_results,
                "total_pages" => $this->total_pages,
                "page" => $this->page,
                "data" => $data,
                "columns" => $this->table_columns,
                "col_span" => count($this->table_columns) + 1,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function getCreateData(): array
    {
        $this->processFormRequest();
        $fc = $this->formControls();
        return [
            ...$this->commonData(),
            "controls" => $fc,
            "form" => [
                "data" => [],
                "columns" => $this->form_columns,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function getEditData(string $id): array
    {
        $this->processFormRequest($id);
        $qb = $this->getEditQuery($id);
        $data = null;
        try {
            $data = !is_null($qb)
                ? db()
                ->run($qb->build(), $qb->values())
                ->fetch()
                : [];
        } catch (PDOException $ex) {
            $this->handleDatabaseException($ex);
        }
        if (!$data) {
            $this->moduleNotFound();
        }
        $fc = $this->formControls($id);

        return [
            ...$this->commonData(),
            "id" => $id,
            "controls" => $fc,
            "form" => [
                "data" => $data,
                "columns" => $this->form_columns,
            ],
        ];
    }
}
