<?php

namespace Nebula\Framework\Console;

use Nebula\Framework\Database\Interfaces\Migration;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Migrations
{

	public function getMigrationFiles(string $migration_path): array
	{
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($migration_path),
			RecursiveIteratorIterator::SELF_FIRST
		);

		$files = array();

		foreach ($iterator as $file) {
			if ($file->isFile() && $file->getExtension() === "php") {
				$files[] = $file->getPathname();
			}
		}

		return $files;
	}

	public function getMigrations(): void
	{
		$migration_path = config("path.migrations");
		$migrations = $this->getMigrationFiles($migration_path);
		dump($migrations);
	}

	public function migrationUp(Migration $migration)
	{
		$result = db()->query($migration->up());
	}

	public function migrationDown(Migration $migration)
	{
		$result = db()->query($migration->down());
	}
}