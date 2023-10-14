<?php

namespace Nebula\Backend;

use Nebula\Alerts\Flash;
use Nebula\Database\QueryBuilder;
use Nebula\Traits\Http\Response;
use Nebula\Validation\Validate;
use PDOException;

class Module
{
    use Response;

    protected string $module_name;
    protected string $module_title;
    protected ?string $table_name;
    protected array $validation = [];
    protected array $errors = [];
    protected array $table_columns = [];
    protected array $table_data = [];
    protected array $form_columns = [];
    protected array $form_data = [];
    protected bool $table_view = true;
    protected bool $edit_view = true;
    protected bool $create_view = true;

    public function __construct(string $module_name, ?string $table_name = null)
    {
        $this->module_name = $module_name;
        $this->module_title = ucfirst($module_name);
        $this->table_name = $table_name;
    }

    public function getModuleName(): string
    {
        return $this->module_name;
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }

    private function moduleNotFound(): never
    {
        Flash::addFlash(
            "warning",
            "Oops! The requested record could not be found"
        );
        echo $this->response(404, latte("backend/alert.latte"))->send();
        die();
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

    public function store(): string
    {
        if ($this->validate($this->validation)) {
            $qb = QueryBuilder::insert($this->table_name)->columns(
                request()->data()
            );
            try {
                $result = db()->run($qb->build(), $qb->values());
                if ($result) {
                    Flash::addFlash("success", "Record created successfully");
                } else {
                    Flash::addFlash(
                        "danger",
                        "Oops! An unknown issue occurred while creating new record"
                    );
                }
            } catch (PDOException $ex) {
                Flash::addFlash(
                    "database",
                    "Oops! A database error occurred while creating new record"
                );
            }
        }
        return $this->createPartial();
    }

    public function update(string $id): string
    {
        if ($this->validate($this->validation)) {
            $qb = QueryBuilder::update($this->table_name)
                ->columns(request()->data())
                ->where(["id", $id]);
            try {
                $result = db()->run($qb->build(), $qb->values());
                if ($result) {
                    Flash::addFlash("success", "Record updated successfully");
                } else {
                    Flash::addFlash(
                        "danger",
                        "Oops! An unknown issue occurred while updating record"
                    );
                }
            } catch (PDOException $ex) {
                Flash::addFlash(
                    "database",
                    "Oops! A database error occurred while updating new record"
                );
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
            Flash::addFlash(
                "database",
                "Oops! A database error occurred while deleting record"
            );
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
        $route = function(string $route_name, ?string $id = null) {
            return moduleRoute($route_name, $this->module_name, $id);
        };
        $moduleRoute = function(string $route_name, string $module_name, ?string $id = null) {
            return moduleRoute($route_name, $module_name, $id);
        };
        $gravatar = fn(string $str) => md5( strtolower( trim( $str ) ) );;
        $singular = function(string $str) {
            return substr($str, -1) === 's'
                ? rtrim($str, 's')
                : $str;
        };
        return [
            "has_flash" => Flash::hasFlash(),
            "gravatar" => $gravatar,
            "route" => $route,
            "moduleRoute" => $moduleRoute,
            "route_name" => request()->route->getName(),
            "module_name" => $this->module_name,
            "module_title" => $this->module_title,
            "module_title_singular" => $singular($this->module_title),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function getIndexData(): array
    {
        $qb = $this->getIndexQuery();
        try {
            $data = !is_null($qb)
                ? db()
                    ->run($qb->build(), $qb->values())
                    ->fetchAll()
                : [];
        } catch (PDOException) {
            $data = [];
            Flash::addFlash(
                "database",
                "Oops! A database error occurred while selecting record(s)"
            );
        }

        return [
            ...$this->commonData(),
            "table" => [
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
        return [
            ...$this->commonData(),
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
        $qb = $this->getEditQuery($id);
        try {
            $data = !is_null($qb)
                ? db()
                    ->run($qb->build(), $qb->values())
                    ->fetch()
                : [];
        } catch (PDOException) {
            $data = [];
            Flash::addFlash(
                "database",
                "Oops! A database error occurred while selecting record(s)"
            );
        }
        if (!$data) {
            $this->moduleNotFound();
        }

        return [
            ...$this->commonData(),
            "id" => $id,
            "form" => [
                "data" => $data,
                "columns" => $this->form_columns,
            ],
        ];
    }
}
