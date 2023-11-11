<?php
/**
 * NEBULA -- a powerful PHP micro-framework
 * Github: https://github.com/libra-php/nebula
 * Created: william.hleucka@gmail.com
 * License: MIT
 */

$global_memory = memory_get_usage();
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__ . "/../bootstrap/app.php";

// Attribute-based-routing is enabled by default
// However, you can still use traditional routing
// Delete the following routes if you wish to use
// Attribute-based-routing
// Basic example:
$app->route('GET', '/', function() {
    echo "Hello, world!";die;
}, middleware: ['cache']);

$app->run(Nebula\Interfaces\Http\Kernel::class);
