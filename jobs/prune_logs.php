<?php
/**
* Will automatically remove logs older than 7 days
*/

require_once __DIR__ . "/../vendor/autoload.php";

$logs_dir = config("path.logs");
$max_days = 7;
$max_age = $max_days * 24 * 60 * 60;

foreach (glob($logs_dir."/*.log") as $log) {
    $mod_time = filemtime($log);
    $file_age = time() - $mod_time;
    if ($file_age >= $max_age) {
        // Attempt to remove the file
        if (!unlink($log)) {
            print("Failed to remove log: $log" . PHP_EOL);
        }
    }
}
