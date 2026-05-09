<?php
$settings = ['speed' => 5, 'winrate' => 70];
$settings_file = "settings.txt";
if (file_exists($settings_file)) {
    foreach (file($settings_file) as $line) {
        list($k, $v) = explode(':', trim($line));
        $settings[$k] = (int)$v;
    }
}
echo json_encode($settings);