<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Test extends Module
{
    public function __construct()
    {
        /**
         * Table columns for Index view
         * key: column for table query
         * value: table header label
         */
        $this->table_columns = [
            "id" => "ID",
            "name" => "Name",
            "number" => "Number",
            "updated_at" => "Updated At",
            "created_at" => "Created At",
        ];

        /**
         * Searchable table columns
         */
        $this->search = ["number", "name", "comment"];

        /**
         * Date fitler column
         */
        $this->filter_datetime = "created_at";

        /**
         * Table filter links
         * key: link label
         * value: where clause filter
         */
        $this->filter_links = [
            "All" => "1=1",
            "Over 9000" => "number > 9000",
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
        ];

        /**
         * Form validation columns / rules
         * key: form column to validate
         * value: array of validation rules
         */
        $this->validation = [
            "name" => ["required"],
            "number" => ["required", "numeric"],
        ];

        /**
         * Edit & Create view controls
         * key: form column
         * value: control type
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
        ];

        /**
         * Select filters
         * key: column to filter on
         * value: title of select
         */
        $this->filter_select = [
            "dropdown" => "Animals",
        ];

        /**
         * The options for a select control
         * key: form column
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
            ]
        ];

        parent::__construct("test");
    }
}
