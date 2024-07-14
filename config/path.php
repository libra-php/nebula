<?php

$root = __DIR__ . "/../";
$app = $root . "app/";

return [
	"root" => $root,
	"env" => $root,
	"bin" => $root . "bin",
	"templates" => $root . "templates",
	"controllers" => $app . "Controllers",
	"migrations" => $root . "migrations",
	"middleware" => $app . "Middleware",
	"public" => $root . "public",
	"storage" => $root . "storage",
	"modules" => $app . "Modules",
	"jobs" => $root . "jobs",
	"logs" => $root . "storage/logs",
];
