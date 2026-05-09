<?php
session_start();
$username = $_SESSION['username'] ?? '';
$balance_file = "balanclamuhadifucke.txt";

function updateBalance($username, $delta) {
    global $balance_file;
    $lines = file($balance_file, FILE_IGNORE_NEW_LINES);
    $updated = false;
    foreach ($lines as &$line) {
        list($user, $bal) = explode(':', $line);
        if ($user === $username) {
            $bal = max(0, $bal + $delta);
            $line = "$user:$bal";
            $updated = true;
            break;
        }
    }
    if (!$updated && $delta > 0) {
        $lines[] = "$username:$delta";
    }
    file_put_contents($balance_file, implode("\n", $lines) . "\n");
}

$type = $_POST['type'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);

if ($type === 'bet') {
    $lines = file($balance_file, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        list($user, $bal) = explode(':', $line);
        if ($user === $username) {
            if ($bal < $amount) {
                echo "Insufficient balance";
                exit;
            }
        }
    }
    updateBalance($username, -$amount);
    echo "Bet placed";
} elseif ($type === 'cashout') {
    updateBalance($username, $amount);
    echo "Cashout success";
}