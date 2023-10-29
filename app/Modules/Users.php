<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Users extends Module
{
    public function __construct()
    {
        $this->module_icon = "users";
        $this->table_columns = [
            "id" => "ID",
            "uuid" => "UUID",
            "name" => "Name",
            "email" => "Email",
            "created_at" => "Created At",
        ];

        $this->form_columns = [
            "name" => "Name",
            "email" => "Email",
        ];

        $this->validation = [
            "name" => ["required"],
            "email" => ["required", "email"],
        ];

        $this->form_controls = [
            "name" => "disabled",
            "email" => "disabled",
        ];

        parent::__construct("users", "users");
    }
}
