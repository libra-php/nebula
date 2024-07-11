<?php

namespace App\Modules;

use Nebula\Framework\Admin\Module;

class Audit extends Module
{
    public function init(): void
    {
        $this->create = $this->delete = $this->edit = false;
        $this->table_columns = [
            "ID" => "id",
            "Table" => "table_name",
            "Field" => "field",
            "Old Value" => "old_value",
            "New Value" => "new_value",
            "Tag" => "tag",
            "Created" => "created_at",
        ];
        $this->filter_links = [
            "All" => "1=1",
            "Create" => "tag = 'CREATE'",
            "Update" => "tag = 'UPDATE'",
            "Delete" => "tag = 'DELETE'",
        ];
    }
}
