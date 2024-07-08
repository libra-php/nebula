<?php

namespace Nebula\Migrations;

use Nebula\Framework\Database\Blueprint;
use Nebula\Framework\Database\Schema;
use Nebula\Framework\Database\Interface\Migration;

return new class implements Migration
{
    public function up()
    {
        return Schema::create("user_types", function(Blueprint $table) {
            $table->unsignedBigInteger("id")->autoIncrement();
            $table->varchar("name");
            $table->tinyInteger("permission_level")->default(2); // default standard user
            $table->timestamps();
            $table->primaryKey("id");
        });
    }

    public function afterUp()
    {
        return Schema::insert("user_types", ["name", "permission_level"], [
            ["Super Admin", 0],
            ["Admin", 1],
            ["Standard", 2],
        ]);
    }

    public function down()
    {
        return Schema::drop("user_types");
    }
};
