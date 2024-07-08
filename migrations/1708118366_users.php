<?php

namespace Nebula\Migrations;

use Nebula\Framework\Database\{Blueprint, Schema};
use Nebula\Framework\Database\Interface\Migration;

return new class implements Migration
{
	public function up()
	{
		return Schema::create("users", function (Blueprint $table) {
			$table->unsignedBigInteger("id")->autoIncrement();
			$table->uuid("uuid")->default("(UUID())");
			$table->unsignedBigInteger("user_type_id")->default(3);
			$table->varchar("name");
			$table->varchar("email");
			$table->binary("password", 96);
			$table->timestamp("login_at")->default("CURRENT_TIMESTAMP");
			$table->timestamps();
			$table->unique("email");
			$table->primaryKey("id");
			$table->foreignKey("user_type_id")->references("user_types", "id");
		});
	}

	public function down()
	{
		return Schema::drop("users");
	}
};
