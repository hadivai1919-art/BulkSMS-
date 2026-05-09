<?php
session_start();

$admin_user = "lamuhadi@@";
$admin_pass = "@#1912@#";

// Files
$balance_file = "balanclamuhadifucke.txt";
$config_file = "config.txt";
$ban_file = "banlist.txt";
$log_file = "logs.txt";
$bonus_file = "bonus.txt";
$redeem_file = "redeem.txt";
$redeem_user_file = "redeem_user.txt";
$notice_file = "notices.txt";
$bonus_data_file = "bonus_data.json";
$apk_file = "apk_link.txt";
$referral_config_file = "referral_config.txt";
$notice_seen_file = "notice_seen.txt";
$spam_words_file = "spam_words.txt";
$rate_limit_file = "rate_limit.txt";
$hadi_api_file = "api/hadi.json";

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Login Form
if (!isset($_SESSION['admin_logged'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = $_POST['user'] ?? '';
        $pass = $_POST['pass'] ?? '';
        if ($user === $admin_user && $pass === $admin_pass) {
            $_SESSION['admin_logged'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $error = "❌ Incorrect username or password";
        }
    }

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
            }
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                color: #fff;
            }
            .login-container {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                padding: 2rem;
                border-radius: 15px;
                width: 400px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
                animation: fadeIn 0.8s ease-in-out;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            h2 {
                text-align: center;
                margin-bottom: 1.5rem;
                font-weight: 600;
            }
            .form-group {
                margin-bottom: 1.5rem;
                position: relative;
            }
            input {
                width: 100%;
                padding: 12px 15px;
                border: none;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 8px;
                color: #fff;
                font-size: 16px;
                transition: all 0.3s ease;
            }
            input:focus {
                outline: none;
                background: rgba(255, 255, 255, 0.3);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
            input::placeholder {
                color: rgba(255, 255, 255, 0.7);
            }
            button {
                width: 100%;
                padding: 12px;
                background: #fff;
                color: #667eea;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            button:hover {
                transform: translateY(-3px);
                box-shadow: 0 7px 14px rgba(0, 0, 0, 0.2);
            }
            .error {
                color: #ff6b6b;
                text-align: center;
                margin-bottom: 1rem;
                animation: shake 0.5s ease-in-out;
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                20%, 60% { transform: translateX(-5px); }
                40%, 80% { transform: translateX(5px); }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2><i class="fas fa-shield-alt"></i> Admin Login</h2>
HTML;

    if (!empty($error)) {
        echo "<div class='error'>$error</div>";
    }

    echo <<<HTML
            <form method="POST">
                <div class="form-group">
                    <input name="user" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input name="pass" type="password" placeholder="Password" required>
                </div>
                <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
        </div>
    </body>
    </html>
HTML;
    exit;
}

// Function to modify balance
function modifyBalance($username, $amount) {
    global $balance_file;
    $lines = file_exists($balance_file) ? file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $updated = false;

    foreach ($lines as &$line) {
        if (strpos($line, ':') !== false) {
            list($user, $bal) = explode(':', $line);
            if ($user === $username) {
                $bal = max(0, $bal + $amount);
                $line = "$user:$bal";
                $updated = true;
            }
        }
    }

    if (!$updated && $amount > 0) {
        $lines[] = "$username:$amount";
    }

    file_put_contents($balance_file, implode("\n", $lines) . "\n");
}

// Function to set balance to 0 for banned users
function setBannedUserBalanceToZero($username) {
    global $balance_file;
    $lines = file_exists($balance_file) ? file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    
    foreach ($lines as &$line) {
        if (strpos($line, ':') !== false) {
            list($user, $bal) = explode(':', $line);
            if ($user === $username) {
                $line = "$user:0";
            }
        }
    }
    
    file_put_contents($balance_file, implode("\n", $lines) . "\n");
}

// Function to format API URL
function formatApiUrl($url, $type) {
    $url = trim($url);
    if (empty($url)) return '';
    if (strpos($url, '{number}') !== false || strpos($url, '{msg}') !== false) return $url;

    if (strpos($url, '?number=&msg=') !== false) {
        return str_replace('?number=&msg=', '?number={number}&msg={msg}', $url);
    }

    if (parse_url($url, PHP_URL_QUERY)) {
        return rtrim($url, '&') . '&number={number}&msg={msg}';
    }

    return $url . '?number={number}&msg={msg}';
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['win_rate'])) {
        file_put_contents($config_file, "win_rate:{$_POST['win_rate']}\nspeed:{$_POST['speed']}");
    }

    if (isset($_POST['target_user'])) {
        if (isset($_POST['add_coin'])) modifyBalance($_POST['target_user'], (int)$_POST['add_coin']);
        if (isset($_POST['remove_coin'])) modifyBalance($_POST['target_user'], -(int)$_POST['remove_coin']);
    }

    if (isset($_POST['ban_user'])) {
        file_put_contents($ban_file, $_POST['ban_user'] . "\n", FILE_APPEND);
        setBannedUserBalanceToZero($_POST['ban_user']);
    }

    if (isset($_POST['unban_user'])) {
        $banlist = file($ban_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newlist = array_filter($banlist, fn($u) => trim($u) !== $_POST['unban_user']);
        file_put_contents($ban_file, implode("\n", $newlist) . "\n");
    }

    if (isset($_POST['save_sms'])) {
        $url = formatApiUrl($_POST['sms_api_url'], 'sms');
        file_put_contents("api/sms.json", json_encode([
            "api_url" => $url,
            "status" => $_POST['sms_status']
        ], JSON_PRETTY_PRINT));
        file_put_contents("remove_coin.txt", (int)$_POST['sms_coin']);
    }

    if (isset($_POST['delete_sms'])) {
        @unlink("api/sms.json");
        @unlink("remove_coin.txt");
    }

    if (isset($_POST['save_otp'])) {
        $url = formatApiUrl($_POST['otp_api_url'], 'otp');
        file_put_contents("api/otp.json", json_encode([
            "api_url" => $url,
            "status" => $_POST['otp_status']
        ], JSON_PRETTY_PRINT));
    }

    if (isset($_POST['delete_otp'])) {
        @unlink("api/otp.json");
    }

    if (isset($_POST['bonus_amount'])) {
        file_put_contents($bonus_file, (int)$_POST['bonus_amount']);
    }

    if (isset($_POST['create_redeem'])) {
        $code = trim($_POST['code']) ?: strtoupper(bin2hex(random_bytes(5)));
        $coin = (int)$_POST['coin'];
        $limit = trim($_POST['limit']) !== '' ? (int)$_POST['limit'] : 'unlimited';
        $entry = "$code:$coin:$limit\n";
        file_put_contents($redeem_file, $entry, FILE_APPEND);
    }

    if (isset($_POST['delete_redeem_code'])) {
        $codeToDelete = $_POST['delete_redeem_code'];
        $codes = file($redeem_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newCodes = array_filter($codes, function($line) use ($codeToDelete) {
            return strpos($line, $codeToDelete) !== 0;
        });
        file_put_contents($redeem_file, implode("\n", $newCodes) . "\n");
    }

    if (isset($_POST['clear_redeem_history'])) {
        file_put_contents($redeem_user_file, "");
    }

    if (isset($_POST['send_notice'])) {
        $notice = trim($_POST['notice_content']);
        $target_user = trim($_POST['notice_target_user']);
        
        if (!empty($notice)) {
            $notice_entry = [
                'id' => uniqid(),
                'time' => time(),
                'content' => $notice,
                'target' => $target_user ?: 'all'
            ];
            
            file_put_contents($notice_file, json_encode($notice_entry) . PHP_EOL, FILE_APPEND);
            $_SESSION['success'] = "Notice sent successfully!";
        } else {
            $_SESSION['error'] = "Notice content cannot be empty!";
        }
    }

    if (isset($_POST['delete_notice'])) {
        $noticeId = $_POST['delete_notice'];
        $notices = file_exists($notice_file) ? file($notice_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $newNotices = [];
        
        foreach ($notices as $noticeJson) {
            $notice = json_decode($noticeJson, true);
            if ($notice && $notice['id'] !== $noticeId) {
                $newNotices[] = $noticeJson;
            }
        }
        
        file_put_contents($notice_file, implode(PHP_EOL, $newNotices) . PHP_EOL);
        $_SESSION['success'] = "Notice deleted successfully!";
    }

    if (isset($_POST['send_bonus'])) {
        $amount = (int)$_POST['bonus_amount'];
        $target_user = trim($_POST['bonus_target_user']);
        $user_limit = (int)$_POST['bonus_user_limit'];
        $duration = (int)$_POST['bonus_duration'];
        $reason = trim($_POST['bonus_reason']);
        
        if ($amount > 0 && !empty($reason)) {
            $bonus_id = uniqid();
            $bonus_entry = [
                'id' => $bonus_id,
                'time' => time(),
                'amount' => $amount,
                'target' => $target_user ?: 'all',
                'user_limit' => $user_limit > 0 ? $user_limit : 'unlimited',
                'duration' => $duration > 0 ? $duration * 3600 : 86400,
                'reason' => $reason,
                'claimed_by' => []
            ];
            
            $bonuses = [];
            if (file_exists($bonus_data_file)) {
                $bonuses = json_decode(file_get_contents($bonus_data_file), true) ?: [];
            }
            
            $bonuses[$bonus_id] = $bonus_entry;
            file_put_contents($bonus_data_file, json_encode($bonuses, JSON_PRETTY_PRINT));
            $_SESSION['success'] = "Bonus created successfully!";
        } else {
            $_SESSION['error'] = "Bonus amount and reason are required!";
        }
    }

    if (isset($_POST['save_apk'])) {
        $apk_link = trim($_POST['apk_link']);
        if (!empty($apk_link)) {
            file_put_contents($apk_file, $apk_link);
            $_SESSION['success'] = "APK link saved successfully!";
        } else {
            $_SESSION['error'] = "APK link cannot be empty!";
        }
    }

    if (isset($_POST['save_referral'])) {
        $coin_reward = (int)$_POST['referral_coin'];
        if ($coin_reward > 0) {
            file_put_contents($referral_config_file, $coin_reward);
            $_SESSION['success'] = "Referral reward updated successfully!";
        } else {
            $_SESSION['error'] = "Referral reward must be greater than 0!";
        }
    }

    if (isset($_POST['save_spam_words'])) {
        $spam_words = trim($_POST['spam_words']);
        if (!empty($spam_words)) {
            file_put_contents($spam_words_file, $spam_words);
            $_SESSION['success'] = "Spam words updated successfully!";
        } else {
            file_put_contents($spam_words_file, "");
            $_SESSION['success'] = "Spam words cleared successfully!";
        }
    }

    if (isset($_POST['save_rate_limit'])) {
        $number_limit = (int)$_POST['number_limit'];
        $ip_limit = (int)$_POST['ip_limit'];
        
        $rate_limits = [
            'number_limit' => $number_limit,
            'ip_limit' => $ip_limit
        ];
        
        file_put_contents($rate_limit_file, json_encode($rate_limits, JSON_PRETTY_PRINT));
        $_SESSION['success'] = "Rate limits updated successfully!";
    }

    // New API Management
    if (isset($_POST['save_api1'])) {
        $url = formatApiUrl($_POST['api1_url'], 'api1');
        file_put_contents("api/api1.json", json_encode([
            "api_url" => $url,
            "status" => $_POST['api1_status'],
            "coin" => (int)$_POST['api1_coin']
        ], JSON_PRETTY_PRINT));
    }

    if (isset($_POST['delete_api1'])) {
        @unlink("api/api1.json");
    }

    if (isset($_POST['save_api2'])) {
        $url = formatApiUrl($_POST['api2_url'], 'api2');
        file_put_contents("api/api2.json", json_encode([
            "api_url" => $url,
            "status" => $_POST['api2_status'],
            "coin" => (int)$_POST['api2_coin']
        ], JSON_PRETTY_PRINT));
    }

    if (isset($_POST['delete_api2'])) {
        @unlink("api/api2.json");
    }

    if (isset($_POST['save_api3'])) {
        $url = formatApiUrl($_POST['api3_url'], 'api3');
        file_put_contents("api/api3.json", json_encode([
            "api_url" => $url,
            "status" => $_POST['api3_status'],
            "coin" => (int)$_POST['api3_coin']
        ], JSON_PRETTY_PRINT));
    }

    if (isset($_POST['delete_api3'])) {
        @unlink("api/api3.json");
    }

    if (isset($_POST['save_api4'])) {
        $url = formatApiUrl($_POST['api4_url'], 'api4');
        file_put_contents("api/api4.json", json_encode([
            "api_url" => $url,
            "status" => $_POST['api4_status'],
            "coin" => (int)$_POST['api4_coin']
        ], JSON_PRETTY_PRINT));
    }

    if (isset($_POST['delete_api4'])) {
        @unlink("api/api4.json");
    }

    if (isset($_POST['save_api5'])) {
        $url = formatApiUrl($_POST['api5_url'], 'api5');
        file_put_contents("api/api5.json", json_encode([
            "api_url" => $url,
            "status" => $_POST['api5_status'],
            "coin" => (int)$_POST['api5_coin']
        ], JSON_PRETTY_PRINT));
    }

    if (isset($_POST['delete_api5'])) {
        @unlink("api/api5.json");
    }

    // New Hadi API Management
    if (isset($_POST['save_hadi_api'])) {
        $api_url = trim($_POST['api_url']);
        $amount = (int)$_POST['amount'];
        
        if (!empty($api_url)) {
            $api_data = [
                'api_url' => $api_url,
                'amount' => $amount,
                'status' => 'active'
            ];
            
            file_put_contents($hadi_api_file, json_encode($api_data, JSON_PRETTY_PRINT));
            $_SESSION['success'] = "Hadi API saved successfully!";
        } else {
            $_SESSION['error'] = "API URL cannot be empty!";
        }
    }

    if (isset($_POST['delete_hadi_api'])) {
        @unlink($hadi_api_file);
        $_SESSION['success'] = "Hadi API deleted successfully!";
    }

    header("Location: admin.php?page=" . ($_GET['page'] ?? ''));
    exit;
}

// Load settings
$win_rate = "50";
$speed = "3";
if (file_exists($config_file)) {
    foreach (file($config_file, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_starts_with($line, "win_rate:")) $win_rate = explode(":", $line)[1];
        if (str_starts_with($line, "speed:")) $speed = explode(":", $line)[1];
    }
}
$ban_users = file_exists($ban_file) ? file($ban_file, FILE_IGNORE_NEW_LINES) : [];
$welcome_bonus = file_exists($bonus_file) ? (int)file_get_contents($bonus_file) : 0;
$apk_link = file_exists($apk_file) ? file_get_contents($apk_file) : '';
$referral_reward = file_exists($referral_config_file) ? (int)file_get_contents($referral_config_file) : 0;
$spam_words = file_exists($spam_words_file) ? file_get_contents($spam_words_file) : '';
$rate_limits = file_exists($rate_limit_file) ? json_decode(file_get_contents($rate_limit_file), true) : ['number_limit' => 0, 'ip_limit' => 0];
$hadi_api = file_exists($hadi_api_file) ? json_decode(file_get_contents($hadi_api_file), true) : null;

// Load API configurations
$sms_api = @json_decode(@file_get_contents("api/sms.json"), true) ?? [];
$otp_api = @json_decode(@file_get_contents("api/otp.json"), true) ?? [];
$api1 = @json_decode(@file_get_contents("api/api1.json"), true) ?? [];
$api2 = @json_decode(@file_get_contents("api/api2.json"), true) ?? [];
$api3 = @json_decode(@file_get_contents("api/api3.json"), true) ?? [];
$api4 = @json_decode(@file_get_contents("api/api4.json"), true) ?? [];
$api5 = @json_decode(@file_get_contents("api/api5.json"), true) ?? [];

// Calculate stats for dashboard
$total_coins = 0;
$total_users = 0;
if (file_exists($balance_file)) {
    $balance_lines = file($balance_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $total_users = count($balance_lines);
    foreach ($balance_lines as $line) {
        if (strpos($line, ':') !== false) {
            [$user, $bal] = explode(':', $line);
            $total_coins += $bal;
        }
    }
}

$total_banned = count($ban_users);
$total_redeem_codes = file_exists($redeem_file) ? count(file($redeem_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;

// Get current page
$page = $_GET['page'] ?? 'home';

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #6c5ce7;
            --secondary: #a29bfe;
            --dark: #2d3436;
            --light: #f5f6fa;
            --success: #00b894;
            --danger: #d63031;
            --warning: #fdcb6e;
            --info: #0984e3;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #f5f7fa;
            color: var(--dark);
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary) 0%, #8c7ae6 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        .sidebar:hover {
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
        }
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
            animation: slideInLeft 0.5s ease-in-out;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        @keyframes slideInLeft {
            from { transform: translateX(-50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .sidebar-header h2 {
            display: flex;
            align-items: center;
            font-size: 1.3rem;
        }
        .sidebar-header h2 i {
            margin-right: 10px;
            color: var(--warning);
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .nav-menu {
            list-style: none;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .nav-item {
            margin: 5px 0;
            position: relative;
            overflow: hidden;
        }
        .nav-item:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--warning);
            transform: translateX(-100%);
            transition: all 0.3s ease;
        }
        .nav-item:hover:before {
            transform: translateX(0);
        }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 1.8rem;
        }
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 500;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .header h1 {
            color: var(--primary);
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .header h1 i {
            margin-right: 10px;
        }
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(214, 48, 49, 0.3);
        }
        .logout-btn i {
            margin-right: 5px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .card-header h3 {
            color: var(--primary);
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .card-header h3 i {
            margin-right: 10px;
            color: var(--secondary);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn i {
            margin-right: 5px;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: #5a4bd1;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(108, 92, 231, 0.3);
        }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(214, 48, 49, 0.3);
        }
        .btn-success {
            background: var(--success);
            color: white;
        }
        .btn-success:hover {
            background: #00a884;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 184, 148, 0.3);
        }
        .btn-warning {
            background: var(--warning);
            color: var(--dark);
        }
        .btn-warning:hover {
            background: #fdbb5e;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(253, 203, 110, 0.3);
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: var(--primary);
            color: white;
            font-weight: 500;
        }
        tr:nth-child(even) {
            background: rgba(108, 92, 231, 0.05);
        }
        tr:hover {
            background: rgba(108, 92, 231, 0.1);
        }
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success {
            background: rgba(0, 184, 148, 0.1);
            color: var(--success);
        }
        .badge-danger {
            background: rgba(214, 48, 49, 0.1);
            color: var(--danger);
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            animation: slideInDown 0.5s ease-in-out;
        }
        @keyframes slideInDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .alert-danger {
            background: rgba(214, 48, 49, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        .alert-success {
            background: rgba(0, 184, 148, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        .alert i {
            margin-right: 10px;
            font-size: 20px;
        }
        .input-group {
            display: flex;
            margin-bottom: 1rem;
        }
        .input-group input {
            flex: 1;
            border-radius: 5px 0 0 5px !important;
        }
        .input-group button {
            border-radius: 0 5px 5px 0 !important;
            width: auto;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 1rem;
        }
        .stat-card .icon.coin {
            background: rgba(253, 203, 110, 0.2);
            color: var(--warning);
        }
        .stat-card .icon.user {
            background: rgba(0, 184, 148, 0.2);
            color: var(--success);
        }
        .stat-card .icon.ban {
            background: rgba(214, 48, 49, 0.2);
            color: var(--danger);
        }
        .stat-card .icon.redeem {
            background: rgba(155, 89, 182, 0.2);
            color: #9b59b6;
        }
        .stat-card h3 {
            font-size: 14px;
            color: #777;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .stat-card p {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
        }
        .small-text {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        .action-btn {
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 4px;
            margin: 0 2px;
        }
        /* Notice and Bonus popup styles */
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
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideInUp 0.3s ease-in-out;
        }
        @keyframes slideInUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
            color: var(--danger);
            transform: rotate(90deg);
        }
        .popup-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
            text-align: center;
        }
        .popup-message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .popup-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        .bonus-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--warning);
            text-align: center;
            margin-bottom: 1rem;
        }
        .bonus-reason {
            font-size: 18px;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        .bonus-amount {
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            color: var(--success);
            margin-bottom: 1.5rem;
        }
        /* Mobile menu toggle */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        .menu-toggle i {
            display: block;
            width: 25px;
            height: 2px;
            background: white;
            margin: 5px 0;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 1100;
                background: rgba(0,0,0,0.5);
                border-radius: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i></i>
            <i></i>
            <i></i>
        </button>

        <!-- Sidebar Navigation -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin.php" class="nav-link<?php echo $page === 'home' ? ' active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=game" class="nav-link<?php echo $page === 'game' ? ' active' : ''; ?>">
                        <i class="fas fa-gamepad"></i> Game Control
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=user" class="nav-link<?php echo $page === 'user' ? ' active' : ''; ?>">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=notice" class="nav-link<?php echo $page === 'notice' ? ' active' : ''; ?>">
                        <i class="fas fa-bell"></i> Notice System
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=bonus" class="nav-link<?php echo $page === 'bonus' ? ' active' : ''; ?>">
                        <i class="fas fa-gift"></i> Bonus System
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=apk" class="nav-link<?php echo $page === 'apk' ? ' active' : ''; ?>">
                        <i class="fas fa-download"></i> APK Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=referral" class="nav-link<?php echo $page === 'referral' ? ' active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> Referral System
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=api" class="nav-link<?php echo $page === 'api' ? ' active' : ''; ?>">
                        <i class="fas fa-plug"></i> API Configuration
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=log" class="nav-link<?php echo $page === 'log' ? ' active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> SMS Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=log_api1" class="nav-link<?php echo $page === 'log_api1' ? ' active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> API1 Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=log_api2" class="nav-link<?php echo $page === 'log_api2' ? ' active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> API2 Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=log_api3" class="nav-link<?php echo $page === 'log_api3' ? ' active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> API3 Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=log_api4" class="nav-link<?php echo $page === 'log_api4' ? ' active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> API4 Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=log_api5" class="nav-link<?php echo $page === 'log_api5' ? ' active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> API5 Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=redeem" class="nav-link<?php echo $page === 'redeem' ? ' active' : ''; ?>">
                        <i class="fas fa-ticket-alt"></i> Redeem Codes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=spam" class="nav-link<?php echo $page === 'spam' ? ' active' : ''; ?>">
                        <i class="fas fa-ban"></i> Spam Filter
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=limit" class="nav-link<?php echo $page === 'limit' ? ' active' : ''; ?>">
                        <i class="fas fa-stopwatch"></i> Rate Limits
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=hadi" class="nav-link<?php echo $page === 'hadi' ? ' active' : ''; ?>">
                        <i class="fas fa-globe"></i> Hadi API
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?logout=1" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <h1>
                    <?php 
                    switch($page) {
                        case 'home': echo '<i class="fas fa-tachometer-alt"></i> Dashboard'; break;
                        case 'game': echo '<i class="fas fa-gamepad"></i> Game Control'; break;
                        case 'user': echo '<i class="fas fa-users"></i> User Management'; break;
                        case 'notice': echo '<i class="fas fa-bell"></i> Notice System'; break;
                        case 'bonus': echo '<i class="fas fa-gift"></i> Bonus System'; break;
                        case 'apk': echo '<i class="fas fa-download"></i> APK Management'; break;
                        case 'referral': echo '<i class="fas fa-user-plus"></i> Referral System'; break;
                        case 'api': echo '<i class="fas fa-plug"></i> API Configuration'; break;
                        case 'log': echo '<i class="fas fa-clipboard-list"></i> SMS Logs'; break;
                        case 'log_api1': echo '<i class="fas fa-clipboard-list"></i> API1 Logs'; break;
                        case 'log_api2': echo '<i class="fas fa-clipboard-list"></i> API2 Logs'; break;
                        case 'log_api3': echo '<i class="fas fa-clipboard-list"></i> API3 Logs'; break;
                        case 'log_api4': echo '<i class="fas fa-clipboard-list"></i> API4 Logs'; break;
                        case 'log_api5': echo '<i class="fas fa-clipboard-list"></i> API5 Logs'; break;
                        case 'redeem': echo '<i class="fas fa-ticket-alt"></i> Redeem Codes'; break;
                        case 'spam': echo '<i class="fas fa-ban"></i> Spam Filter'; break;
                        case 'limit': echo '<i class="fas fa-stopwatch"></i> Rate Limits'; break;
                        case 'hadi': echo '<i class="fas fa-globe"></i> Hadi API'; break;
                        default: echo '<i class="fas fa-shield-alt"></i> Admin Panel';
                    }
                    ?>
                </h1>
                <a href="?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <?php if ($page === 'home'): ?>
            <div class="grid">
                <div class="stat-card">
                    <div class="icon coin">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h3>Total Coins in System</h3>
                    <p><?php echo $total_coins; ?></p>
                    <span class="small-text">Across all users</span>
                </div>
                <div class="stat-card">
                    <div class="icon user">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h3>Registered Users</h3>
                    <p><?php echo $total_users; ?></p>
                    <span class="small-text">Total accounts</span>
                </div>
                <div class="stat-card">
                    <div class="icon ban">
                        <i class="fas fa-ban"></i>
                    </div>
                    <h3>Banned Users</h3>
                    <p><?php echo $total_banned; ?></p>
                    <span class="small-text">Currently restricted</span>
                </div>
                <div class="stat-card">
                    <div class="icon redeem">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3>Redeem Codes</h3>
                    <p><?php echo $total_redeem_codes; ?></p>
                    <span class="small-text">Active codes</span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-rocket"></i> Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="?page=game" class="btn btn-primary">
                            <i class="fas fa-sliders-h"></i> Game Settings
                        </a>
                        <a href="?page=user" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Manage Users
                        </a>
                        <a href="?page=notice" class="btn btn-warning">
                            <i class="fas fa-bell"></i> Send Notice
                        </a>
                        <a href="?page=bonus" class="btn" style="background: #e84393; color: white;">
                            <i class="fas fa-gift"></i> Create Bonus
                        </a>
                        <a href="?page=apk" class="btn" style="background: #00cec9; color: white;">
                            <i class="fas fa-download"></i> APK Settings
                        </a>
                        <a href="?page=referral" class="btn" style="background: #6c5ce7; color: white;">
                            <i class="fas fa-user-plus"></i> Referral System
                        </a>
                        <a href="?page=spam" class="btn" style="background: #d63031; color: white;">
                            <i class="fas fa-ban"></i> Spam Filter
                        </a>
                        <a href="?page=limit" class="btn" style="background: #00b894; color: white;">
                            <i class="fas fa-stopwatch"></i> Rate Limits
                        </a>
                        <a href="?page=hadi" class="btn" style="background: #0984e3; color: white;">
                            <i class="fas fa-globe"></i> Hadi API
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php
            switch ($page) {
                case 'game':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-sliders-h"></i> Game Settings</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="win_rate">Win Rate (%)</label>
                                    <input type="number" id="win_rate" name="win_rate" class="form-control" 
                                           value="<?php echo htmlspecialchars($win_rate); ?>" min="0" max="100">
                                </div>
                                <div class="form-group">
                                    <label for="speed">Plane Speed</label>
                                    <input type="number" id="speed" name="speed" class="form-control" 
                                           value="<?php echo htmlspecialchars($speed); ?>" min="1" max="10">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Settings
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php
                    break;

                case 'user':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-edit"></i> User Balance Management</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="target_user">Username</label>
                                    <input type="text" id="target_user" name="target_user" class="form-control" required>
                                </div>
                                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div class="form-group">
                                        <label for="add_coin">Add Coin</label>
                                        <input type="number" id="add_coin" name="add_coin" class="form-control" min="0">
                                    </div>
                                    <div class="form-group">
                                        <label for="remove_coin">Remove Coin</label>
                                        <input type="number" id="remove_coin" name="remove_coin" class="form-control" min="0">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Update Balance
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-lock"></i> Ban Management</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" style="margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label for="ban_user">Username to Ban</label>
                                    <div class="input-group">
                                        <input type="text" id="ban_user" name="ban_user" class="form-control">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-ban"></i> Ban User
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="unban_user">Username to Unban</label>
                                    <div class="input-group">
                                        <input type="text" id="unban_user" name="unban_user" class="form-control">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check-circle"></i> Unban User
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-gift"></i> Welcome Bonus</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="bonus_amount">Bonus Amount</label>
                                    <input type="number" id="bonus_amount" name="bonus_amount" class="form-control" 
                                           value="<?php echo $welcome_bonus; ?>" min="0">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Bonus
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-users-cog"></i> User List</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Coin Balance</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (file_exists($balance_file)) {
                                            foreach (file($balance_file, FILE_IGNORE_NEW_LINES) as $line) {
                                                if (strpos($line, ':') !== false) {
                                                    list($user, $bal) = explode(':', $line);
                                                    $status = in_array($user, $ban_users) ? 
                                                        '<span class="badge badge-danger"><i class="fas fa-ban"></i> BANNED</span>' : 
                                                        '<span class="badge badge-success"><i class="fas fa-check-circle"></i> ACTIVE</span>';
                                                    echo "<tr><td>$user</td><td>$bal</td><td>$status</td></tr>";
                                                }
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;

                case 'notice':
                    // Clean up expired notices (older than 24 hours)
                    $notices = file_exists($notice_file) ? file($notice_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                    $currentTime = time();
                    $newNotices = [];
                    
                    foreach ($notices as $noticeJson) {
                        $notice = json_decode($noticeJson, true);
                        if ($notice && ($currentTime - $notice['time']) < 86400) { // 86400 seconds = 24 hours
                            $newNotices[] = $noticeJson;
                        }
                    }
                    
                    // Save back if any notices were removed
                    if (count($newNotices) !== count($notices)) {
                        file_put_contents($notice_file, implode(PHP_EOL, $newNotices) . PHP_EOL);
                    }
                    
                    $notices = array_map('json_decode', $newNotices);
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-bell"></i> Send Notice</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="notice_content">Notice Content</label>
                                    <textarea id="notice_content" name="notice_content" class="form-control" rows="5" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="notice_target_user">Target User (leave empty for all users)</label>
                                    <input type="text" id="notice_target_user" name="notice_target_user" class="form-control">
                                </div>
                                <button type="submit" name="send_notice" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send Notice
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Notices</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Content</th>
                                            <th>Target</th>
                                            <th>Expires In</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach (array_reverse($notices) as $notice) {
                                            if (is_object($notice)) {
                                                $expiresIn = 24 - round((time() - $notice->time) / 3600, 1);
                                                $expiresText = $expiresIn > 0 ? "$expiresIn hours" : "Expired";
                                                
                                                echo "<tr>
                                                    <td>" . date('Y-m-d H:i', $notice->time) . "</td>
                                                    <td>{$notice->content}</td>
                                                    <td>" . ($notice->target === 'all' ? 'All Users' : $notice->target) . "</td>
                                                    <td>$expiresText</td>
                                                    <td>
                                                        <form method='POST' style='display:inline;'>
                                                            <input type='hidden' name='delete_notice' value='{$notice->id}'>
                                                            <button type='submit' class='btn btn-danger action-btn' onclick='return confirm(\"Are you sure you want to delete this notice?\")'>
                                                                <i class='fas fa-trash'></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;

                case 'bonus':
                    $bonuses = file_exists($bonus_data_file) ? json_decode(file_get_contents($bonus_data_file), true) : [];
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-gift"></i> Create Bonus</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="bonus_amount">Bonus Amount</label>
                                    <input type="number" id="bonus_amount" name="bonus_amount" class="form-control" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label for="bonus_target_user">Target User (leave empty for all users)</label>
                                    <input type="text" id="bonus_target_user" name="bonus_target_user" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="bonus_user_limit">User Limit (first X users, 0 for unlimited)</label>
                                    <input type="number" id="bonus_user_limit" name="bonus_user_limit" class="form-control" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="bonus_duration">Duration (hours, 0 for 24 hours)</label>
                                    <input type="number" id="bonus_duration" name="bonus_duration" class="form-control" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="bonus_reason">Reason (required)</label>
                                    <input type="text" id="bonus_reason" name="bonus_reason" class="form-control" required>
                                </div>
                                <button type="submit" name="send_bonus" class="btn btn-primary">
                                    <i class="fas fa-gift"></i> Create Bonus
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Active Bonuses</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Amount</th>
                                            <th>Target</th>
                                            <th>Limit</th>
                                            <th>Duration</th>
                                            <th>Reason</th>
                                            <th>Claims</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach (array_reverse($bonuses) as $id => $bonus) {
                                            $expired = (time() - $bonus['time']) > $bonus['duration'];
                                            $target = $bonus['target'] === 'all' ? 'All Users' : $bonus['target'];
                                            $limit = $bonus['user_limit'] === 'unlimited' ? 'Unlimited' : $bonus['user_limit'];
                                            $duration = $bonus['duration'] / 3600 . ' hours';
                                            $claims = count($bonus['claimed_by']) . ($bonus['user_limit'] !== 'unlimited' ? '/' . $bonus['user_limit'] : '');
                                            
                                            echo "<tr>
                                                <td>" . date('Y-m-d H:i', $bonus['time']) . "</td>
                                                <td>{$bonus['amount']}</td>
                                                <td>$target</td>
                                                <td>$limit</td>
                                                <td>$duration</td>
                                                <td>{$bonus['reason']}</td>
                                                <td>$claims</td>
                                            </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;

                case 'apk':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-download"></i> APK Management</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="apk_link">APK Download Link</label>
                                    <input type="url" id="apk_link" name="apk_link" class="form-control" 
                                           value="<?php echo htmlspecialchars($apk_link); ?>" required>
                                </div>
                                <button type="submit" name="save_apk" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save APK Link
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Current APK Link</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($apk_link)): ?>
                                <p>Current APK link: <a href="<?php echo htmlspecialchars($apk_link); ?>" target="_blank"><?php echo htmlspecialchars($apk_link); ?></a></p>
                                <p>Users will see this link in their dashboard for downloading the APK.</p>
                            <?php else: ?>
                                <p>No APK link has been set yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    break;

                case 'referral':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-plus"></i> Referral System</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="referral_coin">Coin Reward per Referral</label>
                                    <input type="number" id="referral_coin" name="referral_coin" class="form-control" 
                                           value="<?php echo $referral_reward; ?>" min="1" required>
                                </div>
                                <button type="submit" name="save_referral" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> How It Works</h3>
                        </div>
                        <div class="card-body">
                            <p>Users will get a referral link in this format: <code>https://hl-hadi.info.gf/cmsg?ref=USERNAME</code></p>
                            <p>When someone signs up using their referral link, both users will receive the coin reward you set above.</p>
                            <p>Current reward: <strong><?php echo $referral_reward; ?> coins</strong> per successful referral.</p>
                        </div>
                    </div>
                    <?php
                    break;

                case 'api':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-sms"></i> SMS API Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="sms_api_url">API URL</label>
                                    <input type="text" id="sms_api_url" name="sms_api_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($sms_api['api_url'] ?? ''); ?>" 
                                           placeholder="http://example.com/api?number={number}&msg={msg}">
                                    <small style="color: #666;">Note: Use {number} and {msg} as placeholders</small>
                                </div>
                                <div class="form-group">
                                    <label for="sms_coin">Coin Deduction per SMS</label>
                                    <input type="number" id="sms_coin" name="sms_coin" class="form-control" 
                                           value="<?php echo htmlspecialchars(@file_get_contents('remove_coin.txt') ?: 1); ?>" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="sms_status">API Status</label>
                                    <select id="sms_status" name="sms_status" class="form-control">
                                        <option value="on" <?php echo ($sms_api['status'] ?? '') === 'on' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="off" <?php echo ($sms_api['status'] ?? '') === 'off' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" name="save_sms" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                    <button type="submit" name="delete_sms" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete SMS API configuration?')">
                                        <i class="fas fa-trash-alt"></i> Delete Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-key"></i> OTP API Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="otp_api_url">API URL</label>
                                    <input type="text" id="otp_api_url" name="otp_api_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($otp_api['api_url'] ?? ''); ?>" 
                                           placeholder="http://example.com/api?number={number}&msg={msg}">
                                    <small style="color: #666;">Note: Use {number} and {msg} as placeholders</small>
                                </div>
                                <div class="form-group">
                                    <label for="otp_status">API Status</label>
                                    <select id="otp_status" name="otp_status" class="form-control">
                                        <option value="on" <?php echo ($otp_api['status'] ?? '') === 'on' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="off" <?php echo ($otp_api['status'] ?? '') === 'off' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" name="save_otp" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                    <button type="submit" name="delete_otp" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete OTP API configuration?')">
                                        <i class="fas fa-trash-alt"></i> Delete Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- API 1 Configuration -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-plug"></i> API 1 Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="api1_url">API URL</label>
                                    <input type="text" id="api1_url" name="api1_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($api1['api_url'] ?? ''); ?>" 
                                           placeholder="http://example.com/api?number={number}&msg={msg}">
                                    <small style="color: #666;">Note: Use {number} and {msg} as placeholders</small>
                                </div>
                                <div class="form-group">
                                    <label for="api1_coin">Coin Deduction</label>
                                    <input type="number" id="api1_coin" name="api1_coin" class="form-control" 
                                           value="<?php echo htmlspecialchars($api1['coin'] ?? 1); ?>" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="api1_status">API Status</label>
                                    <select id="api1_status" name="api1_status" class="form-control">
                                        <option value="on" <?php echo ($api1['status'] ?? '') === 'on' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="off" <?php echo ($api1['status'] ?? '') === 'off' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" name="save_api1" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                    <button type="submit" name="delete_api1" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete API 1 configuration?')">
                                        <i class="fas fa-trash-alt"></i> Delete Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- API 2 Configuration -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-plug"></i> API 2 Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="api2_url">API URL</label>
                                    <input type="text" id="api2_url" name="api2_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($api2['api_url'] ?? ''); ?>" 
                                           placeholder="http://example.com/api?number={number}&msg={msg}">
                                    <small style="color: #666;">Note: Use {number} and {msg} as placeholders</small>
                                </div>
                                <div class="form-group">
                                    <label for="api2_coin">Coin Deduction</label>
                                    <input type="number" id="api2_coin" name="api2_coin" class="form-control" 
                                           value="<?php echo htmlspecialchars($api2['coin'] ?? 1); ?>" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="api2_status">API Status</label>
                                    <select id="api2_status" name="api2_status" class="form-control">
                                        <option value="on" <?php echo ($api2['status'] ?? '') === 'on' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="off" <?php echo ($api2['status'] ?? '') === 'off' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" name="save_api2" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                    <button type="submit" name="delete_api2" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete API 2 configuration?')">
                                        <i class="fas fa-trash-alt"></i> Delete Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- API 3 Configuration -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-plug"></i> API 3 Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="api3_url">API URL</label>
                                    <input type="text" id="api3_url" name="api3_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($api3['api_url'] ?? ''); ?>" 
                                           placeholder="http://example.com/api?number={number}&msg={msg}">
                                    <small style="color: #666;">Note: Use {number} and {msg} as placeholders</small>
                                </div>
                                <div class="form-group">
                                    <label for="api3_coin">Coin Deduction</label>
                                    <input type="number" id="api3_coin" name="api3_coin" class="form-control" 
                                           value="<?php echo htmlspecialchars($api3['coin'] ?? 1); ?>" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="api3_status">API Status</label>
                                    <select id="api3_status" name="api3_status" class="form-control">
                                        <option value="on" <?php echo ($api3['status'] ?? '') === 'on' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="off" <?php echo ($api3['status'] ?? '') === 'off' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" name="save_api3" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                    <button type="submit" name="delete_api3" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete API 3 configuration?')">
                                        <i class="fas fa-trash-alt"></i> Delete Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- API 4 Configuration -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-plug"></i> API 4 Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="api4_url">API URL</label>
                                    <input type="text" id="api4_url" name="api4_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($api4['api_url'] ?? ''); ?>" 
                                           placeholder="http://example.com/api?number={number}&msg={msg}">
                                    <small style="color: #666;">Note: Use {number} and {msg} as placeholders</small>
                                </div>
                                <div class="form-group">
                                    <label for="api4_coin">Coin Deduction</label>
                                    <input type="number" id="api4_coin" name="api4_coin" class="form-control" 
                                           value="<?php echo htmlspecialchars($api4['coin'] ?? 1); ?>" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="api4_status">API Status</label>
                                    <select id="api4_status" name="api4_status" class="form-control">
                                        <option value="on" <?php echo ($api4['status'] ?? '') === 'on' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="off" <?php echo ($api4['status'] ?? '') === 'off' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" name="save_api4" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                    <button type="submit" name="delete_api4" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete API 4 configuration?')">
                                        <i class="fas fa-trash-alt"></i> Delete Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- API 5 Configuration -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-plug"></i> API 5 Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="api5_url">API URL</label>
                                    <input type="text" id="api5_url" name="api5_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($api5['api_url'] ?? ''); ?>" 
                                           placeholder="http://example.com/api?number={number}&msg={msg}">
                                    <small style="color: #666;">Note: Use {number} and {msg} as placeholders</small>
                                </div>
                                <div class="form-group">
                                    <label for="api5_coin">Coin Deduction</label>
                                    <input type="number" id="api5_coin" name="api5_coin" class="form-control" 
                                           value="<?php echo htmlspecialchars($api5['coin'] ?? 1); ?>" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="api5_status">API Status</label>
                                    <select id="api5_status" name="api5_status" class="form-control">
                                        <option value="on" <?php echo ($api5['status'] ?? '') === 'on' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="off" <?php echo ($api5['status'] ?? '') === 'off' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" name="save_api5" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                    <button type="submit" name="delete_api5" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete API 5 configuration?')">
                                        <i class="fas fa-trash-alt"></i> Delete Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    break;

                case 'log':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clipboard-list"></i> SMS Logs</h3>
                        </div>
                        <div class="card-body">
                            <?php if (file_exists($log_file)): ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Username</th>
                                                <th>API Key</th>
                                                <th>Number</th>
                                                <th>Message</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                            foreach (array_reverse($lines) as $json) {
                                                $entry = json_decode($json, true);
                                                if ($entry) {
                                                    echo "<tr>
                                                        <td>{$entry['ip']}</td>
                                                        <td>{$entry['username']}</td>
                                                        <td>{$entry['api_key']}</td>
                                                        <td>{$entry['number']}</td>
                                                        <td>" . htmlspecialchars($entry['msg']) . "</td>
                                                        <td>{$entry['time']}</td>
                                                    </tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p style="text-align: center; color: #666;">No logs found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    break;

                case 'log_api1':
                    $log_file = "logs_api1.txt";
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clipboard-list"></i> API 1 Logs</h3>
                        </div>
                        <div class="card-body">
                            <?php if (file_exists($log_file)): ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Username</th>
                                                <th>API Key</th>
                                                <th>Number</th>
                                                <th>Message</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                            foreach (array_reverse($lines) as $json) {
                                                $entry = json_decode($json, true);
                                                if ($entry) {
                                                    echo "<tr>
                                                        <td>{$entry['ip']}</td>
                                                        <td>{$entry['username']}</td>
                                                        <td>{$entry['api_key']}</td>
                                                        <td>{$entry['number']}</td>
                                                        <td>" . htmlspecialchars($entry['msg']) . "</td>
                                                        <td>{$entry['time']}</td>
                                                    </tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p style="text-align: center; color: #666;">No logs found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    break;

                case 'log_api2':
                    $log_file = "logs_api2.txt";
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clipboard-list"></i> API 2 Logs</h3>
                        </div>
                        <div class="card-body">
                            <?php if (file_exists($log_file)): ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Username</th>
                                                <th>API Key</th>
                                                <th>Number</th>
                                                <th>Message</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                            foreach (array_reverse($lines) as $json) {
                                                $entry = json_decode($json, true);
                                                if ($entry) {
                                                    echo "<tr>
                                                        <td>{$entry['ip']}</td>
                                                        <td>{$entry['username']}</td>
                                                        <td>{$entry['api_key']}</td>
                                                        <td>{$entry['number']}</td>
                                                        <td>" . htmlspecialchars($entry['msg']) . "</td>
                                                        <td>{$entry['time']}</td>
                                                    </tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p style="text-align: center; color: #666;">No logs found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    break;

                case 'log_api3':
                    $log_file = "logs_api3.txt";
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clipboard-list"></i> API 3 Logs</h3>
                        </div>
                        <div class="card-body">
                            <?php if (file_exists($log_file)): ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Username</th>
                                                <th>API Key</th>
                                                <th>Number</th>
                                                <th>Message</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                            foreach (array_reverse($lines) as $json) {
                                                $entry = json_decode($json, true);
                                                if ($entry) {
                                                    echo "<tr>
                                                        <td>{$entry['ip']}</td>
                                                        <td>{$entry['username']}</td>
                                                        <td>{$entry['api_key']}</td>
                                                        <td>{$entry['number']}</td>
                                                        <td>" . htmlspecialchars($entry['msg']) . "</td>
                                                        <td>{$entry['time']}</td>
                                                    </tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p style="text-align: center; color: #666;">No logs found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    break;

                case 'log_api4':
                    $log_file = "logs_api4.txt";
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clipboard-list"></i> API 4 Logs</h3>
                        </div>
                        <div class="card-body">
                            <?php if (file_exists($log_file)): ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Username</th>
                                                <th>API Key</th>
                                                <th>Number</th>
                                                <th>Message</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                            foreach (array_reverse($lines) as $json) {
                                                $entry = json_decode($json, true);
                                                if ($entry) {
                                                    echo "<tr>
                                                        <td>{$entry['ip']}</td>
                                                        <td>{$entry['username']}</td>
                                                        <td>{$entry['api_key']}</td>
                                                        <td>{$entry['number']}</td>
                                                        <td>" . htmlspecialchars($entry['msg']) . "</td>
                                                        <td>{$entry['time']}</td>
                                                    </tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p style="text-align: center; color: #666;">No logs found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    break;

                case 'log_api5':
                    $log_file = "logs_api5.txt";
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clipboard-list"></i> API 5 Logs</h3>
                        </div>
                        <div class="card-body">
                            <?php if (file_exists($log_file)): ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Username</th>
                                                <th>API Key</th>
                                                <th>Number</th>
                                                <th>Message</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                            foreach (array_reverse($lines) as $json) {
                                                $entry = json_decode($json, true);
                                                if ($entry) {
                                                    echo "<tr>
                                                        <td>{$entry['ip']}</td>
                                                        <td>{$entry['username']}</td>
                                                        <td>{$entry['api_key']}</td>
                                                        <td>{$entry['number']}</td>
                                                        <td>" . htmlspecialchars($entry['msg']) . "</td>
                                                        <td>{$entry['time']}</td>
                                                    </tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p style="text-align: center; color: #666;">No logs found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    break;

                case 'redeem':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-ticket-alt"></i> Redeem Code Generator</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="code">Redeem Code</label>
                                    <div class="input-group">
                                        <input type="text" id="code" name="code" class="form-control" placeholder="Leave empty for random">
                                        <button type="button" class="btn btn-secondary" onclick="generateCode()">
                                            <i class="fas fa-random"></i> Generate
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="coin">Coin Amount</label>
                                    <input type="number" id="coin" name="coin" class="form-control" required min="1">
                                </div>
                                <div class="form-group">
                                    <label for="limit">Usage Limit (leave empty for unlimited)</label>
                                    <input type="number" id="limit" name="limit" class="form-control" min="1">
                                </div>
                                <button type="submit" name="create_redeem" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Create Redeem Code
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Active Redeem Codes</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Coin Amount</th>
                                            <th>Usage Limit</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (file_exists($redeem_file)) {
                                            foreach (file($redeem_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                                                [$code, $coin, $limit] = explode(':', $line);
                                                echo "<tr>
                                                    <td>$code</td>
                                                    <td>$coin</td>
                                                    <td>" . ($limit === 'unlimited' ? 'Unlimited' : $limit) . "</td>
                                                    <td>
                                                        <form method='POST' style='display:inline;'>
                                                            <input type='hidden' name='delete_redeem_code' value='$code'>
                                                            <button type='submit' class='btn btn-danger action-btn' onclick='return confirm(\"Are you sure you want to delete this redeem code?\")'>
                                                                <i class='fas fa-trash'></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Redeem History</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" style="margin-bottom: 1rem;">
                                <button type="submit" name="clear_redeem_history" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to clear ALL redeem history? This cannot be undone.')">
                                    <i class="fas fa-trash"></i> Clear All History
                                </button>
                            </form>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Code</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (file_exists($redeem_user_file)) {
                                            foreach (file($redeem_user_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                                                [$user, $code, $time] = explode(':', $line);
                                                echo "<tr>
                                                    <td>$user</td>
                                                    <td>$code</td>
                                                    <td>$time</td>
                                                </tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <script>
                    function generateCode() {
                        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                        let code = '';
                        for (let i = 0; i < 10; i++) {
                            code += chars.charAt(Math.floor(Math.random() * chars.length));
                        }
                        document.getElementById('code').value = code;
                    }
                    </script>
                    <?php
                    break;

                case 'spam':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-ban"></i> Spam SMS Filter</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="spam_words">Spam Words (comma separated)</label>
                                    <textarea id="spam_words" name="spam_words" class="form-control" rows="5" placeholder="Enter words to block, separated by commas (e.g. fuck,hi,hello)"><?php echo htmlspecialchars($spam_words); ?></textarea>
                                    <small class="text-muted">Any SMS containing these words will be blocked and user will receive a notification</small>
                                </div>
                                <button type="submit" name="save_spam_words" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Spam Words
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Current Spam Words</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($spam_words)): ?>
                                <p>Current spam words: <strong><?php echo htmlspecialchars($spam_words); ?></strong></p>
                                <p>Any SMS containing these words will be blocked and the user will receive a notification.</p>
                            <?php else: ?>
                                <p>No spam words have been set yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    break;

                case 'limit':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-stopwatch"></i> Rate Limits</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="number_limit">Number Limit (minutes)</label>
                                    <input type="number" id="number_limit" name="number_limit" class="form-control" 
                                           value="<?php echo $rate_limits['number_limit']; ?>" min="0">
                                    <small class="text-muted">Time (in minutes) between SMS requests from the same number (0 = no limit)</small>
                                </div>
                                <div class="form-group">
                                    <label for="ip_limit">IP Limit (minutes)</label>
                                    <input type="number" id="ip_limit" name="ip_limit" class="form-control" 
                                           value="<?php echo $rate_limits['ip_limit']; ?>" min="0">
                                    <small class="text-muted">Time (in minutes) between SMS requests from the same IP (0 = no limit)</small>
                                </div>
                                <button type="submit" name="save_rate_limit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Rate Limits
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Current Rate Limits</h3>
                        </div>
                        <div class="card-body">
                            <p>Current number limit: <strong><?php echo $rate_limits['number_limit'] == 0 ? 'No limit' : $rate_limits['number_limit'] . ' minutes'; ?></strong></p>
                            <p>Current IP limit: <strong><?php echo $rate_limits['ip_limit'] == 0 ? 'No limit' : $rate_limits['ip_limit'] . ' minutes'; ?></strong></p>
                            <p>Users will have to wait for the specified time before sending another SMS from the same number or IP.</p>
                        </div>
                    </div>
                    <?php
                    break;

                case 'hadi':
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-plug"></i> Hadi API Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="api_url">API URL</label>
                                    <input type="url" id="api_url" name="api_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($hadi_api['api_url'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="amount">Coin Deduction Amount</label>
                                    <input type="number" id="amount" name="amount" class="form-control" 
                                           value="<?php echo htmlspecialchars($hadi_api['amount'] ?? 1); ?>" min="1" required>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" name="save_hadi_api" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save API Settings
                                    </button>
                                    <button type="submit" name="delete_hadi_api" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete Hadi API configuration?')">
                                        <i class="fas fa-trash-alt"></i> Delete Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    break;

                default:
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-tachometer-alt"></i> System Overview</h3>
                        </div>
                        <div class="card-body">
                            <p>Welcome to the admin panel. Use the navigation menu to manage different aspects of the system.</p>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>

    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('active');
    }
    </script>
</body>
</html>