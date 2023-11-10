<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Blueprint;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
        return Schema::create("modules", function (Blueprint $table) {
            $table->id();
            $table->varchar("module_name");
            $table->varchar("class_name");
            $table->varchar("module_table")->nullable();
            $table->varchar("module_title");
            $table->varchar("module_icon")->default("'package'");
            $table->unsignedBigInteger("user_type")->default(2);
            $table->timestamps();
            $table->unique("module_name");
            $table->primaryKey("id");
            $table->foreignKey("user_type")->references("user_types", "id");
        });
    }

    public function down(): string
    {
        return Schema::drop("modules");
    }
};

