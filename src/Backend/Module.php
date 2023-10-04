<?php

namespace Nebula\Backend;

class Module
{
	protected string $module_name;
	protected ?string $table_name;

	public function __construct(string $module_name, ?string $table_name = null)
	{
		$this->module_name = $module_name;
		$this->table_name = $table_name;
	}

	public function getModuleName(): string
	{
		return $this->module_name;
	}

	public function getTableName(): string
	{
		return $this->table_name;
	}
}
