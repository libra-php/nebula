<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Users extends Module
{
    protected array $table_columns = [
        "id" => "ID",
        "uuid" => "UUID",
        "name" => "Name",
        "email" => "Email",
        "created_at" => "Created At",
    ];

    protected array $form_columns = [
        "name" => "Name",
        "email" => "Email",
    ];

    public function __construct()
    {
        parent::__construct("users", "users");
    }
}
