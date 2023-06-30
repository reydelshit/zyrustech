<?php
include_once(__DIR__ . "/src/config.php");
$url = HOST . ":" . PORT_NUMBER;

function open_browser(string $url)
{
    if (PHP_OS_FAMILY === 'Darwin') {
        exec("open $url");
    } elseif (PHP_OS_FAMILY === 'Windows') {
        exec("start $url");
    } elseif (PHP_OS_FAMILY === 'Linux') {
        exec("xdg-open $url");
    }
}

// Start the server in the background using a system command
if (PHP_OS_FAMILY === 'Windows') {
    $command = "start /B php -S " . $url . " > NUL";
} else {
    $command = "php -S " . $url . " > /dev/null 2>&1 &";
}

exec($command);

// Wait for a short delay to allow the server to start
sleep(1);

// Open the browser
open_browser($url);
