<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
        // TODO QueryBuilder could do this
            return Schema::raw("INSERT INTO user_types (name, level)
                VALUES ('Super Admin', 0),
                ('Admin', 1),
                ('Standard', 2)");
    }

    public function down(): string
    {
        return Schema::raw("DELETE FROM user_types");
    }
};

