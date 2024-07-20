<?php

return [
    "enabled" => env("EMAIL_ENABLED", true),
    "debug" => env("EMAIL_DEBUG", false),
    "host" => env("EMAIL_HOST"),
    "port" => env("EMAIL_PORT"),
    "username" => env("EMAIL_USERNAME"),
    "password" => env("EMAIL_PASSWORD"),
    "from" => env("EMAIL_FROM"),
    "from_name" => env("EMAIL_FROM_NAME"),
    "reply_to" => env("EMAIL_REPLY_TO"),
    "reply_to_name" => env("EMAIL_REPLY_TO_NAME"),
];
