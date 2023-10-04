<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Users extends Module
{
    public function __construct()
    {
        parent::__construct("users", "users");
    }
}
