<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Sessions extends Module
{
    public function __construct()
    {
        $this->module_icon = "activity";
        $this->table_create = $this->table_edit = $this->table_destroy = false;
        $this->table_columns = [
            "sessions.id" => "ID",
            "sessions.uri" => "URI",
            "users.name" => "User",
            "sessions.created_at" => "Created At",
        ];
        $this->joins = ["INNER JOIN users ON sessions.user_id = users.id"];
        $this->filter_links = [
            "Me" => "user_id = " . user()->id,
            "All" => "1=1",
        ];
        parent::__construct("sessions", "sessions");
    }
}
