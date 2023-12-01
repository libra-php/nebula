<?php

namespace Nebula\Traits\Admin;

use Nebula\Admin\FormControls;
use Nebula\Alerts\Flash;
use Nebula\Database\QueryBuilder;
use PDO;

trait ModuleForm
{
    // Form (shared by edit & create)
    /** Form query columns */
    protected array $form_columns = [];
    /** Form control options */
    protected array $form_controls = [];
    /** Form default values */
    protected array $form_defaults = [];
    /** Form data aray */
    protected array $form_data = [];
    /** Form actions */
    protected array $form_actions = [];
    /** Valid file extensions */
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
    /** Valid image extensions */
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

    /**
     * Process request for edit / create views
     */
    protected function processFormRequest(?string $id = null): void
    {
        $this->handleDeleteFile($id);
        $this->handleSession();
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
     * Return the template used for edit
     */
    protected function getEditTemplate(): string
    {
        return "admin/edit.latte";
    }

    /**
     * Return the template used for create
     */
    protected function getCreateTemplate(): string
    {
        return "admin/create.latte";
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
            exit;
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
     * Add an action to the form
     */
    protected function addFormAction(
        string $name,
        string $title,
        string $label,
        ?string $confirm = null,
        string $class = "primary",
        ...$attrs
    ): void {
        $this->form_actions[] = [
            "name" => $name,
            "title" => $title,
            "label" => $label,
            "confirm" => $confirm,
            "class" => $class,
            "attrs" => $attrs
        ];
    }

    /**
     * Filter out columns that should not be used in the
     * request for creation of / updating a record.
     */
    protected function getFilteredFormColumns(): array
    {
        $data = request()->data();
        // Only real table columns are considered
        $table_columns = db()
            ->query("DESCRIBE $this->table_name")
            ->fetchAll(PDO::FETCH_COLUMN);
        $data = array_filter($data, fn ($value, $key) => is_string($value) && in_array($key, $table_columns), ARRAY_FILTER_USE_BOTH);
        // Deal with "null" string
        array_walk(
            $data,
            fn (&$value, $key) => ($value =
                is_string($value) && strtolower($value) === "null"
                ? null
                : $value)
        );
        return $data;
    }

	/**
     * Return the edit QueryBuilder
     */
    protected function getEditQuery(string $id): QueryBuilder
    {
        $columns = $this->getFilteredFormColumns();
        $qb = QueryBuilder::select($this->table_name)
            ->columns(array_keys($columns))
            ->where([$this->key_col, $id]);

        return $qb;
    }

    /**
     * Returns an array of all form_columns that are required
     */
    protected function getRequiredForm(): array
    {
        return array_keys(
            array_filter(
                $this->validation,
                fn ($rules) => in_array("required", $rules)
            )
        );
    }

    /**
     * Override data before storing in db
     */
    protected function storeOverride(array $data): array
    {
        return $data;
    }

    /**
     * Override data before updating db
     */
    protected function updateOverride(array $data): array
    {
        return $data;
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
                "password" => $fc->input($column, $value, "password"),
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
        // Remember request values
        $columns = $this->getFilteredFormColumns();
        foreach ($columns as $column => $value) {
            $this->form_defaults[$column] = $value;
        }
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
}
