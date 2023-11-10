<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
            return Schema::raw("INSERT INTO modules (module_name, class_name, module_table, module_title, module_icon, user_type) VALUES
                ('home', 'App\\\\Modules\\\\Home', null, 'Home', 'home', 3),
                ('audit', 'App\\\\Modules\\\\Audit', 'audit', 'Audit', 'flag', 2),
                ('modules', 'App\\\\Modules\\\\Modules', 'modules', 'Modules', 'cpu', 1),
                ('sessions', 'App\\\\Modules\\\\Sessions', 'sessions', 'Sessions', 'activity', 2),
                ('users', 'App\\\\Modules\\\\Users', 'users', 'Users', 'users', 2)");
    }

    public function down(): string
    {
        return Schema::raw("DELETE FROM modules");
    }
};
