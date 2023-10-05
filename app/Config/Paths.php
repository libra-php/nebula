<?php

namespace App\Config;

$app_root = __DIR__ . "/../../";

return [
    "app_root" => $app_root,
    "logs" => $app_root . "logs/",
    "controllers" => $app_root . "app/Controllers/",
    "migrations" => $app_root . "migrations/",
    "config" => $app_root . "app/Config/",
    "modules" => $app_root . "app/Modules/",
];
