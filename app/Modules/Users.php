<?php

namespace App\Modules;

use App\Models\User;
use Nebula\Framework\Admin\Module;
use Nebula\Framework\Auth\Auth;

class Users extends Module
{
    private User $user;

    public function init(): void
    {
        $this->user = user();
        $this->table_columns = [
            "ID" => "id",
            "UUID" => "uuid",
            "Name" => "name",
            "Type" => "(SELECT name FROM user_types WHERE id = user_type_id) as type",
            "Created" => "created_at",
        ];
        $this->table_format = [
            "created_at" => "ago",
        ];
        $this->search_columns = ["uuid", "name", "email"];
        $this->filter_links = [
            "All" => "1=1",
            "Me" => "id = {$this->user->id}",
            "Others" => "id != {$this->user->id}",
        ];
        $this->form_columns = [
            "Name" => "name",
            "Email" => "email",
            "Type" => "user_type_id",
            "Password" => "password",
            "Password (agian)" => "password_match",
        ];
        $this->form_controls = [
            "password" => "password",
            "password_match" => "password",
            "user_type_id" => "select",
        ];
        $this->select_options = [
            "user_type_id" => db()->fetchAll("SELECT id as value, name as label
                FROM user_types
                ORDER BY permission_level, name"),
        ];
        $this->validation_rules = [
            "name" => ["required"],
            "email" => ["required"],
            "password" => ["minlength|8", "symbol"],
            "password_match" => ["match|password"]
        ];
        // New records always require these columns
        if (!$this->id) {
            $this->validation_rules["password"][] = "required";
            $this->validation_rules["password_match"][] = "required";
        }

        // Special admin case
        // There must exist at least 1 super admin
        if ($this->id === "1") {
            $this->form_disabled[] = "user_type_id";
        } else {
            $this->validation_rules["user_type_id"] = ["required"];
            $this->validation_rules["email"][] = "email";
        }
    }

    public function hasDeletePermission(string $id): bool
    {
        // Default admin and current user cannot be deleted here
        return $id !== "1" && user()->id !== $id;
    }

    protected function editValueOverride(object &$row): void
    {
        // Always hide this value
        if ($row->column === "password") $row->value = "";
    }

    public function processCreate(array $request): mixed
    {
        // If there is a non-blank password set, then change it
        if (trim($request["password"]) !== '' && trim($request["password_match"]) !== '') {
            // Hash the password
            $request["password"] = Auth::hashPassword($request["password"]);
        }
        return parent::processCreate($request);
    }

    public function processUpdate(string $id, array $request): mixed
    {
        // If there is a non-blank password set, then change it
        if (trim($request["password"]) !== '' && trim($request["password_match"]) !== '') {
            // Hash the password
            $request["password"] = Auth::hashPassword($request["password"]);
        } else {
            // Otherwise, we don't need to update the password field
            unset($request["password"]);
            unset($request["password_match"]);
        }
        return parent::processUpdate($id, $request);
    }
}
