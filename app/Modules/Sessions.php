<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Sessions extends Module
{
    public function __construct()
    {
        $this->table_create = $this->table_edit = $this->table_destroy = false;
        $this->table_columns = [
            "sessions.id" => "ID",
            "sessions.method" => "Method",
            "sessions.uri" => "URI",
            "users.name" => "User",
            "sessions.created_at" => "Created At",
        ];
        $this->filter_datetime = "created_at";
        $this->joins = ["INNER JOIN users ON sessions.user_id = users.id"];
        $this->filter_links = [
            "Me" => "user_id = " . user()->id,
            "Others" => "user_id != " . user()->id,
            "All" => "1=1",
        ];
        $this->filter_select = [
            "method" => "Method",
        ];
        $this->select_options = [
            "method" => [
                option("GET", "GET"),
                option("POST", "POST"),
                option("PUT", "PUT"),
                option("PATCH", "PATCH"),
                option("DELETE", "DELTE"),
            ],
        ];
        parent::__construct("sessions");
    }
}
