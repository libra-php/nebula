<?php

namespace Nebula\Admin;

use Nebula\Alerts\Flash;
use Nebula\Database\QueryBuilder;
use Nebula\Traits\Http\Response;
use Nebula\Traits\Admin\{ModuleCommon, ModuleForm, ModuleTable};

class Module
{
    use Response;
    /** Shared properties / methods */
    use ModuleCommon;
    /** Table properties / methods */
    use ModuleTable;
    /** Form properties / methods */
    use ModuleForm;

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
            $columns = $this->storeOverride($columns);
            $qb = QueryBuilder::insert($this->table_name)->columns($columns);
            $result = (bool) db()->run($qb->build(), $qb->values());
            $id = db()->lastInsertId();
            if ($id && request()->files()) {
                $result &= $this->handleUpload($id);
            }
            if ($result) {
                $this->auditColumns($columns, $id, "INSERT");
                Flash::addFlash("success", "Record created successfully");
                // Redirect to edit
                redirectModule("module.edit", $this->module_name, $id);
                exit;
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
            $columns = $this->updateOverride($columns);
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
                // Redirect to edit
                redirectModule("module.edit", $this->module_name, $id);
                exit;
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
