<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Home extends Module
{
    public function __construct()
    {
        $this->module_icon = "home";
        parent::__construct("home");
    }

    protected function customContent(): string
    {
        return latte("backend/home.latte");
    }
}
