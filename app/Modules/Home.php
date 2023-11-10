<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Home extends Module
{
    public function __construct()
    {
        $this->table_create = false;
        $this->export_csv = false;

        parent::__construct("home");
    }

    protected function customContent(): string
    {
        return latte("backend/home.latte");
    }
}
