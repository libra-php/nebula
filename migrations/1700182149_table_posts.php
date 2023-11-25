<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Blueprint;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
        return Schema::create("posts", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->enum("status", ["Draft", "Published", "Archived"])->default("'Draft'");
            $table->varchar("slug");
            $table->varchar("title");
            $table->varchar("subtitle")->nullable();
            $table->text("content")->nullable();
            $table->mediumText("banner_image")->nullable();
            $table->dateTime("published_at")->nullable();
            $table->timestamps();
            $table->primaryKey("id");
            $table->foreignKey("user_id")
                ->references("users", "id");
        });
    }

    public function down(): string
    {
        return Schema::drop("posts");
    }
};

