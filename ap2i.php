<?php
header('Content-Type: application/json');

$api_key = $_GET['key'] ?? '';
$number = $_GET['number'] ?? '';
$msg = $_GET['msg'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

if (empty($api_key) || empty($number) || empty($msg)) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
    exit;
}

// Check rate limiting
$rate_limit_file = 'rate_limit.txt';
if (file_exists($rate_limit_file)) {
    $rate_limit = intval(trim(file_get_contents($rate_limit_file)));
    if ($rate_limit > 0) {
        $ip_log_file = 'rate_limit.txt';
        $current_time = time();
        $ip_requests = [];
        
        if (file_exists($ip_log_file)) {
            $lines = file($ip_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                list($log_ip, $timestamp) = explode('|', $line);
                if ($current_time - $timestamp < 60) { // Within last minute
                    $ip_requests[$log_ip] = ($ip_requests[$log_ip] ?? 0) + 1;
                }
            }
        }
        
        if (($ip_requests[$ip] ?? 0) >= $rate_limit) {
            echo json_encode(["status" => "error", "message" => "Rate limit exceeded. Try again later."]);
            exit;
        }
        
        // Log this request
        file_put_contents($ip_log_file, "$ip|$current_time" . PHP_EOL, FILE_APPEND);
    }
}

// Check spam words
$spam_words_file = 'spam_words.txt';
if (file_exists($spam_words_file)) {
    $spam_words = file($spam_words_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($spam_words as $word) {
        if (stripos($msg, trim($word)) !== false) {
            echo json_encode(["status" => "error", "message" => "Message contains blocked content."]);
            exit;
        }
    }
}

// Load SMS API config
$config_file = 'api/sms.json';
if (!file_exists($config_file)) {
    echo json_encode(["status" => "error", "message" => "SMS API config not found."]);
    exit;
}

$config = json_decode(file_get_contents($config_file), true);
if (!isset($config['api_url']) || $config['status'] !== 'on') {
    echo json_encode(["status" => "error", "message" => "SMS API is disabled."]);
    exit;
}

// Load removal coin amount
$remove_file = 'remove_coin.txt';
if (!file_exists($remove_file)) {
    echo json_encode(["status" => "error", "message" => "Remove coin file not found."]);
    exit;
}

$remove_coin = floatval(trim(file_get_contents($remove_file)));
if ($remove_coin <= 0) $remove_coin = 1; // Default fallback

// Load balances
$balance_file = 'balanclamuhadifucke.txt';
if (!file_exists($balance_file)) {
    echo json_encode(["status" => "error", "message" => "Balance file not found."]);
    exit;
}

// Load API keys mapping
$api_keys_file = 'api_keys.txt';
if (!file_exists($api_keys_file)) {
    echo json_encode(["status" => "error", "message" => "API keys file not found."]);
    exit;
}

// Find username for this API key
$username = null;
$keys = file($api_keys_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($keys as $key) {
    list($user, $keyValue) = explode(':', $key);
    if ($keyValue === $api_key) {
        $username = $user;
        break;
    }
}

if (!$username) {
    echo json_encode(["status" => "error", "message" => "Invalid API key."]);
    exit;
}

// Process balance update
$lines = file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$updated_lines = [];
$enough_balance = false;
$deducted = false;
$new_balance = 0;

foreach ($lines as $line) {
    list($user, $balance) = explode(':', trim($line));

    if ($user === $username) {
        if ((float)$balance >= $remove_coin) {
            $balance -= $remove_coin;
            $enough_balance = true;
            $deducted = true;
            $new_balance = $balance;

            // Replace placeholders in API URL
            $api_url = str_replace(
                ['{$number}', '{$msg}'],
                [urlencode($number), urlencode($msg)],
                $config['api_url']
            );

            // Make API request
            $response = file_get_contents($api_url);

            // Log the request
            $log_data = [
                "ip" => $ip,
                "username" => $username,
                "api_key" => $api_key,
                "number" => $number,
                "msg" => $msg,
                "time" => date("Y-m-d H:i:s")
            ];
            file_put_contents("logs.txt", json_encode($log_data) . PHP_EOL, FILE_APPEND);

            echo json_encode([
                "status" => "success",
                "message" => "Message sent.",
                "Api_Owner" => "@hadi_vai1",
                "balance_left" => round($balance, 2)
            ]);
        } else {
            $enough_balance = false;
        }
    }

    $updated_lines[] = ($user === $username) ? "$user:$balance" : $line;
}

if (!$enough_balance) {
    echo json_encode(["status" => "error", "message" => "Insufficient balance."]);
    exit;
}

// Update balances only if deduction happened
if ($deducted) {
    file_put_contents($balance_file, implode("\n", $updated_lines));
    
    // Also update SMS logs file with the transaction
    $sms_log_entry = "$username|$number|$msg|" . date('Y-m-d H:i:s') . "|Success";
    file_put_contents("sms_logs.txt", $sms_log_entry . PHP_EOL, FILE_APPEND);
}
?>