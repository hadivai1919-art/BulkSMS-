<?php
$config = [
    "win_rate" => 50,
    "speed" => 3
];

if (file_exists("config.txt")) {
    $lines = file("config.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'win_rate:') === 0) {
            $config["win_rate"] = (float)explode(':', $line)[1];
        } elseif (strpos($line, 'speed:') === 0) {
            $config["speed"] = (float)explode(':', $line)[1];
        }
    }
}

header("Content-Type: application/json");
echo json_encode($config);