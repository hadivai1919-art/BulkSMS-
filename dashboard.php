<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

header('Content-Type: text/html');

// Domain and Directory to API URL
$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$baseApiUrl = "$domain$dir/api.php";

// File paths
$balance_file = "balanclamuhadifucke.txt";
$notice_file = "notices.txt";
$bonus_data_file = "bonus_data.json";
$bonus_claimed_file = "bonus_claimed.txt";
$apk_file = "apk_link.txt";
$referral_config_file = "referral_config.txt";
$referral_mapping_file = "referral_mapping.txt";
$sms_logs_file = "sms_logs.txt";
$api_keys_file = "api_keys.txt";

// Generate or get random referral ID for user
function getReferralId($username) {
    global $referral_mapping_file;
    
    if (!file_exists($referral_mapping_file)) {
        file_put_contents($referral_mapping_file, '');
    }
    
    $mappings = file($referral_mapping_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($mappings as $mapping) {
        list($user, $refId) = explode(':', $mapping);
        if ($user === $username) {
            return $refId;
        }
    }
    
    $refId = bin2hex(random_bytes(8));
    file_put_contents($referral_mapping_file, "$username:$refId" . PHP_EOL, FILE_APPEND);
    
    return $refId;
}

// Generate or get API key for user
function getApiKey($username) {
    global $api_keys_file;
    
    if (!file_exists($api_keys_file)) {
        file_put_contents($api_keys_file, '');
    }
    
    $keys = file($api_keys_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($keys as $key) {
        list($user, $apiKey) = explode(':', $key);
        if ($user === $username) {
            return $apiKey;
        }
    }
    
    $apiKey = bin2hex(random_bytes(16));
    file_put_contents($api_keys_file, "$username:$apiKey" . PHP_EOL, FILE_APPEND);
    
    return $apiKey;
}

function getUsernameFromReferralId($refId) {
    global $referral_mapping_file;
    
    if (!file_exists($referral_mapping_file)) return false;
    
    $mappings = file($referral_mapping_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($mappings as $mapping) {
        list($user, $id) = explode(':', $mapping);
        if ($id === $refId) {
            return $user;
        }
    }
    
    return false;
}

function getUserBalance($username) {
    global $balance_file;
    if (!file_exists($balance_file)) return 0;
    $lines = file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        list($userFromFile, $bal) = explode(':', $line);
        if (trim($userFromFile) === trim($username)) {
            return $bal;
        }
    }
    return 0;
}

function redeemCoin($username, $code) {
    if (!file_exists('redeem.txt')) {
        return ['success' => false, 'message' => '❌ No redeem codes available'];
    }
    
    if (!file_exists('redeem_user.txt')) {
        file_put_contents('redeem_user.txt', '');
    }
    
    $timestamp = date('Y-m-d H:i:s');
    
    $usedCodes = file('redeem_user.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($usedCodes as $usedCode) {
        $parts = explode(':', $usedCode);
        $user = $parts[0];
        $usedCodeValue = $parts[1];
        if ($user === $username && $usedCodeValue === $code) {
            $usedTime = isset($parts[2]) ? $parts[2] : 'unknown time';
            return ['success' => false, 'message' => "❌ You have already used this code at $usedTime"];
        }
    }
    
    $redeemCodes = file('redeem.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newRedeemCodes = [];
    $found = false;
    $coinsToAdd = 0;
    
    foreach ($redeemCodes as $redeemLine) {
        list($codeFromFile, $coins, $limit) = explode(':', $redeemLine);
        
        if (trim($codeFromFile) === trim($code)) {
            if ($limit === 'unlimited') {
                $coinsToAdd = $coins;
                $found = true;
                $newRedeemCodes[] = $redeemLine;
            } elseif (intval($limit) > 0) {
                $coinsToAdd = $coins;
                $found = true;
                $newLimit = intval($limit) - 1;
                if ($newLimit > 0) {
                    $newRedeemCodes[] = "$codeFromFile:$coins:$newLimit";
                }
            }
        } else {
            $newRedeemCodes[] = $redeemLine;
        }
    }
    
    if (!$found) {
        return ['success' => false, 'message' => '❌ Invalid redeem code'];
    }
    
    file_put_contents('redeem.txt', implode(PHP_EOL, $newRedeemCodes));
    file_put_contents('redeem_user.txt', "$username:$code:$timestamp" . PHP_EOL, FILE_APPEND);
    
    $currentBalance = getUserBalance($username);
    $newBalance = $currentBalance + $coinsToAdd;
    
    $balanceLines = file_exists($balance_file) ? file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $newBalanceLines = [];
    $balanceUpdated = false;
    
    foreach ($balanceLines as $line) {
        list($userFromFile, $bal) = explode(':', $line);
        if (trim($userFromFile) === trim($username)) {
            $newBalanceLines[] = "$userFromFile:$newBalance";
            $balanceUpdated = true;
        } else {
            $newBalanceLines[] = $line;
        }
    }
    
    if (!$balanceUpdated) {
        $newBalanceLines[] = "$username:$newBalance";
    }
    
    file_put_contents($balance_file, implode(PHP_EOL, $newBalanceLines));
    
    return [
        'success' => true,
        'message' => "✅ Successfully redeemed $coinsToAdd coins!",
        'balance' => $newBalance
    ];
}

function getNoticesForUser($username) {
    global $notice_file;
    if (!file_exists($notice_file)) return [];
    
    $notices = file($notice_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $userNotices = [];
    
    foreach ($notices as $noticeJson) {
        $notice = json_decode($noticeJson, true);
        if ($notice && ($notice['target'] === 'all' || $notice['target'] === $username)) {
            $userNotices[] = $notice;
        }
    }
    
    return array_reverse($userNotices);
}

function getAvailableBonuses($username) {
    global $bonus_data_file, $bonus_claimed_file;
    if (!file_exists($bonus_data_file)) return [];
    
    $bonuses = json_decode(file_get_contents($bonus_data_file), true) ?: [];
    $availableBonuses = [];
    
    $claimed = [];
    if (file_exists($bonus_claimed_file)) {
        $claimedLines = file($bonus_claimed_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($claimedLines as $line) {
            list($user, $bonusId) = explode(':', $line);
            if ($user === $username) {
                $claimed[] = $bonusId;
            }
        }
    }
    
    foreach ($bonuses as $id => $bonus) {
        $isExpired = (time() - $bonus['time']) > $bonus['duration'];
        $alreadyClaimed = in_array($id, $claimed);
        $isForUser = $bonus['target'] === 'all' || $bonus['target'] === $username;
        $limitReached = $bonus['user_limit'] !== 'unlimited' && 
                        count($bonus['claimed_by']) >= $bonus['user_limit'];
        
        if (!$isExpired && !$alreadyClaimed && $isForUser && !$limitReached) {
            $availableBonuses[$id] = $bonus;
        }
    }
    
    return $availableBonuses;
}

function claimBonus($username, $bonusId) {
    global $bonus_data_file, $bonus_claimed_file, $balance_file;
    
    if (!file_exists($bonus_data_file)) {
        return ['success' => false, 'message' => '❌ Bonus data not found'];
    }
    
    $bonuses = json_decode(file_get_contents($bonus_data_file), true) ?: [];
    
    if (!isset($bonuses[$bonusId])) {
        return ['success' => false, 'message' => '❌ Invalid bonus ID'];
    }
    
    $bonus = $bonuses[$bonusId];
    
    if ((time() - $bonus['time']) > $bonus['duration']) {
        return ['success' => false, 'message' => '❌ This bonus has expired'];
    }
    
    $claimed = [];
    if (file_exists($bonus_claimed_file)) {
        $claimedLines = file($bonus_claimed_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($claimedLines as $line) {
            list($user, $id) = explode(':', $line);
            if ($user === $username && $id === $bonusId) {
                return ['success' => false, 'message' => '❌ You have already claimed this bonus'];
            }
        }
    }
    
    if ($bonus['target'] !== 'all' && $bonus['target'] !== $username) {
        return ['success' => false, 'message' => '❌ This bonus is not for you'];
    }
    
    if ($bonus['user_limit'] !== 'unlimited' && 
        count($bonus['claimed_by']) >= $bonus['user_limit']) {
        return ['success' => false, 'message' => '❌ This bonus has reached its claim limit'];
    }
    
    file_put_contents($bonus_claimed_file, "$username:$bonusId" . PHP_EOL, FILE_APPEND);
    
    $bonuses[$bonusId]['claimed_by'][] = [
        'username' => $username,
        'time' => time()
    ];
    file_put_contents($bonus_data_file, json_encode($bonuses, JSON_PRETTY_PRINT));
    
    $currentBalance = getUserBalance($username);
    $newBalance = $currentBalance + $bonus['amount'];
    
    $balanceLines = file_exists($balance_file) ? file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $newBalanceLines = [];
    $balanceUpdated = false;
    
    foreach ($balanceLines as $line) {
        list($userFromFile, $bal) = explode(':', $line);
        if (trim($userFromFile) === trim($username)) {
            $newBalanceLines[] = "$userFromFile:$newBalance";
            $balanceUpdated = true;
        } else {
            $newBalanceLines[] = $line;
        }
    }
    
    if (!$balanceUpdated) {
        $newBalanceLines[] = "$username:$newBalance";
    }
    
    file_put_contents($balance_file, implode(PHP_EOL, $newBalanceLines));
    
    return [
        'success' => true,
        'message' => "✅ Successfully claimed {$bonus['amount']} coins!",
        'balance' => $newBalance,
        'bonus' => $bonus
    ];
}

function getUserSmsLogs($username) {
    global $sms_logs_file;
    if (!file_exists($sms_logs_file)) return [];
    
    $logs = file($sms_logs_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $userLogs = [];
    
    foreach ($logs as $log) {
        $parts = explode('|', $log);
        if (count($parts) >= 4 && $parts[0] === $username) {
            // Convert timestamp to Bangladesh time (UTC+6)
            $timestamp = date('d M Y, h:i A', strtotime($parts[3]) + (6 * 3600));
            $userLogs[] = [
                'number' => $parts[1],
                'message' => $parts[2],
                'time' => $timestamp,
                'status' => $parts[4] ?? 'Sent'
            ];
        }
    }
    
    return array_reverse($userLogs);
}

$apk_link = file_exists($apk_file) ? file_get_contents($apk_file) : '';
$referral_reward = file_exists($referral_config_file) ? (int)file_get_contents($referral_config_file) : 0;
$referral_id = getReferralId($_SESSION['username']);
$referral_link = "$domain$dir?ref=$referral_id";
$api_key = getApiKey($_SESSION['username']);

$username = $_SESSION['username'];
$userFile = "users/$username.txt";

if (!file_exists($userFile)) {
    echo "❌ User file not found!";
    exit();
}

$user = json_decode(file_get_contents($userFile), true);
$notices = getNoticesForUser($username);
$availableBonuses = getAvailableBonuses($username);
$smsLogs = getUserSmsLogs($username);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (isset($_POST['redeem'])) {
        $code = trim($_POST['code'] ?? '');
        
        if (!$code) {
            echo json_encode(['response' => '❌ Redeem code required']);
            exit();
        }
        
        $result = redeemCoin($username, $code);
        echo json_encode([
            'response' => $result['message'],
            'balance' => $result['balance'] ?? getUserBalance($username),
            'success' => $result['success']
        ]);
        exit();
    }
    
    if (isset($_POST['claim_bonus'])) {
        $bonusId = trim($_POST['bonus_id'] ?? '');
        
        if (!$bonusId) {
            echo json_encode(['success' => false, 'message' => '❌ Bonus ID required']);
            exit();
        }
        
        $result = claimBonus($username, $bonusId);
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['message'],
            'balance' => $result['balance'] ?? getUserBalance($username)
        ]);
        exit();
    }
    
    if (isset($_POST['check_notices'])) {
        $notices = getNoticesForUser($username);
        $bonuses = getAvailableBonuses($username);
        
        echo json_encode([
            'notices' => $notices,
            'bonuses' => $bonuses
        ]);
        exit();
    }
    
    if (isset($_POST['get_sms_logs'])) {
        $logs = getUserSmsLogs($username);
        echo json_encode(['logs' => $logs]);
        exit();
    }
    
    $targetNumber = trim($_POST['number'] ?? '');
    $message = trim($_POST['msg'] ?? '');

    if (!$targetNumber || !$message) {
        echo json_encode(['response' => '❌ Number and message required']);
        exit();
    }

    $encodedNumber = urlencode($targetNumber);
    $encodedMessage = urlencode($message);
    $apiUrl = "$baseApiUrl?key=$api_key&number=$encodedNumber&msg=$encodedMessage";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $response = "❌ cURL Error: " . curl_error($ch);
    } elseif ($response === false) {
        $response = "❌ No response from API.";
    }

    curl_close($ch);
    
    // Log the SMS
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "$username|$targetNumber|$message|$timestamp|$response";
    file_put_contents($sms_logs_file, $logEntry . PHP_EOL, FILE_APPEND);

    echo json_encode([
        'response' => $response,
        'balance' => getUserBalance($username)
    ]);
    exit();
}

$balance = getUserBalance($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e6e9ff;
            --secondary: #3a0ca3;
            --accent: #4895ef;
            --text: #2b2d42;
            --text-light: #8d99ae;
            --bg: #f8f9fa;
            --card: #ffffff;
            --border: #e9ecef;
            --success: #4cc9f0;
            --error: #f72585;
            --warning: #f8961e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 0;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Top Navigation */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: var(--card);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .menu-toggle {
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        
        .menu-toggle span {
            width: 25px;
            height: 3px;
            background-color: var(--primary);
            margin: 3px 0;
            border-radius: 3px;
        }
        
        .balance-display {
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 70px;
            left: -300px;
            width: 280px;
            height: calc(100vh - 70px);
            background-color: var(--card);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 999;
            overflow-y: auto;
        }
        
        .sidebar.active {
            left: 0;
        }
        
        .nav-menu {
            list-style: none;
            padding: 20px;
        }
        
        .nav-item {
            margin-bottom: 10px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
        }
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .nav-link:hover {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .nav-link.active {
            background-color: var(--primary);
            color: white;
        }
        
        .notification-badge {
            background-color: var(--error);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-left: auto;
        }
        
        /* Main Content */
        .main-content {
            background-color: var(--card);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }
        
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--text);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        /* Button Container */
        .button-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .btn-secondary {
            background-color: var(--accent);
        }
        
        .btn-telegram {
            background-color: #0088cc;
        }
        
        .btn-redeem {
            background-color: #3a0ca3;
        }
        
        .btn-warning {
            background-color: var(--warning);
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-notification {
            background-color: var(--error);
        }
        
        /* Response Box */
        .response-box {
            margin-top: 25px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary);
            font-family: 'Courier New', monospace;
            font-size: 14px;
            display: none;
        }
        
        /* Profile Info */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .info-card {
            background-color: var(--bg);
            border-radius: 8px;
            padding: 20px;
        }
        
        .info-label {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
            word-break: break-all;
        }
        
        /* API Section */
        .api-card {
            background-color: var(--bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .api-method {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: var(--primary);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .api-url {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            margin: 15px 0;
            word-break: break-all;
        }
        
        .copy-btn {
            background-color: var(--primary-light);
            color: var(--primary);
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        
        .copy-btn i {
            margin-right: 5px;
        }
        
        .copy-btn:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .api-params {
            margin-top: 20px;
        }
        
        .param-item {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }
        
        .param-name {
            width: 100px;
            font-weight: 500;
            color: var(--primary);
        }
        
        .param-desc {
            flex: 1;
            color: var(--text-light);
        }
        
        /* Notice and Bonus Popup Styles */
        .popup-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .popup-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideInUp 0.3s ease-in-out;
        }
        
        .popup-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #777;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .popup-close:hover {
            color: var(--error);
            transform: rotate(90deg);
        }
        
        .popup-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .popup-message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
            color: var(--text);
        }
        
        .popup-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        /* Notice specific styles */
        .notice-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .notice-content {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
            padding: 15px;
            background-color: var(--bg);
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        /* Bonus specific styles */
        .bonus-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--warning);
            text-align: center;
            margin-bottom: 15px;
        }
        
        .bonus-reason {
            font-size: 18px;
            text-align: center;
            margin-bottom: 15px;
            color: var(--text);
            font-weight: 500;
        }
        
        .bonus-amount {
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            color: var(--success);
            margin-bottom: 20px;
        }
        
        .bonus-details {
            font-size: 14px;
            color: var(--text-light);
            text-align: center;
            margin-bottom: 20px;
        }
        
        /* SMS Logs Section */
        .logs-container {
            margin-top: 20px;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .logs-table th, 
        .logs-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .logs-table th {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }
        
        .logs-table tr:hover {
            background-color: var(--bg);
        }
        
        .log-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .log-status.success {
            background-color: #e6f7ee;
            color: #00a854;
        }
        
        .log-status.error {
            background-color: #fff1f0;
            color: #f5222d;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 0.5s ease;
        }
        
        /* Loading */
        .loading {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Referral Section Styles */
        .referral-card {
            background-color: var(--bg);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .referral-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .referral-title i {
            margin-right: 10px;
            color: var(--accent);
        }
        
        .referral-link-container {
            display: flex;
            margin-bottom: 20px;
        }
        
        .referral-link {
            flex: 1;
            padding: 12px 15px;
            background-color: white;
            border: 1px solid var(--border);
            border-radius: 8px 0 0 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        
        .referral-copy {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0 15px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .referral-copy:hover {
            background-color: var(--secondary);
        }
        
        .referral-info {
            background-color: var(--primary-light);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .referral-reward {
            font-size: 16px;
            font-weight: 500;
            color: var(--primary);
        }
        
        /* APK Section Styles */
        .apk-card {
            background-color: var(--bg);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .apk-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .apk-title i {
            margin-right: 10px;
            color: var(--accent);
        }
        
        .apk-download-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            background-color: var(--success);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
        }
        
        .apk-download-btn:hover {
            background-color: #3aa8d8;
            transform: translateY(-2px);
        }
        
        .apk-download-btn i {
            margin-right: 8px;
        }
        
        .apk-unavailable {
            color: var(--text-light);
            font-style: italic;
        }
        
        /* Scrollable popup content */
        .scrollable-popup {
            max-height: 70vh;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        /* SMS Logs Section */
        .logs-section {
            margin-top: 20px;
            padding: 20px;
            background-color: var(--bg);
            border-radius: 12px;
        }
        
        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .refresh-logs {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .refresh-logs i {
            margin-right: 5px;
        }
        
        .refresh-logs:hover {
            background-color: var(--secondary);
        }
        
        /* Notification List Styles */
        .notification-list {
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: var(--primary-light);
        }
        
        .notification-item i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: var(--text);
        }
        
        .notification-preview {
            font-size: 14px;
            color: var(--text-light);
        }
        
        /* Support Section Styles */
        .support-section {
            padding: 20px;
            background-color: var(--bg);
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .support-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .support-description {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            text-align: center;
            color: var(--text);
        }
        
        .support-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .support-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .support-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .support-button i {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .support-button.telegram i {
            color: #0088cc;
        }
        
        .support-button.call i {
            color: #25D366;
        }
        
        .support-button.mail i {
            color: #EA4335;
        }
        
        .support-button.youtube i {
            color: #FF0000;
        }
        
        .support-button-text {
            font-weight: 600;
            text-align: center;
        }
        
        .buy-sms-section {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .buy-sms-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .buy-sms-title i {
            margin-right: 10px;
            color: var(--accent);
        }
        
        .price-list {
            margin: 20px 0;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        
        .price-label {
            font-weight: 500;
        }
        
        .price-value {
            color: var(--primary);
            font-weight: 600;
        }
        
        .sms-info {
            background-color: var(--primary-light);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .sms-info-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .sms-info-list {
            list-style-type: disc;
            padding-left: 20px;
        }
        
        .sms-info-list li {
            margin-bottom: 8px;
        }
        
        .buy-sms-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            background-color: var(--success);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
        }
        
        .buy-sms-button:hover {
            background-color: #3aa8d8;
            transform: translateY(-2px);
        }
        
        .buy-sms-button i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!-- Notification List Popup -->
    <div class="popup-container" id="notificationListPopup" style="display: none;">
        <div class="popup-content" style="max-width: 600px;">
            <span class="popup-close" onclick="closeNotificationList()">&times;</span>
            <div class="notice-title">All Notifications</div>
            <div class="notification-list scrollable-popup" id="notificationList">
                <!-- Notifications will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Top Navigation -->
    <div class="top-nav">
        <div class="menu-toggle" id="menuToggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="balance-display">
            Balance: <span id="balanceText"><?php echo $balance; ?></span> coins
        </div>
    </div>
    
    <!-- Sidebar Menu -->
    <div class="sidebar" id="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="#" class="nav-link active" data-target="send-section">
                    <i class="fas fa-paper-plane"></i> Send Message
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="redeem-section">
                    <i class="fas fa-gift"></i> Redeem Coin
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="sms-logs-section">
                    <i class="fas fa-history"></i> SMS Logs
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="apk-section">
                    <i class="fas fa-download"></i> Download APK
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="referral-section">
                    <i class="fas fa-user-plus"></i> Referral Program
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="profile-section">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="api-section">
                    <i class="fas fa-code"></i> API
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="support-section">
                    <i class="fas fa-headset"></i> Support
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" id="notificationMenuBtn">
                    <i class="fas fa-bell"></i> Admin Notifications
                    <?php if (!empty($notices) || !empty($availableBonuses)): ?>
                        <div class="notification-badge" id="menuNotificationCount">
                            <?php echo count($notices) + count($availableBonuses); ?>
                        </div>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="add_coin.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i> Add Coin
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Send Message Section -->
            <div id="send-section" class="section active">
                <h2 class="section-title">
                    <i class="fas fa-paper-plane"></i> Send Message
                </h2>
                
                <div class="button-container">
                    <a href="https://mhbulksms.mooo.com/tg.php" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-headset"></i>BuyCoin                    </a>
                    <a href="https://t.me/custom_sms" class="btn btn-telegram" target="_blank">
                        <i class="fab fa-telegram"></i> Join Channel
                    </a>
                </div>
                
                <form id="sendForm">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="number" class="form-control" placeholder="e.g. 01234567890" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea name="msg" class="form-control" placeholder="Type your message here..." required></textarea>
                    </div>
                    
                    <input type="hidden" name="ajax" value="true">
                    
                    <button type="submit" class="btn btn-block" id="sendButton">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
                
                <div id="responseBox" class="response-box"></div>
            </div>
            
            <!-- Redeem Coin Section -->
            <div id="redeem-section" class="section">
                <h2 class="section-title">
                    <i class="fas fa-gift"></i> Redeem Coin
                </h2>
                
                <form id="redeemForm">
                    <div class="form-group">
                        <label class="form-label">Redeem Code</label>
                        <input type="text" name="code" class="form-control" placeholder="Enter your redeem code" required>
                    </div>
                    
                    <input type="hidden" name="ajax" value="true">
                    <input type="hidden" name="redeem" value="true">
                    
                    <button type="submit" class="btn btn-redeem btn-block" id="redeemButton">
                        <i class="fas fa-gift"></i> Redeem Coin
                    </button>
                </form>
                
                <div id="redeemResponseBox" class="response-box"></div>
            </div>
            
            <!-- SMS Logs Section -->
            <div id="sms-logs-section" class="section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> SMS Logs
                </h2>
                
                <div class="logs-section">
                    <div class="logs-header">
                        <h3>Your Message History</h3>
                        <button class="refresh-logs" id="refreshLogs">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    
                    <div class="logs-container">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>Number</th>
                                    <th>Message</th>
                                    <th>Time (BD)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <?php if (!empty($smsLogs)): ?>
                                    <?php foreach ($smsLogs as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['number']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($log['message'], 0, 50)); ?><?php echo strlen($log['message']) > 50 ? '...' : ''; ?></td>
                                            <td><?php echo htmlspecialchars($log['time']); ?></td>
                                            <td>
                                                <span class="log-status <?php echo strpos($log['status'], '❌') === false ? 'success' : 'error'; ?>">
                                                    <?php echo htmlspecialchars($log['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">No SMS logs found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- APK Download Section -->
            <div id="apk-section" class="section">
                <h2 class="section-title">
                    <i class="fas fa-download"></i> Download APK
                </h2>
                
                <div class="apk-card">
                    <h3 class="apk-title">
                        <i class="fas fa-mobile-alt"></i> App Download
                    </h3>
                    
                    <?php if (!empty($apk_link)): ?>
                        <p>Download the latest version of our app:</p>
                        <a href="<?php echo htmlspecialchars($apk_link); ?>" class="apk-download-btn" download>
                            <i class="fas fa-download"></i> Download APK
                        </a>
                    <?php else: ?>
                        <p class="apk-unavailable">APK download is not currently available. Please check back later.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Referral Program Section -->
            <div id="referral-section" class="section">
                <h2 class="section-title">
                    <i class="fas fa-user-plus"></i> Referral Program
                </h2>
                
                <div class="referral-card">
                    <h3 class="referral-title">
                        <i class="fas fa-share-alt"></i> Your Referral Link
                    </h3>
                    
                    <div class="referral-link-container">
                        <div class="referral-link" id="referralLink"><?php echo $referral_link; ?></div>
                        <button class="referral-copy" onclick="copyReferralLink()">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                    
                    <div class="referral-info">
                        <p>Share your referral link with friends and earn <span class="referral-reward"><?php echo $referral_reward; ?> coins</span> for each successful referral!</p>
                        <p>When someone signs up using your link, both of you will receive the bonus.</p>
                    </div>
                </div>
            </div>
            
            <!-- Profile Section -->
            <div id="profile-section" class="section">
                <h2 class="section-title">
                    <i class="fas fa-user"></i> Profile Information
                </h2>
                
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Username</div>
                        <div class="info-value">@<?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['number']); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Password</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['password']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- API Section -->
            <div id="api-section" class="section">
                <h2 class="section-title">
                    <i class="fas fa-code"></i> API Documentation
                </h2>
                
                <div class="api-card">
                    <span class="api-method">GET</span>
                    <p>Use this endpoint to send messages via API:</p>

                    <div class="api-url" id="apiEndpoint">
                        <?php 
                        $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                        $dir = dirname($_SERVER['SCRIPT_NAME']);
                        echo "$domain$dir/api.php?key=$api_key&number=PHONE_NUMBER&msg=MESSAGE_CONTENT";
                        ?>
                    </div>

                    <button class="copy-btn" onclick="copyApiEndpoint()">
                        <i class="far fa-copy"></i> Copy API Endpoint
                    </button>
                    
                    <div class="api-params">
                        <h3>Parameters:</h3>
                        <div class="param-item">
                            <div class="param-name">key</div>
                            <div class="param-desc">Your API key (shown above, keep it secret)</div>
                        </div>
                        <div class="param-item">
                            <div class="param-name">number</div>
                            <div class="param-desc">Target phone number (with country code, no + sign)</div>
                        </div>
                        <div class="param-item">
                            <div class="param-name">msg</div>
                            <div class="param-desc">URL-encoded message content</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Support Section -->
            <div id="support-section" class="section">
                <h2 class="section-title">
                    <i class="fas fa-headset"></i> Support
                </h2>
                
                <div class="support-section">
                    <h3 class="support-title">We're Here to Help</h3>
                    <p class="support-description">
                        Our SMS service provides reliable and fast messaging solutions. 
                        Contact us through any of the following channels for assistance.
                    </p>
                    
                    <div class="support-buttons">
                        <a href="https://t.me/custom_sms" class="support-button telegram" target="_blank">
                            <i class="fab fa-telegram"></i>
                            <span class="support-button-text">Telegram Channel</span>
                        </a>
                        
                        <a href="tel:+8809638581380" class="support-button call">
                            <i class="fas fa-phone"></i>
                            <span class="support-button-text">Call Us</span>
                        </a>
                        
                        <a href="mailto: mhecpartteam@gmail.com" class="support-button mail">
                            <i class="fas fa-envelope"></i>
                            <span class="support-button-text">Email Support</span>
                        </a>
                        
                        <a href="https://www.youtube.com/@MHEXPARTTEAM" class="support-button youtube" target="_blank">
                            <i class="fab fa-youtube"></i>
                            <span class="support-button-text">YouTube Channel</span>
                        </a>
                    </div>
                    
                    <div class="buy-sms-section">
                        <h3 class="buy-sms-title">
                            <i class="fas fa-shopping-cart"></i> Buy SMS Package
                        </h3>
                        
                        <div class="price-list">
                            <div class="price-item">
                                <span class="price-label">Per SMS Cost</span>
                                <span class="price-value">0.35 Taka</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">145 SMS</span>
                                <span class="price-value">50 Taka</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">290 SMS</span>
                                <span class="price-value">100 Taka</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">585 SMS</span>
                                <span class="price-value">200 Taka</span>
                            </div>
                        </div>
                        
                        <div class="sms-info">
                            <h4 class="sms-info-title">Why Choose Our SMS Service?</h4>
                            <ul class="sms-info-list">
                                <li>High delivery rate with instant sending</li>
                                <li>Competitive pricing with bulk discounts</li>
                                <li>24/7 customer support</li>
                                <li>Easy API integration for developers</li>
                                <li>Secure and reliable service</li>
                            </ul>
                        </div>
                        
                        <a href="https://mhbulksms.mooo.com/tg.php" class="buy-sms-button" target="_blank">
                            <i class="fab fa-telegram"></i> Buy SMS Package
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Tab navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.hasAttribute('data-target')) {
                    e.preventDefault();
                    
                    // Hide all sections
                    document.querySelectorAll('.section').forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    // Show target section
                    const target = this.getAttribute('data-target');
                    document.getElementById(target).classList.add('active');
                    
                    // Update active nav link
                    document.querySelectorAll('.nav-link').forEach(navLink => {
                        navLink.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Close sidebar
                    sidebar.classList.remove('active');
                    
                    // If SMS logs section, refresh logs
                    if (target === 'sms-logs-section') {
                        refreshSmsLogs();
                    }
                }
            });
        });
        
        // Notification menu button
        document.getElementById('notificationMenuBtn').addEventListener('click', function(e) {
            e.preventDefault();
            checkNotifications();
            sidebar.classList.remove('active');
        });
        
        // Refresh SMS logs button
        document.getElementById('refreshLogs').addEventListener('click', function() {
            refreshSmsLogs();
        });
        
        function refreshSmsLogs() {
            const refreshButton = document.getElementById('refreshLogs');
            const originalButtonText = refreshButton.innerHTML;
            
            refreshButton.disabled = true;
            refreshButton.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
            
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=true&get_sms_logs=true'
            })
            .then(response => response.json())
            .then(data => {
                const logsTableBody = document.getElementById('logsTableBody');
                logsTableBody.innerHTML = '';
                
                if (data.logs && data.logs.length > 0) {
                    data.logs.forEach(log => {
                        const row = document.createElement('tr');
                        
                        const numberCell = document.createElement('td');
                        numberCell.textContent = log.number;
                        
                        const messageCell = document.createElement('td');
                        messageCell.textContent = log.message.length > 50 ? 
                            log.message.substring(0, 50) + '...' : log.message;
                        
                        const timeCell = document.createElement('td');
                        timeCell.textContent = log.time;
                        
                        const statusCell = document.createElement('td');
                        const statusSpan = document.createElement('span');
                        statusSpan.className = 'log-status ' + (log.status.includes('❌') ? 'error' : 'success');
                        statusSpan.textContent = log.status;
                        statusCell.appendChild(statusSpan);
                        
                        row.appendChild(numberCell);
                        row.appendChild(messageCell);
                        row.appendChild(timeCell);
                        row.appendChild(statusCell);
                        
                        logsTableBody.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 4;
                    cell.style.textAlign = 'center';
                    cell.textContent = 'No SMS logs found';
                    row.appendChild(cell);
                    logsTableBody.appendChild(row);
                }
            })
            .catch(error => {
                console.error('Error refreshing SMS logs:', error);
            })
            .finally(() => {
                refreshButton.disabled = false;
                refreshButton.innerHTML = originalButtonText;
            });
        }
        
        // Show all notifications in a list
        function showNotificationList(notices, bonuses) {
            const notificationList = document.getElementById('notificationList');
            notificationList.innerHTML = '';
            
            // Combine notices and bonuses
            const allNotifications = [];
            
            if (notices && notices.length > 0) {
                notices.forEach(notice => {
                    allNotifications.push({
                        type: 'notice',
                        id: notice.id || Date.now(),
                        title: 'Admin Notice',
                        content: notice.content,
                        time: notice.time || new Date().toISOString()
                    });
                });
            }
            
            if (bonuses) {
                Object.keys(bonuses).forEach(bonusId => {
                    const bonus = bonuses[bonusId];
                    allNotifications.push({
                        type: 'bonus',
                        id: bonusId,
                        title: 'Bonus Available',
                        content: bonus.reason,
                        amount: bonus.amount,
                        time: new Date(bonus.time * 1000).toISOString(),
                        expiry: new Date((bonus.time + bonus.duration) * 1000).toISOString()
                    });
                });
            }
            
            // Sort by time (newest first)
            allNotifications.sort((a, b) => new Date(b.time) - new Date(a.time));
            
            if (allNotifications.length === 0) {
                notificationList.innerHTML = '<p style="text-align: center; color: var(--text-light);">No notifications available</p>';
            } else {
                allNotifications.forEach(notification => {
                    const notificationItem = document.createElement('div');
                    notificationItem.className = 'notification-item';
                    notificationItem.style.padding = '12px';
                    notificationItem.style.borderBottom = '1px solid var(--border)';
                    notificationItem.style.cursor = 'pointer';
                    notificationItem.style.display = 'flex';
                    notificationItem.style.alignItems = 'center';
                    
                    const icon = document.createElement('i');
                    icon.className = notification.type === 'notice' ? 
                        'fas fa-bell' : 'fas fa-gift';
                    icon.style.marginRight = '10px';
                    icon.style.color = notification.type === 'notice' ? 
                        'var(--primary)' : 'var(--warning)';
                    
                    const content = document.createElement('div');
                    content.style.flex = '1';
                    
                    const title = document.createElement('div');
                    title.style.fontWeight = '600';
                    title.style.color = 'var(--text)';
                    title.textContent = notification.title;
                    
                    const preview = document.createElement('div');
                    preview.style.fontSize = '14px';
                    preview.style.color = 'var(--text-light)';
                    preview.textContent = notification.type === 'notice' ? 
                        notification.content.substring(0, 50) + (notification.content.length > 50 ? '...' : '') :
                        `${notification.amount} coins - ${notification.content.substring(0, 50)}`;
                    
                    content.appendChild(title);
                    content.appendChild(preview);
                    
                    notificationItem.appendChild(icon);
                    notificationItem.appendChild(content);
                    
                    // Add click handler to show full notification
                    notificationItem.addEventListener('click', () => {
                        closeNotificationList();
                        if (notification.type === 'notice') {
                            showSingleNotice(notification);
                        } else {
                            showSingleBonus(notification);
                        }
                    });
                    
                    notificationList.appendChild(notificationItem);
                });
            }
            
            document.getElementById('notificationListPopup').style.display = 'flex';
        }
        
        function closeNotificationList() {
            document.getElementById('notificationListPopup').style.display = 'none';
        }
        
        function showSingleNotice(notice) {
            const noticePopup = document.createElement('div');
            noticePopup.className = 'popup-container notice-popup';
            noticePopup.style.display = 'flex';
            noticePopup.innerHTML = `
                <div class="popup-content">
                    <span class="popup-close" onclick="this.closest('.popup-container').remove(); updateNotificationCount();">&times;</span>
                    <div class="notice-title">Admin Notice</div>
                    <div class="notice-content scrollable-popup">
                        ${notice.content.replace(/\n/g, '<br>')}
                    </div>
                    <div class="popup-buttons">
                        <button class="btn btn-primary close-notice" onclick="this.closest('.popup-container').remove(); updateNotificationCount();">OK</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(noticePopup);
        }
        
        function showSingleBonus(bonus) {
            const expiryTime = new Date(bonus.expiry).toLocaleTimeString();
            
            const bonusPopup = document.createElement('div');
            bonusPopup.className = 'popup-container bonus-popup';
            bonusPopup.style.display = 'flex';
            bonusPopup.setAttribute('data-bonus-id', bonus.id);
            bonusPopup.innerHTML = `
                <div class="popup-content">
                    <span class="popup-close" onclick="this.closest('.popup-container').remove(); updateNotificationCount();">&times;</span>
                    <div class="bonus-title">Admin's Gift</div>
                    <div class="bonus-reason">${bonus.content}</div>
                    <div class="bonus-amount">+${bonus.amount} coins</div>
                    <div class="bonus-details">
                        Available until ${expiryTime}
                    </div>
                    <div class="popup-buttons">
                        <button class="btn btn-success claim-bonus">Claim Bonus</button>
                        <button class="btn btn-secondary close-bonus" onclick="this.closest('.popup-container').remove(); updateNotificationCount();">Cancel</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(bonusPopup);
            
            bonusPopup.querySelector('.claim-bonus').addEventListener('click', function() {
                const originalButtonText = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<div class="loading"></div> Claiming...';
                
                fetch('dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax=true&claim_bonus=true&bonus_id=${bonus.id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const balanceText = document.getElementById('balanceText');
                        balanceText.textContent = data.balance;
                        balanceText.classList.add('pulse');
                        setTimeout(() => {
                            balanceText.classList.remove('pulse');
                        }, 500);
                        
                        bonusPopup.remove();
                        showResponse(data.message, 'success');
                        updateNotificationCount();
                    } else {
                        showResponse(data.message, 'error');
                        this.disabled = false;
                        this.innerHTML = originalButtonText;
                    }
                })
                .catch(error => {
                    showResponse(`❌ Error: ${error.message}`, 'error');
                    this.disabled = false;
                    this.innerHTML = originalButtonText;
                });
            });
        }
        
        function checkNotifications() {
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=true&check_notices=true'
            })
            .then(response => response.json())
            .then(data => {
                showNotificationList(data.notices, data.bonuses);
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
        }
        
        function updateNotificationCount() {
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=true&check_notices=true'
            })
            .then(response => response.json())
            .then(data => {
                const total = (data.notices ? data.notices.length : 0) + (data.bonuses ? Object.keys(data.bonuses).length : 0);
                const countElement = document.getElementById('menuNotificationCount');
                
                if (total > 0) {
                    if (!countElement) {
                        const menuItem = document.getElementById('notificationMenuBtn');
                        const count = document.createElement('div');
                        count.id = 'menuNotificationCount';
                        count.className = 'notification-badge';
                        count.textContent = total;
                        menuItem.appendChild(count);
                    } else {
                        countElement.textContent = total;
                    }
                } else if (countElement) {
                    countElement.remove();
                }
            })
            .catch(error => {
                console.error('Error updating notification count:', error);
            });
        }
        
        function showResponse(message, type) {
            const responseBox = document.getElementById('responseBox');
            responseBox.innerHTML = `<pre>${message}</pre>`;
            responseBox.style.display = 'block';
            responseBox.style.borderLeftColor = type === 'error' ? 'var(--error)' : 'var(--success)';
        }
        
        function showRedeemResponse(message, type) {
            const responseBox = document.getElementById('redeemResponseBox');
            responseBox.innerHTML = `<pre>${message}</pre>`;
            responseBox.style.display = 'block';
            responseBox.style.borderLeftColor = type === 'error' ? 'var(--error)' : 'var(--success)';
        }
        
        function copyApiEndpoint() {
            const text = document.getElementById('apiEndpoint').textContent;
            navigator.clipboard.writeText(text).then(() => {
                const button = document.querySelector('.copy-btn');
                const original = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    button.innerHTML = original;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
        
        function copyReferralLink() {
            const text = document.getElementById('referralLink').textContent;
            navigator.clipboard.writeText(text).then(() => {
                const button = document.querySelector('.referral-copy');
                button.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    button.innerHTML = '<i class="far fa-copy"></i>';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
        
        // Handle form submission with AJAX
        document.getElementById('sendForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sendButton = document.getElementById('sendButton');
            const originalButtonText = sendButton.innerHTML;
            
            sendButton.disabled = true;
            sendButton.innerHTML = '<div class="loading"></div> Sending...';
            
            const number = formData.get('number').trim();
            const message = formData.get('msg').trim();
            
            if (!number) {
                showResponse('❌ Phone number is required', 'error');
                sendButton.disabled = false;
                sendButton.innerHTML = originalButtonText;
                return;
            }
            
            if (!message) {
                showResponse('❌ Message content is required', 'error');
                sendButton.disabled = false;
                sendButton.innerHTML = originalButtonText;
                return;
            }
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                showResponse(data.response, data.status);
                
                if (data.status === 'success') {
                    const balanceText = document.getElementById('balanceText');
                    balanceText.textContent = data.balance;
                    balanceText.classList.add('pulse');
                    setTimeout(() => {
                        balanceText.classList.remove('pulse');
                    }, 500);
                    
                    // Refresh SMS logs after sending
                    refreshSmsLogs();
                }
            })
            .catch(error => {
                showResponse(`❌ Error: ${error.message}`, 'error');
            })
            .finally(() => {
                sendButton.disabled = false;
                sendButton.innerHTML = originalButtonText;
            });
        });
        
        // Handle redeem form submission
        document.getElementById('redeemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const redeemButton = document.getElementById('redeemButton');
            const originalButtonText = redeemButton.innerHTML;
            
            redeemButton.disabled = true;
            redeemButton.innerHTML = '<div class="loading"></div> Processing...';
            
            const code = formData.get('code').trim();
            
            if (!code) {
                showRedeemResponse('❌ Redeem code is required', 'error');
                redeemButton.disabled = false;
                redeemButton.innerHTML = originalButtonText;
                return;
            }
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                showRedeemResponse(data.response, data.success ? 'success' : 'error');
                
                if (data.success) {
                    const balanceText = document.getElementById('balanceText');
                    balanceText.textContent = data.balance;
                    balanceText.classList.add('pulse');
                    setTimeout(() => {
                        balanceText.classList.remove('pulse');
                    }, 500);
                    
                    document.querySelector('#redeemForm input[name="code"]').value = '';
                    updateNotificationCount();
                }
            })
            .catch(error => {
                showRedeemResponse(`❌ Error: ${error.message}`, 'error');
            })
            .finally(() => {
                redeemButton.disabled = false;
                redeemButton.innerHTML = originalButtonText;
            });
        });
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('.form-control');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Show notices first, then bonuses
            setTimeout(() => {
                checkNotifications();
            }, 500);
        });
    </script>
</body>
</html>