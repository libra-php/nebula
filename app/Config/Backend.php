<?php

namespace App\Config;

return [
    "maintenance_mode" => env("APP_MAINTENANCE_MODE", "true") == "true",
];
