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
            "name" => "Name",
            "number" => "Number",
        ];

        // Form columns for Edit / Create views
        $this->form_columns = [
            "name" => "Name",
            "number" => "Number",
            "file" => "File",
            "image" => "Image",
            "dropdown" => "Dropdown",
            "comment" => "Comment",
            "color" => "Colour",
            "checkbox" => "Checkbox",
            "switch" => "Switch",
        ];

        // Form valdiation columns / rules
        $this->validation = [
            "name" => ["required"],
            "number" => ["required", "numeric"],
            "dropdown" => ["required", "numeric"],
            "color" => ["required"],
        ];

        // Edit / Create view controls
        $this->form_controls = [
            "number" => "number",
            "name" => "input",
            "comment" => "textarea",
            "dropdown" => "select",
            "color" => "color",
            "file" => "upload",
            "image" => "image",
            "checkbox" => "checkbox",
            "switch" => "switch",
        ];

        // The options for a select control
        $this->select_options = [
            // The select control can be provided by
            // a query with attributes id & name
            "dropdown" => db()->selectAll("SELECT id, name FROM animals"),
            // Or an array
            // "dropdown" => ['Dog', 'Cat', 'Mouse', 'Duck', 'Deer', 'Shrimp'],
        ];

        // Searchable columns
        $this->search = ["number", "name", "comment"];

        parent::__construct("test", "test");
    }
}
