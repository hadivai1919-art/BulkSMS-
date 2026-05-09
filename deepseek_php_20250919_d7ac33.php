<?php
// Telegram bot configuration
$botToken = "8449880164:AAFo6jggdxTZaz8H5g0qjBhFijvOuYLkHwU";
$chatId = "6607250676";

// Commission rates for different amount ranges
$commissionRates = [
    'default' => 0.25, // 1 Taka = 4 coins
    '50-100' => 0.25,  // 50-100 Taka range
    '200-500' => 0.20, // 200-500 Taka range (5% discount)
    '500-1000' => 0.15 // 500-1000 Taka range (10% discount)
];

// Function to read file and return array of lines
function readFileLines($filename) {
    if (!file_exists($filename)) return [];
    $content = file_get_contents($filename);
    return array_filter(explode("\n", $content), 'trim');
}

// Function to write array to file
function writeFileLines($filename, $lines) {
    file_put_contents($filename, implode("\n", $lines));
}

// Function to get user balance
function getUserBalance($username) {
    $lines = readFileLines('balanclamuhadifucke.txt');
    foreach ($lines as $line) {
        list($user, $balance) = explode(':', $line);
        if (trim($user) === trim($username)) {
            return floatval(trim($balance));
        }
    }
    return 0;
}

// Function to update user balance
function updateUserBalance($username, $amount) {
    $lines = readFileLines('balanclamuhadifucke.txt');
    $found = false;
    $newLines = [];
    
    foreach ($lines as $line) {
        list($user, $balance) = explode(':', $line);
        if (trim($user) === trim($username)) {
            $newBalance = floatval(trim($balance)) + $amount;
            $newLines[] = trim($username) . ':' . $newBalance;
            $found = true;
        } else {
            $newLines[] = trim($line);
        }
    }
    
    if (!$found) {
        $newLines[] = trim($username) . ':' . $amount;
    }
    
    writeFileLines('balanclamuhadifucke.txt', $newLines);
}

// Function to check if user exists in balance file
function checkUserExists($username) {
    $lines = readFileLines('balanclamuhadifucke.txt');
    foreach ($lines as $line) {
        list($user, $balance) = explode(':', $line);
        if (trim($user) === trim($username)) {
            return true;
        }
    }
    return false;
}

// Function to get all transaction IDs
function getAllTransactionIDs() {
    $lines = readFileLines('transactions.txt');
    $trxIDs = [];
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 4) {
            $trxIDs[] = trim($parts[0]);
        }
    }
    return $trxIDs;
}

// Function to add transaction
function addTransaction($trxid, $username, $amount, $status = 'pending') {
    $lines = readFileLines('transactions.txt');
    $lines[] = $trxid . '|' . $username . '|' . $amount . '|' . $status . '|' . date('Y-m-d H:i:s');
    writeFileLines('transactions.txt', $lines);
}

// Function to check if transaction ID already exists
function checkTransactionExists($trxid) {
    $trxIDs = getAllTransactionIDs();
    return in_array($trxid, $trxIDs);
}

// Function to update transaction status
function updateTransactionStatus($trxid, $status) {
    $lines = readFileLines('transactions.txt');
    $newLines = [];
    
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 4 && trim($parts[0]) === trim($trxid)) {
            $parts[3] = $status;
            $line = implode('|', $parts);
        }
        $newLines[] = $line;
    }
    
    writeFileLines('transactions.txt', $newLines);
}

// Function to get commission rate based on amount
function getCommissionRate($amount) {
    global $commissionRates;
    
    if ($amount >= 500 && $amount <= 1000) {
        return $commissionRates['500-1000'];
    } elseif ($amount >= 200 && $amount <= 500) {
        return $commissionRates['200-500'];
    } elseif ($amount >= 50 && $amount <= 100) {
        return $commissionRates['50-100'];
    } else {
        return $commissionRates['default'];
    }
}

// Function to calculate coins based on amount
function calculateCoins($amount) {
    $commissionRate = getCommissionRate($amount);
    return $amount / $commissionRate;
}

function sendToTelegram($message, $replyMarkup = null) {
    global $botToken, $chatId;
    
    $url = "https://api.telegram.org/bot".$botToken."/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => "HTML"
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = json_encode($replyMarkup);
    }
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;
}

