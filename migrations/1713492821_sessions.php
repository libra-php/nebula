<?php

namespace Nebula\Migrations;

use Nebula\Framework\Database\Blueprint;
use Nebula\Framework\Database\Schema;
use Nebula\Framework\Database\Interface\Migration;

return new class implements Migration
{
	public function up(): string
	{
		return Schema::create("sessions", function (Blueprint $table) {
			$table->unsignedBigInteger("id")->autoIncrement();
			$table->mediumText("request_uri")->nullable();
			$table->unsignedInteger("ip")->nullable();
			$table->unsignedBigInteger("user_id")->nullable();
			$table->timestamp("created_at")->default("CURRENT_TIMESTAMP");
			$table->primaryKey("id");
			$table->foreignKey("user_id")->references("users", "id");
		});
	}

	public function down(): string
	{
		return Schema::drop("sessions");
	}
};
