<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Blueprint;
use Nebula\Database\Schema;

return new class implements Migration
{
  public function up(): string
  {
    return Schema::create("users", function (Blueprint $table) {
      $table->id();
      $table->uuid("uuid")->default("(UUID())");
      // Default to standard user
      $table->unsignedBigInteger("user_type")->default(2);
      $table->varchar("name");
      $table->varchar("email");
      $table->binary("password", 96);
      $table->char("two_fa_secret", 16)->nullable();
      $table->char("remember_token", 64)->nullable();
      $table->char("reset_token", 64)->nullable();
      $table->unsignedBigInteger('reset_expires_at')->nullable();
      $table->unsignedTinyInteger('failed_login_attempts')->default(0);
      $table->unsignedBigInteger('lock_expires_at')->nullable();
      $table->timestamps();
      $table->unique("email");
      $table->primaryKey("id");
      $table->foreignKey("user_type")->references("user_types", "id");
    });
  }

  public function down(): string
  {
    return Schema::drop("users");
  }
};
