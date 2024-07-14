<?php
/**
* Will automatically remove logs older than 7 days
*/

require_once __DIR__ . "/../vendor/autoload.php";

$logs_dir = config("path.logs");
$max_age = 7 * 24 * 60 * 60; // 7 days

foreach (glob($logs_dir."/*.log") as $log) {
    $mod_time = filemtime($log);
    $file_age = time() - $mod_time;
    if ($file_age > $max_age) {
        // Attempt to remove the file
        unlink($log);
    }
}
