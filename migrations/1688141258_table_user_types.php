<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Blueprint;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
        return Schema::create("user_types", function (Blueprint $table) {
          $table->id();
          $table->varchar("name");
          $table->tinyInteger("level")->default(2);
          $table->primaryKey("id");
        });
    }

    public function down(): string
    {
        return Schema::drop("user_types");
    }
};
