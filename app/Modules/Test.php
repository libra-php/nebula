<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Test extends Module
{
    protected array $table_columns = [
        "id" => "ID",
        "number" => "Number",
        "name" => "Name",
    ];

    protected array $form_columns = [
        "number" => "Number",
        "name" => "Name",
    ];

    protected array $validation = [
        "name" => ["required"],
        "number" => ["required", "numeric"],
    ];

    public function __construct()
    {
        parent::__construct("test", "test");
    }
}
