<?php

namespace Nebula\Migrations;

use Nebula\Framework\Database\Blueprint;
use Nebula\Framework\Database\Schema;
use Nebula\Framework\Database\Interface\Migration;

return new class implements Migration
{
	public function up(): string
	{
		return Schema::create("modules", function (Blueprint $table) {
            $table->bigIncrements("id");
			$table->tinyInteger("enabled")->default(1);
			$table->varchar("title");
			$table->varchar("path")->nullable();
			$table->varchar("class_name")->nullable();
			$table->varchar("sql_table")->nullable();
			$table->varchar("primary_key")->nullable();
			$table->tinyInteger("item_order")->default(0);
			$table->tinyInteger("max_permission_level")->nullable();
			$table->unsignedBigInteger("parent_module_id")->nullable();
			$table->timestamps();
			$table->primaryKey("id");
			$table->foreignKey("parent_module_id")->references("modules", "id");
		});
	}

	public function afterUp(): string
	{
		return Schema::insert(
			"modules",
			[
				"title",
				"path",
				"class_name",
				"sql_table",
				"primary_key",
				"item_order",
				"max_permission_level",
				"parent_module_id"
			],
			["Administration", NULL, NULL, NULL, NULL, 0, NULL, NULL],
			["Account", NULL, NULL, NULL, NULL, 1, NULL, NULL],
			["Users", "users", "\\\App\\\Modules\\\Users", "users", "id", 0, 1, 1],
			["User Types", "user-types", "\\\App\\\Modules\\\UserTypes", "user_types", "id", 0, 0, 3],
			["Modules", "modules", "\\\App\\\Modules\\\Modules", "modules", "id", 1, 0, 1],
			["Sessions", "sessions", "\\\App\\\Modules\\\Sessions", "sessions", "id", 2, 1, 1],
			["Audit", "audit", "\\\App\\\Modules\\\Audit", "audit", "id", 3, 0, 1],
			["Profile", "profile", "\\\App\\\Modules\\\Profile", "users", "id", 0, 2, 2],
		);
	}

	public function down(): string
	{
		return Schema::drop("modules");
	}
};
