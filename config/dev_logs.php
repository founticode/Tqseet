<?php

function logError($message) {

    $date = date("Y-m-d H:i:s");

    $file = __DIR__ . "/../errors.log";

    $formatted = "[TQSEET][$date] " . $message . PHP_EOL;

    file_put_contents($file, $formatted, FILE_APPEND);
}