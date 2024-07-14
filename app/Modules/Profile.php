<?php

namespace App\Modules;

class Profile extends Users
{
    public function init(): void
    {
        parent::init();
        $user = user();
        $this->export_csv = false;
        $this->create = false;
        $this->search_columns = [];
        $this->filter_links = ["Active" => "id = {$user->id}"];
    }

    public function hasDeletePermission(string $id): bool
    {
        return true;
    }
}
