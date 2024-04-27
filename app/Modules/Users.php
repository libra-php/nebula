<?php

namespace App\Modules;

use Nebula\Framework\Admin\Module;

class Users extends Module
{
	public function definition(): void
	{
		$user = user();
		$this->table_columns = [
			"ID" => "id",
			"UUID" => "uuid",
			"Name" => "name",
			"Email" => "email",
			"Created" => "created_at",
		];
		$this->table_format = [
			"created_at" => "ago",
		];
		$this->search_columns = [
			"uuid",
			"name",
			"email",
		];
		$this->filter_links = [
			"All" => "1=1",
			"Me" => "id = {$user->id}",
			"Others" => "id != {$user->id}",
		];
	}
}
