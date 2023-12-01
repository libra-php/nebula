<?php

namespace App\Modules;

use App\Auth;
use App\Models\User;
use Nebula\Admin\Module;

class Users extends Module
{
    public function __construct()
    {
        $this->name_col = "uuid";
        $this->table_columns = [
            "id" => "ID",
            "uuid" => "UUID",
            "name" => "Name",
            "email" => "Email",
            "created_at" => "Created At",
        ];
        $this->search = ["uuid", "name", "email"];

        $this->filter_links = [
            "Me" => "id = " . user()->id,
            "Others" => "id != " . user()->id,
        ];

        $this->form_columns = [
            "name" => "Name",
            "email" => "Email",
            "user_type" => "Type",
            "password" => "Password",
            "password_match" => "Password (again)",
        ];

        $this->form_controls = [
            "name" => "input",
            "email" => "input",
            "user_type" => "select",
            "password" => "password",
            "password_match" => "password",
        ];

        $this->select_options = [
            "user_type" => db()->selectAll(
                "SELECT id, name FROM user_types ORDER BY level DESC"
            ),
        ];

        $this->validation = [
            "name" => ["required"],
            "user_type" => ["required"],
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

        $this->addRowAction("preview_qr", "QR", "<i class='bi bi-qr-code me-1'></i> QR");
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

    protected function processTableRequest(): void
    {
        if (request()->has("preview_qr")) {
            $user = User::find(request()->id);
            if ($user) {
                $url = Auth::urlQR($user);
                $link = moduleRoute("module.index", $this->module_name);
                echo latte("auth/qr.latte", ["url" => $url, "link" => $link]);
                exit;
            }
        }
        parent::processTableRequest();
    }
}
