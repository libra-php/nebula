<?php

namespace App\Modules;

use Nebula\Admin\Module;

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
            "module_name" => "Name",
            "class_name" => "Class",
            "module_table" => "Table",
            "module_title" => "Title",
            "module_icon" => "Icon",
            "user_type" => "Level",
        ];

        $this->validation = [
            "module_name" => ["required", "is_lowercase", "no_spaces"],
            "class_name" => ["required", "is_class"],
            "module_title" => ["required"],
            "user_type" => ["required", "integer"],
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
    }
}
