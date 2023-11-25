<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
            // Note: user_types
            // 1: super admin
            // 2: admin
            // 3: standard
            return Schema::raw("INSERT INTO modules (module_name, class_name, module_table, module_title, module_icon, user_type) VALUES
                ('users', 'App\\\\Modules\\\\Users', 'users', 'Users', 'bi bi-people', 1),
                ('audit', 'App\\\\Modules\\\\Audit', 'audit', 'Audit', 'bi bi-clipboard-check', 1),
                ('modules', 'App\\\\Modules\\\\Modules', 'modules', 'Modules', 'bi bi-box', 1),
                ('blog', 'App\\\\Modules\\\\Blog', 'posts', 'Blog', 'bi bi-feather', 2),
                ('sessions', 'App\\\\Modules\\\\Sessions', 'sessions', 'Sessions', 'bi bi-flag', 2),
                ('home', 'App\\\\Modules\\\\Home', null, 'Home', 'bi bi-house', 3),
                ");
    }

    public function down(): string
    {
        return Schema::raw("DELETE FROM modules");
    }
};
