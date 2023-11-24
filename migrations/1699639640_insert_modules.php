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
                ('users', 'App\\\\Modules\\\\Users', 'users', 'Users', 'users', 1),
                ('audit', 'App\\\\Modules\\\\Audit', 'audit', 'Audit', 'flag', 1),
                ('modules', 'App\\\\Modules\\\\Modules', 'modules', 'Modules', 'cpu', 1),
                ('blog', 'App\\\\Modules\\\\Blog', 'posts', 'Blog', 'feather', 2),
                ('sessions', 'App\\\\Modules\\\\Sessions', 'sessions', 'Sessions', 'activity', 2),
                ('home', 'App\\\\Modules\\\\Home', null, 'Home', 'home', 3),
                ");
    }

    public function down(): string
    {
        return Schema::raw("DELETE FROM modules");
    }
};
