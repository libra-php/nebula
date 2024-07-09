<?php
require_once __DIR__ . '/vendor/autoload.php';

use GO\Scheduler;

// Create a new scheduler
$scheduler = new Scheduler();

// Jobs path
$jobs = config("path.jobs");
$storage = config("path.storage");

// Add scheduled tasks here

// Heartbeat
$heartbeat_filename = date("Y-m-d") . "_heartbeat.log";
$scheduler->php($jobs . "/heartbeat.php")
    ->everyMinute()
    ->output("$storage/$heartbeat_filename", true);

// Let the scheduler execute jobs which are due.
$scheduler->run();
