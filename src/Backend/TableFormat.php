<?php

namespace Nebula\Backend;

class TableFormat
{
    public function __construct()
    {
    }

	public function dollar(string $name, ?string $value): string
	{
		return sprintf("<div class='text-right'>$%s</div>", $this->text($name, $value));
	}

	public function text(string $name, ?string $value): string
	{
		return htmlspecialchars($value ?? '');
	}
}
