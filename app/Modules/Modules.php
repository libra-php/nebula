<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Modules extends Module
{
    public function __construct()
    {
        $this->export_csv = false;
        $this->name_col = "module_title";
        $this->table_columns = [
            "modules.id" => "ID",
            "modules.module_title" => "Title",
            "user_types.name" => "Permission Level",
            "modules.created_at" => "Created At",
        ];

        $this->joins = [
            "INNER JOIN user_types ON modules.user_type = user_types.id",
        ];

        $this->form_columns = [
            "module_name" => "Name",
            "class_name" => "Class",
            "module_table" => "Table",
            "module_title" => "Title",
            "module_icon" => "Icon",
            "user_type" => "Permission Level",
        ];

        $this->validation = [
            "module_name" => ["required"],
            "class_name" => ["required"],
            "module_title" => ["required"],
            "user_type" => ["required"],
        ];

        $this->form_controls = [
            "module_name" => "input",
            "class_name" => "input",
            "module_table" => "input",
            "module_title" => "input",
            "module_icon" => "input",
            "user_type" => "select",
        ];

        $this->select_options = [
            "user_type" => db()->selectAll(
                "SELECT id, name FROM user_types ORDER BY level DESC"
            ),
        ];

        parent::__construct("modules");
    }
}
