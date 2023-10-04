<?php

namespace App\Config;

return [
    "sign_in_route" => "module.index",
    "register_enabled" => env("AUTH_REGISTER_ENABLED", "true") == "true",
    "two_fa_enabled" =>
        env("TWO_FACTOR_AUTHENTICATION_ENABLED", "true") == "true",
];
