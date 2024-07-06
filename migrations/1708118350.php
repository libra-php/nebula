<?php

namespace Nebula\Migrations;

use Nebula\Framework\Database\Schema;
use Nebula\Framework\Database\Interface\Migration;

return new class implements Migration
{
		public function up()
		{
			return Schema::file("/user_types/table/up.sql");
		}

		public function down()
		{
			return Schema::file("/user_types/table/down.sql");
		}
};
