<?php

namespace Nebula\Backend;

use Nebula\Database\QueryBuilder;

class Module
{
	protected string $module_name;
	protected ?string $table_name;
	protected array $table_columns = [];
	protected array $table_data = [];
	protected array $form_columns = [];
	protected array $form_data = [];
	

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

	public function table(): string
	{
		return latte("backend/table.latte", $this->getTableData());
	}

	protected function getTableQuery(): ?QueryBuilder
	{
		if (is_null($this->table_name)) return null;
		$qb = QueryBuilder::select($this->table_name)
			->columns(array_keys($this->table_columns));

		return $qb;
	}

	protected function getTableData(): array
	{
		$qb = $this->getTableQuery();
		$data = !is_null($qb)
			? db()->run($qb->build(), $qb->values())->fetchAll()
			: [];

		return [
			"data" => $data,
			"headers" => array_values($this->table_columns)
		];
	}

	public function form(): string
	{
		return latte("backend/form.latte", $this->getTableData());
	}

	protected function getFormQuery(): QueryBuilder
	{
		$qb = QueryBuilder::select($this->table_name)
			->columns(array_keys($this->form_columns));

		return $qb;
	}

	protected function getFormData(): array
	{
		return [];
	}
}