// Handle AJAX requests for user check
if (isset($_GET['check_user'])) {
    $username = $_GET['check_user'];
    $exists = checkUserExists($username);
    echo json_encode(['exists' => $exists]);
    exit;
}

// Handle AJAX requests for coin calculation
if (isset($_GET['calculate_coins'])) {
    $amount = floatval($_GET['calculate_coins']);
    $coins = calculateCoins($amount);
    echo json_encode(['coins' => number_format($coins, 2)]);
    exit;
}

// Handle callback queries from Telegram
$input = file_get_contents("php://input");
if (!empty($input)) {
    $callbackData = json_decode($input, true);
    
    if (isset($callbackData['callback_query'])) {
        $callback_query = $callbackData['callback_query'];
        $data = $callback_query['data'];
        $message = $callback_query['message'];
        
        if (strpos($data, 'approve_') === 0) {
            // Extract data from callback
            $parts = explode('_', $data);
            $trxid = $parts[1];
            $user = $parts[2];
            $amount = floatval($parts[3]);
            
            // Calculate coins based on amount and commission rate
            $commissionRate = getCommissionRate($amount);
            $coins = $amount / $commissionRate;
            updateUserBalance($user, $coins);
            updateTransactionStatus($trxid, 'approved');
            
            // Edit message to show approval
            $messageId = $message['message_id'];
            $chatId = $message['chat']['id'];
            $newMessage = "<b>✅ পেমেন্ট অনুমোদিত</b>\n";
            $newMessage .= "ব্যবহারকারী: " . $user . "\n";
            $newMessage .= "পরিমাণ: " . $amount . " টাকা\n";
            $newMessage .= "প্রাপ্ত কয়েন: " . number_format($coins, 2) . "\n";
            $newMessage .= "অনুমোদনকারী: " . $callback_query['from']['first_name'];
            
            $url = "https://api.telegram.org/bot".$botToken."/editMessageText";
            $editData = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $newMessage,
                'parse_mode' => "HTML"
            ];
            
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($editData)
                ]
            ];
            
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            // Send callback answer
            $answerUrl = "https://api.telegram.org/bot".$botToken."/answerCallbackQuery";
            $answerData = [
                'callback_query_id' => $callback_query['id'],
                'text' => 'পেমেন্ট অনুমোদন করা হয়েছে!',
                'show_alert' => true
            ];
            
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($answerData)
                ]
            ];
            
            $context  = stream_context_create($options);
            $result = file_get_contents($answerUrl, false, $context);
            
        } elseif (strpos($data, 'cancel_') === 0) {
            // Extract data from callback
            $parts = explode('_', $data);
            $trxid = $parts[1];
            $user = $parts[2];
            
            // Update transaction status
            updateTransactionStatus($trxid, 'cancelled');
            
            // Edit message to show cancellation
            $messageId = $message['message_id'];
            $chatId = $message['chat']['id'];
            $newMessage = "<b>❌ পেমেন্ট বাতিল</b>\n";
            $newMessage .= "ব্যবহারকারী: " . $user . "\n";
            $newMessage .= "লেনদেন আইডি: " . $trxid . "\n";
            $newMessage .= "বাতিলকারী: " . $callback_query['from']['first_name'];
            
            $url = "https://api.telegram.org/bot".$botToken."/editMessageText";
            $editData = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $newMessage,
                'parse_mode' => "HTML"
            ];
            
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($editData)
                ]
            ];
            
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            // Send callback answer
            $answerUrl = "https://api.telegram.org/bot".$botToken."/answerCallbackQuery";
            $answerData = [
                'callback_query_id' => $callback_query['id'],
                'text' => 'পেমেন্ট বাতিল করা হয়েছে!',
                'show_alert' => true
            ];
            
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($answerData)
                ]
            ];
            
            $context  = stream_context_create($options);
            $result = file_get_contents($answerUrl, false, $context);
        }
    }
    exit;
}

// Handle admin actions from web interface
if (isset($_GET['action']) && isset($_GET['trxid']) && isset($_GET['user'])) {
    $action = $_GET['action'];
    $trxid = $_GET['trxid'];
    $user = $_GET['user'];
    $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
    
    if ($action === 'approve') {
        // Calculate coins based on amount and commission rate
        $commissionRate = getCommissionRate($amount);
        $coins = $amount / $commissionRate;
        updateUserBalance($user, $coins);
        updateTransactionStatus($trxid, 'approved');
        
        // Send success response
        echo json_encode(['status' => 'success', 'message' => 'Payment approved and coins added']);
    } elseif ($action === 'cancel') {
        // Update transaction status
        updateTransactionStatus($trxid, 'cancelled');
        
        // Send cancel response
        echo json_encode(['status' => 'cancelled', 'message' => 'Payment cancelled']);
    }
    exit;
}

