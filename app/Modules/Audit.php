<?php

namespace App\Modules;

use Nebula\Alerts\Flash;
use Nebula\Backend\Module;

class Audit extends Module
{
    public function __construct()
    {
        $this->table_create = $this->table_edit = $this->table_destroy = false;
        $this->name_col = "id";
        $this->table_columns = [
            "audit.id" => "ID",
            "users.name" => "User",
            "audit.table_name" => "Table",
            "audit.table_id" => "ID",
            "audit.field" => "Field",
            "ifnull(audit.old_value, 'NULL') as old_value" => "Old",
            "'🠮' as sep" => "",
            "ifnull(audit.new_value, 'NULL') as new_value" => "New",
            "audit.message" => "Message",
            "audit.created_at" => "Created At",
        ];
        $this->joins = ["INNER JOIN users ON audit.user_id = users.id"];
        $this->search = ["table_name", "table_id", "field", "name"];
        $this->filter_datetime = "created_at";
        $this->filter_links = [
            "Me" => "user_id = " . user()->id,
            "Others" => "user_id != " . user()->id,
            "All" => "1=1",
        ];
        $this->filter_select = [
            "users.name" => "User",
            "table_name" => "Table",
        ];
        $this->select_options = [
            "users.name" => db()->selectAll(
                "SELECT name as id, concat(name, ' (', email, ')') as name FROM users ORDER BY name"
            ),
            "table_name" => db()->selectAll(
                "SELECT distinct table_name as id, table_name as name FROM audit ORDER BY table_name"
            ),
        ];
        $this->addRowAction(
            "undo_change",
            "Undo",
            "Are you sure you want to restore this value?"
        );
        parent::__construct("audit");
    }

    protected function hasRowActionPermission(string $name, string $id): bool
    {
        $row = db()->select(
            "SELECT * FROM $this->table_name WHERE $this->key_col = ?",
            $id
        );
        if (in_array($row->message, ["UPDATE", "UNDO"])) {
            return true;
        }
        return false;
    }

    protected function processTableRequest(): void
    {
        parent::processTableRequest();
        if (request()->has("undo_change")) {
            $audit_row = db()->select("SELECT * FROM audit WHERE id = ?", request()->id);
            $result = db()->query(
                "UPDATE $audit_row->table_name SET $audit_row->field = ? WHERE id = ?",
                $audit_row->old_value,
                $audit_row->table_id
            );
            if ($result) {
                Flash::addFlash("success", "Old value restored successfully");
                $this->audit(
                    user()->id,
                    $audit_row->table_name,
                    $audit_row->table_id,
                    $audit_row->field,
                    $audit_row->old_value,
                    "UNDO"
                );
            } else {
                Flash::addFlash(
                    "warning",
                    "Oops! Couldn't undo change for this record"
                );
            }
            request()->remove("undo_change");
            echo $this->indexPartial();
            die();
            exit();
        }
    }
}
