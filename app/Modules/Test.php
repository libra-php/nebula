<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Test extends Module
{
    public function __construct()
    {
        $this->table_columns = [
            "id" => "ID",
            "number" => "Number",
            "name" => "Name",
        ];

        $this->form_columns = [
            "number" => "Number",
            "name" => "Name",
            "comment" => "Comment",
        ];

        $this->validation = [
            "name" => ["required"],
            "number" => ["required", "numeric"],
        ];

        $this->form_controls = [
            "number" => "text",
            "name" => "text",
            "comment" => "textarea",
        ];

        $this->search = [
            "number",
            "name"
        ];

        parent::__construct("test", "test");
    }
}
