<?php

namespace App\Modules;

use App\Auth;
use App\Models\{Session, User};
use Nebula\Admin\Module;

class Users extends Module
{
    public function __construct()
    {
        $this->name_col = "uuid";
        $this->table_columns = [
            "id" => "ID",
            "id as online" => "Online",
            "uuid" => "UUID",
            "name" => "Name",
            "email" => "Email",
            "created_at" => "Created At",
        ];
        $this->table_format = [
            "online" => fn($datum, $column) => $this->isOnline($datum->$column),
        ];
        $this->search = ["uuid", "name", "email"];

        $this->filter_links = [
            "Me" => "id = " . user()->id,
            "Others" => "id != " . user()->id,
            "All" => "1=1",
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

        $this->addRowAction("preview_qr", "QR", "<i class='bi bi-qr-code me-1'></i> QR");
    }

    protected function getUpdateValidation(): array
    {
        return [
            "name" => ["required"],
            "user_type" => ["required"],
            "email" => ["required", "email"],
            "password" => [
                "min_length=8",
                "uppercase=1",
                "lowercase=1",
                "symbol=1",
            ],
            "password_match" => ["match"],
        ];
    }

    protected function getStoreValidation(): array
    {
        return [
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
    }

    protected function hasEditPermission(string $id): bool
    {
        return $id != user()->id;
    }

    protected function hasDeletePermission(string $id): bool
    {
        return $id != user()->id;
    }

    protected function editOverride(array $data): array
    {
        // We don't need a value for password (it is already hashed)
        unset($data['password']);
        return $data;
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
        if (trim($data['password_match']) != '') {
            $data['password'] = Auth::hashPassword($data['password']);
        } else {
            unset($data['password']);
        }
        unset($data['password_match']);
        return $data;
    }

    private function isOnline($user_id)
    {
        $bg = "bg-danger";
        $user_sessions = Session::search(
            ["user_id", $user_id],
            ["created_at", ">", date("Y-m-d H:i:s", strtotime("-5 minute"))]
        );
        if ($user_sessions) {
            $last_session = is_array($user_sessions)
                ? array_pop($user_sessions)
                : $user_sessions;
            if ($last_session->created_at > date("Y-m-d H:i:s", strtotime("-3 minute"))) {
                $bg = "bg-success";
            } else {
                $bg = "bg-warning";
            }
        }
        return latte("admin/users/online-status.latte", [
            "class" => $bg,
            "user_id" => $user_id,
            "update" => $user_id != user()->id,
        ]);
    }

    private function updateUserOnlineStatus()
    {
        // Polls every 10 seconds
        $user = User::find(request()->user_online_status);
        if ($user) {
            echo $this->isOnline($user->id);
        }
        exit;
    }

    private function previewQRCode()
    {
        // Preview the Two-Factor QR code
        $user = User::find(request()->id);
        if ($user) {
            $url = Auth::urlQR($user);
            $link = moduleRoute("module.index", $this->module_name);
            echo latte("auth/qr.latte", ["url" => $url, "link" => $link]);
        }
        exit;
    }

    protected function processTableRequest(): void
    {
        if (request()->has("user_online_status")) {
            $this->updateUserOnlineStatus();
        }
        if (request()->has("preview_qr")) {
            $this->previewQRCode();
        }
        parent::processTableRequest();
    }
}
