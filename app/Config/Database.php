<?php

namespace App\Config;

return [
    "enabled" => env("DB_ENABLED", "true") == "true",
    "show_profiler" => env("DB_SHOW_PROFILER", "true") == "true",
    "mode" => env("DB_MODE"),
    "name" => env("DB_NAME"),
    "host" => env("DB_HOST"),
    "port" => env("DB_PORT"),
    "username" => env("DB_USERNAME"),
    "password" => env("DB_PASSWORD"),
    "charset" => env("DB_CHARSET"),
];
