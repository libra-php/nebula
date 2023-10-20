<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Test extends Module
{
    public function __construct()
    {
        // Table columns for Index view
        $this->table_columns = [
            "id" => "ID",
            "number" => "Number",
            "name" => "Name",
        ];

        // Form columns for Edit / Create views
        $this->form_columns = [
            "number" => "Number",
            "name" => "Name",
            "comment" => "Comment",
            "dropdown" => "Dropdown",
        ];

        // Form valdiation columns / rules
        $this->validation = [
            "name" => ["required"],
            "number" => ["required", "numeric"],
        ];

        // Edit / Create view controls
        $this->form_controls = [
            "number" => "text",
            "name" => "text",
            "comment" => "textarea",
            "dropdown" => "select",
        ];

        // The options for a select control
        $this->select_options = [
            "dropdown" => db()->selectAll("SELECT * FROM animals"),
        ];

        // Searchable columns
        $this->search = [
            "number",
            "name",
            "comment"
        ];

        parent::__construct("test", "test");
    }
}
