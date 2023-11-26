<?php

namespace App\Modules;

use App\Auth;
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
            "password" => "Password",
            "password_match" => "Password (again)",
        ];
        $this->search = ["uuid", "name", "email"];

        $this->validation = [
            "name" => ["required"],
            "email" => ["required", "email"],
            "password" => [
                "required",
                "min_length=8",
                "uppercase=1",
                "lowercase=1",
                "symbol=1",
            ],
            "password_match" => ["required", "match"],
        ];

        $this->form_controls = [
            "name" => "input",
            "email" => "input",
            "password" => "password",
            "password_match" => "password",
        ];

        $this->filter_links = [
            "Me" => "id = " . user()->id,
            "Others" => "id != " . user()->id,
        ];

        parent::__construct("users");
    }

    protected function storeOverride(array $data): array
    {
        $data['password'] = Auth::hashPassword($data['password']);
        $data['two_fa_secret'] = Auth::generateTwoFASecret();
        unset($data['password_match']);
        return $data;
    }

    protected function updateOverride(array $data): array
    {
        $data['password'] = Auth::hashPassword($data['password']);
        unset($data['password_match']);
        return $data;
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
