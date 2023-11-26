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
            "(SELECT user_types.name
                FROM user_types
                WHERE user_types.id = modules.user_type) as permission_level" =>
                "Permission Level",
            "modules.created_at" => "Created At",
        ];

        $this->form_columns = [
            "module_name" => "Route",
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

        $this->filter_select = [
            "user_type" => "Permission Level",
        ];

        $this->select_options = [
            "user_type" => db()->selectAll(
                "SELECT id, name FROM user_types ORDER BY level DESC"
            ),
        ];

        parent::__construct("modules");
    }
}
