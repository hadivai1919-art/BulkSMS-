<?php
header('Content-Type: application/json');

// Get parameters from GET or POST
$api_key = $_REQUEST['key'] ?? '';
$number = $_REQUEST['number'] ?? '';
$msg = $_REQUEST['msg'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

if (empty($api_key) || empty($number) || empty($msg)) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters (key, number, msg)."]);
    exit;
}

// Check rate limiting
$rate_limit_file = 'rate_limit_log.txt'; // Changed name to avoid conflict with config
$rate_config_file = 'rate_limit.txt';

if (file_exists($rate_config_file)) {
    $rate_limit = intval(trim(file_get_contents($rate_config_file)));
    if ($rate_limit > 0) {
        $current_time = time();
        $ip_requests = 0;
        
        if (file_exists($rate_limit_file)) {
            $lines = file($rate_limit_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $new_lines = [];
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) === 2) {
                    list($log_ip, $timestamp) = $parts;
                    if ($current_time - $timestamp < 60) { // Within last minute
                        $new_lines[] = $line;
                        if ($log_ip === $ip) {
                            $ip_requests++;
                        }
                    }
                }
            }
            // Clean up old logs
            file_put_contents($rate_limit_file, implode(PHP_EOL, $new_lines) . (empty($new_lines) ? "" : PHP_EOL));
        }
        
        if ($ip_requests >= $rate_limit) {
            echo json_encode(["status" => "error", "message" => "Rate limit exceeded. Try again later."]);
            exit;
        }
        
        // Log this request
        file_put_contents($rate_limit_file, "$ip|$current_time" . PHP_EOL, FILE_APPEND);
    }
}

// Check spam words
$spam_words_file = 'spam_words.txt';
if (file_exists($spam_words_file)) {
    $spam_words = file($spam_words_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($spam_words as $word) {
        if (!empty(trim($word)) && stripos($msg, trim($word)) !== false) {
            echo json_encode(["status" => "error", "message" => "Message contains blocked content."]);
            exit;
        }
    }
}

// Load SMS API config
$config_file = 'api/sms.json';
if (!file_exists($config_file)) {
    echo json_encode(["status" => "error", "message" => "SMS API config not found at $config_file"]);
    exit;
}

$config = json_decode(file_get_contents($config_file), true);
if (!$config || !isset($config['api_url']) || (isset($config['status']) && $config['status'] !== 'on')) {
    echo json_encode(["status" => "error", "message" => "SMS API is disabled or misconfigured."]);
    exit;
}

// Load removal coin amount
$remove_file = 'remove_coin.txt';
$remove_coin = 1; // Default fallback
if (file_exists($remove_file)) {
    $remove_coin = floatval(trim(file_get_contents($remove_file)));
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
foreach ($keys as $key_line) {
    $parts = explode(':', $key_line);
    if (count($parts) >= 2) {
        list($user, $keyValue) = $parts;
        if (trim($keyValue) === $api_key) {
            $username = trim($user);
            break;
        }
    }
}

if (!$username) {
    echo json_encode(["status" => "error", "message" => "Invalid API key."]);
    exit;
}

// Load balances
$balance_file = 'balanclamuhadifucke.txt';
if (!file_exists($balance_file)) {
    echo json_encode(["status" => "error", "message" => "Balance file not found."]);
    exit;
}

// Process balance update
$lines = file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$updated_lines = [];
$user_found = false;
$enough_balance = false;
$new_balance = 0;

foreach ($lines as $line) {
    $parts = explode(':', trim($line));
    if (count($parts) >= 2) {
        list($user, $balance) = $parts;
        $user = trim($user);
        $balance = floatval(trim($balance));

        if ($user === $username) {
            $user_found = true;
            if ($balance >= $remove_coin) {
                $balance -= $remove_coin;
                $enough_balance = true;
                $new_balance = $balance;
                
                // Prepare API URL
                $api_url = str_replace(
                    ['{$number}', '{$msg}'],
                    [urlencode($number), urlencode($msg)],
                    $config['api_url']
                );

                // Use cURL for better reliability than file_get_contents
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $api_response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                // Log the request
                $log_data = [
                    "ip" => $ip,
                    "username" => $username,
                    "api_key" => $api_key,
                    "number" => $number,
                    "msg" => $msg,
                    "time" => date("Y-m-d H:i:s"),
                    "api_response_code" => $http_code
                ];
                file_put_contents("logs.txt", json_encode($log_data) . PHP_EOL, FILE_APPEND);

                // Save updated balance
                $updated_lines[] = "$user:$balance";
                continue;
            }
        }
    }
    $updated_lines[] = $line;
}

if (!$user_found) {
    echo json_encode(["status" => "error", "message" => "User not found in balance records."]);
    exit;
}

if (!$enough_balance) {
    echo json_encode(["status" => "error", "message" => "Insufficient balance. Required: $remove_coin"]);
    exit;
}

// Update balance file
file_put_contents($balance_file, implode(PHP_EOL, $updated_lines) . PHP_EOL);

// Log SMS
$sms_log_entry = "$username|$number|$msg|" . date('Y-m-d H:i:s') . "|Success";
file_put_contents("sms_logs.txt", $sms_log_entry . PHP_EOL, FILE_APPEND);

echo json_encode([
    "status" => "success",
    "message" => "Message sent successfully.",
    "Api_Owner" => "@hadi_vai1",
    "balance_left" => round($new_balance, 2)
]);
?>
