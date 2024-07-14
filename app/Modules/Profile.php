<?php

namespace App\Modules;

class Profile extends Users
{
    public function init(): void
    {
        parent::init();
        $user = user();
        $this->create = false;
        $this->filter_links = ["Me" => "id = {$user->id}"];
    }

    public function hasDeletePermission(string $id): bool
    {
        return true;
    }
}
