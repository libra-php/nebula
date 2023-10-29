<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Audit extends Module
{
    public function __construct()
    {
        $this->module_icon = "book-open";
        $this->table_create = $this->table_edit = $this->table_destroy = false;
        $this->key_col = "id";
        $this->name_col = "id";
        $this->table_columns = [
            "audit.id" => "ID",
            "users.name" => "User",
            "audit.table_name" => "Table",
            "audit.table_id" => "ID",
            "audit.field" => "Field",
            "CONCAT(audit.old_value, ' => ', audit.new_value) AS audit_change" => "Change",
            "audit.message" => "Message",
        ];
        $this->joins = [
            "INNER JOIN users ON audit.user_id = users.id"
        ];
        $this->search = [
            "table_name",
            "table_id",
            "field",
            "name"
        ];
        $this->filter_links = [
            "Me" => "user_id = " . user()->id,
            "All" => "1=1",
        ];
        parent::__construct("audit", "audit");
    }
}

