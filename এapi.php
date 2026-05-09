<?php
header('Content-Type: application/json');

// Get parameters
$api_key = $_REQUEST['key'] ?? '';
$number = $_REQUEST['number'] ?? '';
$msg = $_REQUEST['msg'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
$timestamp = date('Y-m-d H:i:s');
$log_id = uniqid('SMS_', true);

// Initialize log data
$log_data = [
    'id' => $log_id,
    'timestamp' => $timestamp,
    'api_key' => $api_key,
    'number' => $number,
    'message' => $msg,
    'ip' => $ip,
    'status' => '',
    'details' => '',
    'balance_before' => 0,
    'balance_after' => 0
];

if (empty($api_key) || empty($number) || empty($msg)) {
    $response = ["status" => "error", "message" => "Missing required parameters (key, number, msg)."];
    $log_data['status'] = 'error';
    $log_data['details'] = 'Missing parameters';
    saveToLogs($log_data);
    echo json_encode($response);
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
                $response = ["status" => "error", "message" => "Message contains blocked content (Spam)."];
                $log_data['status'] = 'error';
                $log_data['details'] = 'Spam detected: ' . $word;
                saveToLogs($log_data);
                echo json_encode($response);
                exit;
            }
        }
    }
}

// --- 2. RATE LIMITING (IP & NUMBER) ---
$rate_limit_file = 'rate_limit.txt';
$rate_log_file = 'rate_limit_logs.json';

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
                    $response = ["status" => "error", "message" => "Rate limit: Please wait $wait minute(s) for this number."];
                    $log_data['status'] = 'error';
                    $log_data['details'] = "Rate limit - Number: $wait min wait";
                    saveToLogs($log_data);
                    echo json_encode($response);
                    exit;
                }
            }
            // Check IP Limit
            if ($ip_limit_min > 0 && $log['ip'] === $ip) {
                $elapsed = ($current_time - $log['time']) / 60;
                if ($elapsed < $ip_limit_min) {
                    $wait = ceil($ip_limit_min - $elapsed);
                    $response = ["status" => "error", "message" => "Rate limit: Please wait $wait minute(s) for your IP."];
                    $log_data['status'] = 'error';
                    $log_data['details'] = "Rate limit - IP: $wait min wait";
                    saveToLogs($log_data);
                    echo json_encode($response);
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
$api_keys_file = 'api_keys.txt';
$balance_file = 'balanclamuhadifucke.txt';
$remove_file = 'remove_coin.txt';

if (!file_exists($api_keys_file)) {
    $response = ["status" => "error", "message" => "API keys file not found."];
    $log_data['status'] = 'error';
    $log_data['details'] = 'API keys file missing';
    saveToLogs($log_data);
    echo json_encode($response);
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
    $response = ["status" => "error", "message" => "Invalid API key."];
    $log_data['status'] = 'error';
    $log_data['details'] = 'Invalid API key';
    saveToLogs($log_data);
    echo json_encode($response);
    exit;
}

$log_data['username'] = $username;

$remove_coin = file_exists($remove_file) ? floatval(trim(file_get_contents($remove_file))) : 1;
$lines = file_exists($balance_file) ? file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$updated_lines = [];
$enough_balance = false;
$new_balance = 0;
$old_balance = 0;

foreach ($lines as $line) {
    $parts = explode(':', trim($line));
    if (count($parts) >= 2 && trim($parts[0]) === $username) {
        $current_bal = floatval(trim($parts[1]));
        $old_balance = $current_bal;
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
    $response = ["status" => "error", "message" => "Insufficient balance."];
    $log_data['status'] = 'error';
    $log_data['details'] = 'Insufficient balance';
    $log_data['balance_before'] = $old_balance;
    $log_data['balance_after'] = $old_balance;
    saveToLogs($log_data);
    echo json_encode($response);
    exit;
}

$log_data['balance_before'] = $old_balance;
$log_data['balance_after'] = $new_balance;

// --- 4. SEND SMS ---
$config_file = 'api/sms.json';
if (!file_exists($config_file)) {
    $response = ["status" => "error", "message" => "SMS API config not found."];
    $log_data['status'] = 'error';
    $log_data['details'] = 'SMS config file missing';
    saveToLogs($log_data);
    echo json_encode($response);
    exit;
}

$config = json_decode(file_get_contents($config_file), true);
$api_url = str_replace(['{$number}', '{$msg}', '{number}', '{msg}'], [urlencode($number), urlencode($msg), urlencode($number), urlencode($msg)], $config['api_url']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'SMS-API/1.0');
$api_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Determine SMS sending status
$sms_sent = ($http_code >= 200 && $http_code < 300) ? true : false;
$sms_status = $sms_sent ? 'Success' : 'Failed';
$sms_details = "HTTP Code: $http_code | Response: " . substr($api_response, 0, 100);

// Save balance and logs
file_put_contents($balance_file, implode("\n", $updated_lines) . "\n");

// Save to sms_logs.txt (original format)
$sms_log_entry = "$username|$number|$msg|$timestamp|$sms_status" . PHP_EOL;
file_put_contents("sms_logs.txt", $sms_log_entry, FILE_APPEND);

// Update log data
$log_data['status'] = $sms_sent ? 'success' : 'failed';
$log_data['details'] = $sms_details;
$log_data['sms_status'] = $sms_status;
$log_data['http_code'] = $http_code;

// Save to logs.txt (detailed format)
saveToLogs($log_data);

if ($sms_sent) {
    $response = [
        "status" => "success",
        "message" => "Message sent successfully.",
        "balance_left" => round($new_balance, 2),
        "log_id" => $log_id
    ];
    echo json_encode($response);
} else {
    $response = [
        "status" => "error",
        "message" => "Failed to send SMS. API returned error.",
        "balance_left" => round($new_balance, 2),
        "log_id" => $log_id
    ];
    echo json_encode($response);
}

/**
 * Save log data to logs.txt file
 * @param array $log_data Array containing log information
 */
function saveToLogs($log_data) {
    $log_file = 'logs.txt';
    
    // Format log entry as JSON for easy parsing
    $log_entry = json_encode($log_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    
    // Append to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
?>