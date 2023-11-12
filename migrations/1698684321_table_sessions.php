<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Blueprint;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
        return Schema::create("sessions", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->varchar("method");
            $table->mediumText("uri");
            $table->dateTime("created_at")->default("CURRENT_TIMESTAMP");
            $table->primaryKey("id");
            $table->foreignKey("user_id")
              ->references("users", "id")
              ->onDelete("SET NULL");
        });
    }

    public function down(): string
    {
        return Schema::drop("sessions");
    }
};
