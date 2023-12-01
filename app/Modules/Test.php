<?php

namespace App\Modules;

use Nebula\Admin\Module;
use Nebula\Validation\Validate;

class Test extends Module
{
    public function __construct()
    {
        /**
         * Table columns for Index view
         * key: column for table query (column or subquery or injected value ie, '100' as dollar)
         * value: table header label
         * tip: if you set the value to null, the value is not rendered
         */
        $this->table_columns = [
            "id" => "ID",
            "name" => "Name",
            "number" => "Number",
            "'100' as dollar" => "Dollar",
            "updated_at" => "Updated At",
            "created_at" => "Created At",
        ];

        /**
         * Table filter links
         * Located obove the table as links with counts
         * key: link label
         * value: where clause filter
         */
        $this->filter_links = [
            "All" => "1=1",
            "Over 9000" => "number > 9000",
        ];

        /**
         * Table column formatting
         * key: table column
         * value: format type (can be pre-defined like 'dollar' or a callback fn)
         */
        $this->table_format = [
            "number" => fn($datum, $column) => $datum->$column > 9000
                ? sprintf('<span class="text-success">%s</span>', $datum->$column)
                : sprintf('<span class="text-danger">%s</span>', $datum->$column),
            "dollar" => "dollar"
        ];

        /**
         * Searchable table columns, just provide the column name
         * Control is located in the filter accordion
         */
        $this->search = ["number", "name", "comment"];

        /**
         * Date fitler column
         * Controls will be rendered in the filter accordion
         */
        $this->filter_datetime = "created_at";

        /**
         * Select filters
         * Control is located in the filter accordion
         * key: column to filter on
         * value: title of select
         */
        $this->filter_select = [
            "dropdown" => "Animals",
        ];


        /**
         * Form columns for Edit & Create views
         * key: column for edit / create query
         * value: form label
         */
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
            'test' => "Test",
        ];

        /**
         * Edit & Create view controls
         * key: form column
         * value: control type (can be pre-defined like 'input' or a callback fn)
         */
        $this->form_controls = [
            "number" => "number",
            "name" => "input",
            "comment" => "textarea",
            // select if you require a value
            // nselect if you allow null
            "dropdown" => "nselect",
            "color" => "color",
            "file" => "upload",
            "image" => "image",
            "checkbox" => "checkbox",
            "switch" => "switch",
            "test" => fn($column, $value) => 'Hello!',
        ];

        /**
         * Form validation columns / rules
         * Inspired by Laravel
         * key: form column to validate
         * value: array of validation rules
         */
        // example: override the custom validation message
        Validate::$messages["name_custom"] = "%label must not equal 'test'!!!!";
        $this->validation = [
            // note: custom validation here
            "name" => ["required", fn($value) => trim(strtolower($value)) !== 'test'],
            "number" => ["required", "numeric"],
            "color" => ["color"],
        ];

        /**
         * The options for a dropdown table filter (filter_select) or dropdown control (form column)
         * key: form column (corresponding to fitler/column)
         * value: query or array
         * query: db query of (id = option value & name = option label)
         * array: array of options (key = option value & name = option label)
         */
        $this->select_options = [
            "dropdown" => [
                option(1, "Fish"),
                option(2, "Bear"),
                option(3, "Dog"),
                option(4, "Dolphin"),
                option(5, "Bear"),
                option(6, "Whale"),
                option(7, "Wolf"),
                option(8, "Tiger"),
            ],
        ];
    }
}
