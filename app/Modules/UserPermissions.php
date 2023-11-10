<?php

namespace App\Modules;

use Nebula\Backend\Module;

class UserPermissions extends Module
{
    public function __construct()
    {
        // Disable create, edit, destroy
        $this->table_edit = $this->table_create = $this->table_destroy = false;
        // Disable the csv export
        $this->export_csv = false;
        $this->module_icon = "user-check";
        parent::__construct("user_permissions", "users");
		$this->module_title = "User Permissions";
	}
}
