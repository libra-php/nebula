<?php

namespace App\Modules;

use Nebula\Admin\Module;

class Home extends Module
{
    protected function customContent(): string
    {
        return latte("admin/home/index.latte", ["user" => user()]);
    }
}
