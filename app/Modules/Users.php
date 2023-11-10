<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Users extends Module
{
    public function __construct()
    {
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

        $this->filter_links = [
            "Me" => "id = " . user()->id,
            "Others" => "id != " . user()->id,
        ];

        parent::__construct("users");
    }

    protected function hasEditPermission(string $id): bool
    {
        return $id != user()->id;
    }

    protected function hasDeletePermission(string $id): bool
    {
        return $id != user()->id;
    }
}
