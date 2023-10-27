<?php
/**
 * NEBULA -- a powerful PHP micro-framework
 * Github: https://github.com/libra-php/nebula
 * Created: william.hleucka@gmail.com
 * License: MIT
 */

$global_start = microtime(true);
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__ . "/../bootstrap/app.php";

// Attribute-based-routing is enabled by default
// However, you can still use traditional routing
// Delete the following routes if you wish to use Controller
// Attribute-based-routing
// Example / web endpoint
$app->route('GET', '/', function() {
    return latte('welcome/index.latte');
});

$app->run(Nebula\Interfaces\Http\Kernel::class);
