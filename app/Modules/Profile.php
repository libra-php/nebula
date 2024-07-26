<?php

namespace App\Modules;

class Profile extends Users
{
    public function init(): void
    {
        parent::init();
        $this->table_columns = [
            "ID" => "id",
            "Name" => "name",
            "Email" => "email",
            "Created" => "created_at",
        ];
        $this->form_columns = [
            "Name" => "name",
            "Email" => "email",
            "Password" => "password",
            "Password (again)" => "password_match",
        ];
        $user_id = user()->id;
        $this->show_back = false;
        $this->export_csv = false;
        $this->create = false;
        $this->search_columns = [];
        $this->filter_links = ["Active" => "id = $user_id"];
    }

    public function hasCreatePermission(): bool
    {
        return false;
    }

    public function hasEditPermission(string $id): bool
    {
        return $id === user()->id;
    }

    public function hasDeletePermission(string $id): bool
    {
        return $id === user()->id;
    }

    public function viewIndex(): string
    {
        $id = user()->id;
        header("HX-Location: /admin/profile/$id");
        header("Location: /admin/profile/$id");
        return '';
    }
}
