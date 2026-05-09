<?php
// File: verify_captcha.php
// ক্যাপচা ভেরিফাই করার জন্য ফাংশন

function verify_captcha($user_input) {
    session_start();
    
    // Check if captcha exists and not expired (5 minutes)
    if (!isset($_SESSION['captcha_time']) || (time() - $_SESSION['captcha_time']) > 300) {
        return ['success' => false, 'message' => 'CAPTCHA expired'];
    }
    
    if (!isset($_SESSION['captcha_code'])) {
        return ['success' => false, 'message' => 'CAPTCHA not found'];
    }
    
    // Verify with IP binding
    $hashed_input = md5(trim($user_input) . 'YOUR_SECRET_KEY_' . $_SERVER['REMOTE_ADDR']);
    
    if ($hashed_input === $_SESSION['captcha_code']) {
        // Clear after successful verification
        unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
        
        // Log successful attempt
        if (!is_dir('logs')) mkdir('logs');
        $log = date('Y-m-d H:i:s') . " | IP: " . $_SERVER['REMOTE_ADDR'] . " | CAPTCHA Verified\n";
        file_put_contents('logs/captcha_log.txt', $log, FILE_APPEND);
        
        return ['success' => true, 'message' => 'CAPTCHA verified'];
    } else {
        // Log failed attempt
        if (!is_dir('logs')) mkdir('logs');
        $log = date('Y-m-d H:i:s') . " | IP: " . $_SERVER['REMOTE_ADDR'] . " | CAPTCHA Failed: $user_input\n";
        file_put_contents('logs/captcha_log.txt', $log, FILE_APPEND);
        
        return ['success' => false, 'message' => 'Invalid CAPTCHA'];
    }
}

// Usage example:
// $result = verify_captcha($_POST['captcha']);
// if (!$result['success']) { echo $result['message']; exit; }
?>