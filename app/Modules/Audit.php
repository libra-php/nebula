<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Audit extends Module
{
    public function __construct()
    {
        $this->table_create = $this->table_edit = $this->table_destroy = false;
        $this->name_col = "id";
        $this->table_columns = [
            "id" => "ID",
            "user_id" => "User",
            "table_name" => "Table",
            "table_id" => "ID",
            "field" => "Field",
            "old_value" => "Old Value",
            "new_value" => "New Value",
            "message" => "Message",
        ];
        $this->search = [
            "table_name",
            "table_id",
            "field",
        ];
        $this->filter_links = [
            "Me" => "user_id = " . user()->id,
            "All" => "1=1",
        ];
        parent::__construct("audit", "audit");
    }
}

