<?php
header('Content-Type: application/json');

// Get parameters
$api_key = $_REQUEST['key'] ?? '';
$number = $_REQUEST['number'] ?? '';
$msg = $_REQUEST['msg'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

if (empty($api_key) || empty($number) || empty($msg)) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters (key, number, msg)."]);
    exit;
}

// --- 1. SPAM FILTER ---
$spam_words_file = 'spam_words.txt';
if (file_exists($spam_words_file)) {
    $spam_content = file_get_contents($spam_words_file);
    if (!empty(trim($spam_content))) {
        $spam_words = explode(',', $spam_content);
        foreach ($spam_words as $word) {
            $word = trim($word);
            if (!empty($word) && stripos($msg, $word) !== false) {
                echo json_encode(["status" => "error", "message" => "Message contains blocked content (Spam)."]);
                exit;
            }
        }
    }
}

// --- 2. RATE LIMITING (IP & NUMBER) ---
$rate_limit_file = 'rate_limit.txt';
$rate_log_file = 'rate_limit_logs.json'; // Log file to track requests

if (file_exists($rate_limit_file)) {
    $rate_limits = json_decode(file_get_contents($rate_limit_file), true);
    $number_limit_min = isset($rate_limits['number_limit']) ? (int)$rate_limits['number_limit'] : 0;
    $ip_limit_min = isset($rate_limits['ip_limit']) ? (int)$rate_limits['ip_limit'] : 0;

    if ($number_limit_min > 0 || $ip_limit_min > 0) {
        $current_time = time();
        $logs = file_exists($rate_log_file) ? json_decode(file_get_contents($rate_log_file), true) : [];
        
        // Clean up old logs (older than 60 minutes to be safe)
        $logs = array_filter($logs, function($log) use ($current_time) {
            return ($current_time - $log['time']) < 3600;
        });

        foreach ($logs as $log) {
            // Check Number Limit
            if ($number_limit_min > 0 && $log['number'] === $number) {
                $elapsed = ($current_time - $log['time']) / 60;
                if ($elapsed < $number_limit_min) {
                    $wait = ceil($number_limit_min - $elapsed);
                    echo json_encode(["status" => "error", "message" => "Rate limit: Please wait $wait minute(s) for this number."]);
                    exit;
                }
            }
            // Check IP Limit
            if ($ip_limit_min > 0 && $log['ip'] === $ip) {
                $elapsed = ($current_time - $log['time']) / 60;
                if ($elapsed < $ip_limit_min) {
                    $wait = ceil($ip_limit_min - $elapsed);
                    echo json_encode(["status" => "error", "message" => "Rate limit: Please wait $wait minute(s) for your IP."]);
                    exit;
                }
            }
        }
        
        // Add current request to logs
        $logs[] = ['number' => $number, 'ip' => $ip, 'time' => $current_time];
        file_put_contents($rate_log_file, json_encode(array_values($logs)));
    }
}

// --- 3. API KEY & BALANCE VALIDATION ---
$api_keys_file = 'api_keys.txt'; // Assuming this file exists from previous context
$balance_file = 'balanclamuhadifucke.txt';
$remove_file = 'remove_coin.txt';

if (!file_exists($api_keys_file)) {
    echo json_encode(["status" => "error", "message" => "API keys file not found."]);
    exit;
}

$username = null;
$keys = file($api_keys_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($keys as $key_line) {
    $parts = explode(':', $key_line);
    if (count($parts) >= 2 && trim($parts[1]) === $api_key) {
        $username = trim($parts[0]);
        break;
    }
}

if (!$username) {
    echo json_encode(["status" => "error", "message" => "Invalid API key."]);
    exit;
}

$remove_coin = file_exists($remove_file) ? floatval(trim(file_get_contents($remove_file))) : 1;
$lines = file_exists($balance_file) ? file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$updated_lines = [];
$enough_balance = false;
$new_balance = 0;

foreach ($lines as $line) {
    $parts = explode(':', trim($line));
    if (count($parts) >= 2 && trim($parts[0]) === $username) {
        $current_bal = floatval(trim($parts[1]));
        if ($current_bal >= $remove_coin) {
            $current_bal -= $remove_coin;
            $enough_balance = true;
            $new_balance = $current_bal;
            $line = "$username:$current_bal";
        }
    }
    $updated_lines[] = $line;
}

if (!$enough_balance) {
    echo json_encode(["status" => "error", "message" => "Insufficient balance."]);
    exit;
}

// --- 4. SEND SMS ---
$config_file = 'api/sms.json';
if (!file_exists($config_file)) {
    echo json_encode(["status" => "error", "message" => "SMS API config not found."]);
    exit;
}

$config = json_decode(file_get_contents($config_file), true);
$api_url = str_replace(['{$number}', '{$msg}', '{number}', '{msg}'], [urlencode($number), urlencode($msg), urlencode($number), urlencode($msg)], $config['api_url']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$api_response = curl_exec($ch);
curl_close($ch);

// Save balance and logs
file_put_contents($balance_file, implode("\n", $updated_lines) . "\n");
$log_entry = "$username|$number|$msg|" . date('Y-m-d H:i:s') . "|Success" . PHP_EOL;
file_put_contents("sms_logs.txt", $log_entry, FILE_APPEND);

echo json_encode([
    "status" => "success",
    "message" => "Message sent successfully.",
    "balance_left" => round($new_balance, 2)
]);
?>
