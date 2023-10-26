<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Blueprint;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
        return Schema::create("test", function (Blueprint $table) {
            $table->id();
            $table->varchar("name");
            $table->bigInteger("number");
            $table->mediumText("comment")->nullable();
            $table->unsignedSmallInteger("dropdown")->nullable();
            $table->char("color", 7)->default("'#000000'");
            $table->unsignedTinyInteger("checkbox")->default(0);
            $table->unsignedTinyInteger("switch")->default(0);
            $table->mediumText("file")->nullable();
            $table->mediumText("image")->nullable();
            $table->timestamps();
            $table->primaryKey("id");
        });
    }

    public function down(): string
    {
        return Schema::drop("test");
    }
};

