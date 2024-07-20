<?php

namespace Nebula\Migrations;

use Nebula\Framework\Auth\Auth;
use Nebula\Framework\Database\{Blueprint, Schema};
use Nebula\Framework\Database\Interface\Migration;

return new class implements Migration
{
    public function up(): string
    {
        return Schema::create("users", function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->uuid("uuid")->default("(UUID())");
            $table->unsignedBigInteger("user_type_id")->default(3);
            $table->varchar("name");
            $table->varchar("email");
            $table->binary("password", 96);
            $table->timestamp("login_at")->default("CURRENT_TIMESTAMP");
            $table->char("secret_key", 16)->nullable();
            $table->unsignedTinyInteger("enable_2fa")->default(1);
            $table->timestamps();
            $table->unique("email");
            $table->primaryKey("id");
            $table->foreignKey("user_type_id")->references("user_types", "id");
        });
    }

    public function afterUp(): string
    {
        return Schema::insert(
            "users",
            [
                "user_type_id",
                "name",
                "email",
                "password",
                "secret_key",
                "enable_2fa",
            ],
            [
                1,
                "Administrator",
                "administrator@localhost",
                Auth::hashPassword("admin2024!"),
                Auth::generateSecretKey(),
                0,
            ]
        );
    }

    public function down(): string
    {
        return Schema::drop("users");
    }
};
