<?php

namespace App\Config;

return [
    "maintenance_mode" => env("ADMIN_MAINTENANCE_MODE", "true") == "true",
];