// Handle commission rate update
if (isset($_GET['update_rates'])) {
    $rates = $_GET['update_rates'];
    $rateArray = explode(',', $rates);
    
    if (count($rateArray) >= 4) {
        $commissionRates['50-100'] = floatval($rateArray[0]);
        $commissionRates['200-500'] = floatval($rateArray[1]);
        $commissionRates['500-1000'] = floatval($rateArray[2]);
        $commissionRates['default'] = floatval($rateArray[3]);
        
        echo json_encode(['status' => 'success', 'message' => 'Commission rates updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid rate format']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender = $_POST['sender'];
    $trxid = $_POST['trxid'];
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
    // Validate minimum amount
    if ($amount < 50) {
        $error = "সর্বনিম্ন ৫০ টাকা ডিপোজিট করতে হবে!";
    } elseif (!empty($sender) && !empty($trxid) && !empty($username) && $amount > 0) {
        // Check if transaction ID already exists
        if (checkTransactionExists($trxid)) {
            $error = "এই লেনদেন আইডি ইতিমধ্যে ব্যবহৃত হয়েছে!";
        } else {
            $commissionRate = getCommissionRate($amount);
            $coins = $amount / $commissionRate;
            
            // Add transaction to file
            addTransaction($trxid, $username, $amount);
            
            $message = "<b>নতুন নগদ পেমেন্ট প্রাপ্ত</b>\n";
            $message .= "থেকে: " . $sender . "\n";
            $message .= "লেনদেন আইডি: " . $trxid . "\n";
            $message .= "ব্যবহারকারীর নাম: " . $username . "\n";
            $message .= "পরিমাণ: " . $amount . " টাকা\n";
            $message .= "প্রাপ্ত কয়েন: " . number_format($coins, 2) . "\n";
            $message .= "সময়: " . date('Y-m-d H:i:s');
            
            // Create inline keyboard for admin approval
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ অনুমোদন', 'callback_data' => 'approve_'.$trxid.'_'.$username.'_'.$amount],
                        ['text' => '❌ বাতিল', 'callback_data' => 'cancel_'.$trxid.'_'.$username]
                    ]
                ]
            ];
            
            $result = sendToTelegram($message, $inlineKeyboard);
            
            if ($result !== false) {
                $success = "পেমেন্টের তথ্য সফলভাবে জমা দেওয়া হয়েছে! আপনার পেমেন্ট যাচাই করা হচ্ছে, কিছুক্ষণ অপেক্ষা করুন।";
            } else {
                $error = "পেমেন্টের তথ্য পাঠাতে ব্যর্থ হয়েছে। দয়া করে আবার চেষ্টা করুন।";
            }
        }
    } else {
        $error = "দয়া করে সমস্ত প্রয়োজনীয় তথ্য প্রদান করুন!";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নগদ পেমেন্ট সিস্টেম</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --nagad-orange: #ff6900;
            --nagad-dark: #231f20;
            --nagad-light: #f8f9fa;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            font-family: 'Hind Siliguri', Arial, sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-color: #f5f5f5;
            color: var(--nagad-dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 500px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 25px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .logo {
            height: 60px;
            transition: var(--transition);
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: var(--nagad-orange);
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }
        
        .header h1::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--nagad-orange);
            border-radius: 3px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .payment-steps {
            background: var(--nagad-light);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid var(--nagad-orange);
            transition: var(--transition);
        }
        
        .payment-steps:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .step {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .step:last-child {
            margin-bottom: 0;
        }
        
        .step-number {
            background: var(--nagad-orange);
            color: white;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            font-weight: 600;
            flex-shrink: 0;
            font-size: 14px;
        }
        
        .step-content {
            flex: 1;
            padding-top: 3px;
            font-weight: 400;
        }
        
        .copy-section {
            margin: 30px 0;
            text-align: center;
        }
        
        .copy-label {
            font-size: 15px;
            color: #555;
            margin-bottom: 12px;
            font-weight: 500;
        }
        
        .copy-container {
            display: flex;
            max-width: 280px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(255, 105, 0, 0.15);
            transition: var(--transition);
        }
        
        .copy-container:hover {
            box-shadow: 0 6px 16px rgba(255, 105, 0, 0.2);
        }
        
        .copy-number {
            flex: 1;
            padding: 14px;
            background: white;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #eee;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        
        .copy-btn {
            background: var(--nagad-orange);
            color: white;
            border: none;
            padding: 0 20px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
        }
        
        .copy-btn:hover {
            background: #e05a00;
        }
        
        .form-group {
            margin-bottom: 25px;
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #444;
            font-size: 15px;
        }
        
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
        }
        
        input[type="text"]:focus, input[type="number"]:focus {
            border-color: var(--nagad-orange);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 105, 0, 0.1);
        }
        
        button[type="submit"] {
            background: var(--nagad-orange);
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 15px;
            box-shadow: 0 4px 12px rgba(255, 105, 0, 0.2);
        }
        
        button[type="submit"]:hover {
            background: #e05a00;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 105, 0, 0.25);
        }
        
        button[type="submit"]:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        .alert-success {
            background: #edf7f0;
            color: #1a7b3e;
            border: 1px solid #d1e7dd;
        }
        
        .alert-error {
            background: #fdf2f2;
            color: #9b2c2c;
            border: 1px solid #f8d7da;
        }
        
        .note {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-top: 30px;
            line-height: 1.6;
        }
        
        .price-list {
            background: #fff8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 1px dashed var(--nagad-orange);
        }
        
        .price-list h3 {
            color: var(--nagad-orange);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ffe0c2;
        }
        
        .price-item:last-child {
            border-bottom: none;
        }
        
        .user-check {
            margin-top: 5px;
            font-size: 13px;
        }
        
        .user-valid {
            color: green;
        }
        
        .user-invalid {
            color: red;
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 30px 20px;
                margin: 15px auto;
            }
            
            .logo {
                height: 50px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="https://www.nagad.com.bd/assets/images/header/nagad-logo.png" alt="নগদ লোগো" class="logo">
        </div>
        
        <div class="header">
            <h1>পেমেন্ট গেটওয়ে</h1>
            <p>কয়েকটি সহজ ধাপে আপনার পেমেন্ট সম্পূর্ণ করুন</p>
        </div>
        
        <div class="payment-steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">নিচের নগদ নম্বরটি কপি করুন</div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">নগদ অ্যাপ খুলুন এবং "সেন্ড মানি" অপশন নির্বাচন করুন</div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">নম্বরটি পেস্ট করে পেমেন্ট সম্পূর্ণ করুন</div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">যাচাইকরণের জন্য নিচে আপনার পেমেন্টের বিবরণ জমা দিন</div>
            </div>
        </div>
        
        <div class="copy-section">
            <div class="copy-label">এই নগদ নম্বরে টাকা পাঠান:</div>
            <div class="copy-container">
                <div class="copy-number" id="nagadNumber">01XXXXXXXXX</div>
                <button class="copy-btn" onclick="copyToClipboard()">কপি</button>
            </div>
        </div>
        
        <div class="price-list">
            <h3>কয়েন মূল্য তালিকা</h3>
            <?php
            $amounts = [50, 100, 200, 500, 1000];
            foreach ($amounts as $amount) {
                $commissionRate = getCommissionRate($amount);
                $coins = $amount / $commissionRate;
                echo "<div class='price-item'>";
                echo "<span>{$amount} টাকা</span>";
                echo "<span>" . number_format($coins, 2) . " কয়েন</span>";
                echo "</div>";
            }
            ?>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="paymentForm">
            <div class="form-group">
                <label for="sender">আপনার নগদ নম্বর</label>
                <input type="text" name="sender" id="sender" placeholder="01XXXXXXXXX" required inputmode="numeric" pattern="[0-9]*">
            </div>
            
            <div class="form-group">
                <label for="trxid">লেনদেন আইডি (TrxID)</label>
                <input type="text" name="trxid" id="trxid" placeholder="লেনদেন আইডি লিখুন" required>
            </div>
            
            <div class="form-group">
                <label for="username">ব্যবহারকারীর নাম</label>
                <input type="text" name="username" id="username" placeholder="আপনার ব্যবহারকারীর নাম" required onkeyup="checkUser()">
                <div id="userCheck" class="user-check"></div>
            </div>
            
            <div class="form-group">
                <label for="amount">টাকার পরিমাণ (সর্বনিম্ন ৫০ টাকা)</label>
                <input type="number" name="amount" id="amount" placeholder="টাকার পরিমাণ লিখুন" required min="50" onkeyup="calculateCoins()" inputmode="numeric" pattern="[0-9]*">
                <div id="coinCalculation" class="user-check"></div>
            </div>
            
            <button type="submit">পেমেন্ট যাচাই করুন</button>
        </form>
        
        <div class="note">
            <p><strong>গুরুত্বপূর্ণ নির্দেশাবলী:</strong></p>
            <p>1. সঠিক ব্যবহারকারীর নাম প্রদান করুন</p>
            <p>2. সঠিক লেনদেন আইডি প্রদান করুন</p>
            <p>3. সঠিক নগদ নম্বর প্রদান করুন</p>
            <p>4. পেমেন্ট জমা দেওয়ার পরে কিছুক্ষণ অপেক্ষা করুন</p>
            <p>5. একবারের বেশি জমা দিবেন না</p>
            <p>6. সর্বনিম্ন ৫০ টাকা ডিপোজিট করতে হবে</p>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const text = document.getElementById("nagadNumber").innerText;
            navigator.clipboard.writeText(text).then(function() {
                // Create notification element
                const notification = document.createElement('div');
                notification.textContent = 'কপি করা হয়েছে!';
                notification.style.position = 'fixed';
                notification.style.bottom = '20px';
                notification.style.left = '50%';
                notification.style.transform = 'translateX(-50%)';
                notification.style.backgroundColor = '#333';
                notification.style.color = 'white';
                notification.style.padding = '10px 20px';
                notification.style.borderRadius = '20px';
                notification.style.zIndex = '1000';
                notification.style.boxShadow = '0 3px 10px rgba(0,0,0,0.2)';
                notification.style.animation = 'fadeInOut 2s ease-in-out';
                
                document.body.appendChild(notification);
                
                // Remove after animation
                setTimeout(function() {
                    notification.remove();
                }, 2000);
            }, function(err) {
                alert("কপি করতে ব্যর্থ: " + err);
            });
        }
        
        function checkUser() {
            const username = document.getElementById('username').value;
            if (username.length < 3) {
                document.getElementById('userCheck').innerHTML = '';
                return;
            }
            
            // Make an AJAX request to check the user
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '?check_user=' + encodeURIComponent(username), true);
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    const userCheck = document.getElementById('userCheck');
                    
                    if (response.exists) {
                        userCheck.innerHTML = '<span class="user-valid">✅ ব্যবহারকারী বৈধ</span>';
                    } else {
                        userCheck.innerHTML = '<span class="user-valid">✅ নতুন ব্যবহারকারী তৈরি হবে</span>';
                    }
                }
            };
            xhr.send();
        }
        
        function calculateCoins() {
            const amount = document.getElementById('amount').value;
            if (amount < 50) {
                document.getElementById('coinCalculation').innerHTML = '<span class="user-invalid">সর্বনিম্ন ৫০ টাকা ডিপোজিট করতে হবে</span>';
                return;
            }
            
            // Make an AJAX request to calculate coins
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '?calculate_coins=' + encodeURIComponent(amount), true);
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    document.getElementById('coinCalculation').innerHTML = 
                        `আপনি পাবেন: ${response.coins} কয়েন`;
                }
            };
            xhr.send();
        }
        
        // Add animation to steps
        document.addEventListener('DOMContentLoaded', function() {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                step.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Add animation to form groups
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.animationDelay = `${index * 0.1 + 0.4}s`;
            });
            
            // Set inputmode for numeric fields
            document.getElementById('sender').addEventListener('focus', function() {
                this.setAttribute('inputmode', 'numeric');
            });
            
            document.getElementById('amount').addEventListener('focus', function() {
                this.setAttribute('inputmode', 'numeric');
            });
        });
    </script>
    
    <style>
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateX(-50%) translateY(20px); }
            20% { opacity: 1; transform: translateX(-50%) translateY(0); }
            80% { opacity: 1; transform: translateX(-50%) translateY(0); }
            100% { opacity: 0; transform: translateX(-50%) translateY(-20px); }
        }
    </style>
</body>
</html>