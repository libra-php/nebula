<?php

namespace App\Config;

return [
    "maintenance_mode" => env("BACKEND_MAINTENANCE_MODE", "true") == "true",
];
