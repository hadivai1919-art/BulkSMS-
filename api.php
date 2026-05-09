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

// Check IP rate limiting
$rate_limit_file = 'rate_limit.txt';
if (file_exists($rate_limit_file)) {
    $rate_limit_data = json_decode(file_get_contents($rate_limit_file), true);
    if ($rate_limit_data && isset($rate_limit_data['ip_limit']) && $rate_limit_data['ip_limit'] > 0) {
        $ip_limit = (int)$rate_limit_data['ip_limit'];
        $current_time = time();
        $ip_log_file = 'ip_requests.txt';
        $ip_requests = [];
        
        if (file_exists($ip_log_file)) {
            $lines = file($ip_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '|') !== false) {
                    list($log_ip, $timestamp) = explode('|', $line);
                    // Check if within limit time window (convert minutes to seconds)
                    if ($current_time - intval($timestamp) < ($ip_limit * 60)) {
                        $ip_requests[$log_ip] = ($ip_requests[$log_ip] ?? 0) + 1;
                    }
                }
            }
        }
        
        // Check if IP has exceeded limit
        if (($ip_requests[$ip] ?? 0) >= 1) {
            echo json_encode(["status" => "error", "message" => "IP rate limit exceeded. Try again later."]);
            exit;
        }
        
        // Log this request
        file_put_contents($ip_log_file, "$ip|$current_time" . PHP_EOL, FILE_APPEND);
    }
}

// Check Number rate limiting
if (file_exists($rate_limit_file)) {
    $rate_limit_data = json_decode(file_get_contents($rate_limit_file), true);
    if ($rate_limit_data && isset($rate_limit_data['number_limit']) && $rate_limit_data['number_limit'] > 0) {
        $number_limit = (int)$rate_limit_data['number_limit'];
        $current_time = time();
        $number_log_file = 'number_requests.txt';
        $number_requests = [];
        
        if (file_exists($number_log_file)) {
            $lines = file($number_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '|') !== false) {
                    list($log_number, $timestamp) = explode('|', $line);
                    // Check if within limit time window (convert minutes to seconds)
                    if ($current_time - intval($timestamp) < ($number_limit * 60)) {
                        $number_requests[$log_number] = ($number_requests[$log_number] ?? 0) + 1;
                    }
                }
            }
        }
        
        // Check if number has exceeded limit
        if (($number_requests[$number] ?? 0) >= 1) {
            echo json_encode(["status" => "error", "message" => "Number rate limit exceeded. Try again later."]);
            exit;
        }
        
        // Log this request
        file_put_contents($number_log_file, "$number|$current_time" . PHP_EOL, FILE_APPEND);
    }
}

// Check spam words
$spam_words_file = 'spam_words.txt';
if (file_exists($spam_words_file)) {
    $spam_words_content = trim(file_get_contents($spam_words_file));
    if (!empty($spam_words_content)) {
        // Split by commas and trim each word
        $spam_words = array_map('trim', explode(',', $spam_words_content));
        
        foreach ($spam_words as $word) {
            $word = trim($word);
            if (!empty($word) && stripos($msg, $word) !== false) {
                echo json_encode(["status" => "error", "message" => "Message contains blocked content."]);
                exit;
            }
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
    if (strpos($key, ':') !== false) {
        list($user, $keyValue) = explode(':', $key);
        if (trim($keyValue) === trim($api_key)) {
            $username = trim($user);
            break;
        }
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
$response_sent = false;

foreach ($lines as $line) {
    if (strpos($line, ':') !== false) {
        list($user, $balance) = explode(':', trim($line));

        if (trim($user) === $username) {
            if ((float)$balance >= $remove_coin) {
                $balance = (float)$balance - $remove_coin;
                $enough_balance = true;
                $deducted = true;
                $new_balance = $balance;

                // Replace placeholders in API URL - IMPORTANT FIX HERE
                $api_url = str_replace(
                    ['{number}', '{msg}', '{$number}', '{$msg}'],
                    [urlencode($number), urlencode($msg), urlencode($number), urlencode($msg)],
                    $config['api_url']
                );
                
                // Debug log
                file_put_contents("api_debug.txt", "API URL: $api_url\n", FILE_APPEND);

                // Make API request with better error handling
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                // Log the request and response
                $log_data = [
                    "ip" => $ip,
                    "username" => $username,
                    "api_key" => $api_key,
                    "number" => $number,
                    "msg" => $msg,
                    "time" => date("Y-m-d H:i:s"),
                    "api_url" => $api_url,
                    "response" => $response,
                    "http_code" => $http_code,
                    "error" => $error
                ];
                file_put_contents("logs.txt", json_encode($log_data) . PHP_EOL, FILE_APPEND);

                // Check if API call was successful
                if ($error || ($http_code < 200 || $http_code >= 300)) {
                    // API call failed, refund the coins
                    $balance = $balance + $remove_coin;
                    $deducted = false;
                    
                    echo json_encode([
                        "status" => "error",
                        "message" => "Failed to send message. API error: " . ($error ?: "HTTP $http_code"),
                        "balance_left" => round($balance, 2)
                    ]);
                } else {
                    echo json_encode([
                        "status" => "success",
                        "message" => "Message sent successfully.",
                        "Api_Owner" => "@hadi_vai1",
                        "balance_left" => round($balance, 2)
                    ]);
                }
                $response_sent = true;
            } else {
                $enough_balance = false;
            }
        }

        $updated_lines[] = (trim($user) === $username) ? "$user:$balance" : $line;
    }
}

if (!$enough_balance && !$response_sent) {
    echo json_encode(["status" => "error", "message" => "Insufficient balance."]);
    exit;
}

// Update balances only if deduction happened and API call was successful
if ($deducted) {
    file_put_contents($balance_file, implode("\n", $updated_lines));
    
    // Also update SMS logs file with the transaction
    $sms_log_entry = "$username|$number|$msg|" . date('Y-m-d H:i:s') . "|Success|" . ($response ?? 'No response');
    file_put_contents("sms_logs.txt", $sms_log_entry . PHP_EOL, FILE_APPEND);
}

// If response already sent and not deducted (refund case)
if (!$deducted && $response_sent) {
    file_put_contents($balance_file, implode("\n", $updated_lines));
    
    // Log failed transaction
    $sms_log_entry = "$username|$number|$msg|" . date('Y-m-d H:i:s') . "|Failed|API Error";
    file_put_contents("sms_logs.txt", $sms_log_entry . PHP_EOL, FILE_APPEND);
}
?>