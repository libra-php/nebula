<?php
/**
* Schedule automated tasks here
* Library: https://github.com/peppeocchi/php-cron-scheduler
*/
require_once __DIR__ . '/vendor/autoload.php';

use GO\Scheduler;

$scheduler = new Scheduler();

$jobs = config("path.jobs");
$storage = config("path.storage");

// Add scheduled tasks


// Heartbeat
$heartbeat_filename = date("Y-m-d") . "_heartbeat.log";
$scheduler->php($jobs . "/heartbeat.php")
    ->everyMinute()
    ->output("$storage/logs/$heartbeat_filename", true);

// Rotate logs
$scheduler->php($jobs . "/rotate_logs.php")->monday();


// Let the scheduler execute jobs which are due.
$scheduler->run();
