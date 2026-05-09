<?php
session_start();
$username = $_SESSION['username'] ?? '';
$banlist = file_exists("banlist.txt") ? file("banlist.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

if (in_array($username, $banlist)) {
    http_response_code(403);
    echo "BANNED";
    exit;
}

$balance = 0;
if (file_exists("balanclamuhadifucke.txt")) {
    foreach (file("balanclamuhadifucke.txt") as $line) {
        list($user, $bal) = explode(":", trim($line));
        if ($user === $username) {
            $balance = $bal;
            break;
        }
    }
}
echo $balance;