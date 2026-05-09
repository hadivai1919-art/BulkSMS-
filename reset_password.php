<?php
session_start();

if (!isset($_SESSION['reset_otp'], $_SESSION['reset_user_file'])) {
    die("❌ Invalid request. Please go back and try again.");
}

$generated_otp = $_SESSION['reset_otp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $newpass = trim($_POST['password']);

    if ($otp == $_SESSION['reset_otp']) {
        $file = $_SESSION['reset_user_file'];
        if (!file_exists($file)) {
            die("❌ User file not found.");
        }

        $userData = json_decode(file_get_contents($file), true);
        $userData['password'] = $newpass;
        file_put_contents($file, json_encode($userData, JSON_PRETTY_PRINT));

        unset($_SESSION['reset_otp'], $_SESSION['reset_user_file']);
        
        // AJAX রেসপন্স
        echo json_encode(['status' => 'success', 'redirect' => 'login.php']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => '❌ Incorrect OTP.']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Secure System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #6c5ce7;
            --primary-dark: #5649c0;
            --secondary: #a29bfe;
            --light: #f8f9fa;
            --dark: #2d3436;
            --success: #00b894;
            --error: #d63031;
            --warning: #fdcb6e;
            --border-radius: 12px;
            --box-shadow: 0 15px 30px rgba(0,0,0,0.12);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        
        .reset-container {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 450px;
            padding: 50px 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transform: translateY(0) scale(1);
            opacity: 1;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 2;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .reset-container.success-border {
            border: 3px solid var(--success);
            animation: wavy-green 2s infinite ease-in-out;
        }

        .reset-container.error-border {
            border: 3px solid var(--error);
            animation: shake-red 0.5s ease-in-out;
        }

        @keyframes wavy-green {
            0%, 100% { border-radius: 12px 15px 12px 15px; transform: scale(1.02); }
            50% { border-radius: 15px 12px 15px 12px; transform: scale(1.03); }
        }

        @keyframes shake-red {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .reset-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(108, 92, 231, 0.1) 0%,
                rgba(108, 92, 231, 0) 50%,
                rgba(108, 92, 231, 0.1) 100%
            );
            transform: rotate(30deg);
            z-index: -1;
            animation: shine 8s infinite linear;
        }
        
        @keyframes shine {
            0% { transform: rotate(30deg) translateX(-100%); }
            100% { transform: rotate(30deg) translateX(100%); }
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .reset-header h2 {
            color: var(--dark);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(to right, #6c5ce7, #a29bfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .reset-header p {
            color: #636e72;
            font-size: 15px;
        }
        
        /* OTP Display Box */
        .otp-display-box {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            margin: 25px 0;
            box-shadow: 0 10px 20px rgba(108, 92, 231, 0.3);
            position: relative;
            overflow: hidden;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .otp-display-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #00b894, #55efc4);
            animation: progress 5s linear infinite;
        }

        @keyframes progress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .otp-display-box h3 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .otp-code {
            font-size: 42px;
            font-weight: 700;
            letter-spacing: 10px;
            font-family: 'Courier New', monospace;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            margin: 10px 0;
            cursor: pointer;
            transition: var(--transition);
        }

        .otp-code:hover {
            transform: scale(1.05);
        }

        .warning-note {
            background: rgba(253, 203, 110, 0.15);
            border-left: 4px solid var(--warning);
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-size: 13px;
            color: #636e72;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #dfe6e9;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
            background: rgba(255,255,255,0.8);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.2);
            outline: none;
            transform: translateY(-2px);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.4);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 92, 231, 0.5);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            opacity: 0;
            transition: var(--transition);
            z-index: -1;
        }
        
        .btn:hover::after {
            opacity: 1;
        }
        
        .btn.loading {
            pointer-events: none;
        }
        
        .btn-text {
            position: relative;
            z-index: 1;
        }
        
        .error-message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            background-color: rgba(214, 48, 49, 0.15);
            color: var(--error);
            border: 2px solid var(--error);
            animation: fadeIn 0.5s ease-out;
            display: none;
        }
        
        .success-message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            background-color: rgba(0, 184, 148, 0.15);
            color: var(--success);
            border: 2px solid var(--success);
            display: none;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
            backdrop-filter: blur(3px);
        }
        
        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .loader {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(108, 92, 231, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-100px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .back-link:hover {
            text-decoration: underline;
        }

        .copy-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            margin-top: 10px;
            transition: var(--transition);
        }

        .copy-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .copy-btn.copied {
            background: #00b894;
        }
        
        @media (max-width: 480px) {
            .reset-container {
                padding: 40px 25px;
            }
            
            .reset-header h2 {
                font-size: 24px;
            }
            
            .otp-code {
                font-size: 32px;
                letter-spacing: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element" style="width: 100px; height: 100px; top: 10%; left: 10%; animation-delay: 0s;"></div>
        <div class="floating-element" style="width: 150px; height: 150px; top: 60%; left: 80%; animation-delay: 2s;"></div>
        <div class="floating-element" style="width: 80px; height: 80px; top: 80%; left: 20%; animation-delay: 4s;"></div>
    </div>
    
    <div class="reset-container animate__animated animate__fadeInUp" id="resetContainer">
        <div id="jsErrorMessage" class="error-message"></div>
        <div id="jsSuccessMessage" class="success-message"></div>
        
        <div class="reset-header">
            <h2>Reset Your Password</h2>
            <p>Enter the OTP and your new password</p>
        </div>
        
        <!-- OTP Display Box -->
        <div class="otp-display-box">
            <h3>Your OTP Code is:</h3>
            <div class="otp-code" id="displayOtp"><?= $generated_otp ?></div>
            <button type="button" class="copy-btn" id="copyBtn" onclick="copyOTP()">
                📋 Copy OTP
            </button>
        </div>

        <div class="warning-note">
            ⚠️ <strong>Important:</strong> Enter the OTP shown above to reset your password.
        </div>
        
        <form method="POST" id="resetForm">
            <div class="form-group">
                <label for="otp">Enter 6-digit OTP</label>
                <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" required>
            </div>
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your new password" required>
            </div>
            
            <button type="submit" class="btn" id="resetBtn">
                <span class="btn-text" id="btnText">Reset Password</span>
            </button>
        </form>
        
        <a href="login.php" class="back-link">Back to Login</a>
        
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loader"></div>
        </div>
    </div>

    <script>
        const generatedOTP = "<?= $generated_otp ?>";

        // Copy OTP to clipboard
        function copyOTP() {
            navigator.clipboard.writeText(generatedOTP).then(() => {
                const copyBtn = document.getElementById('copyBtn');
                const originalText = copyBtn.innerHTML;
                copyBtn.innerHTML = '✅ Copied!';
                copyBtn.classList.add('copied');
                
                const successDiv = document.getElementById('jsSuccessMessage');
                successDiv.textContent = '✅ OTP copied to clipboard!';
                successDiv.style.display = 'block';
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                    copyBtn.classList.remove('copied');
                    successDiv.style.display = 'none';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy OTP: ', err);
            });
        }

        // Auto-fill OTP when clicked
        document.getElementById('displayOtp').addEventListener('click', function() {
            document.getElementById('otp').value = generatedOTP;
            document.getElementById('otp').focus();
        });

        // Form submission
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const container = document.getElementById('resetContainer');
            const overlay = document.getElementById('loadingOverlay');
            const btn = document.getElementById('resetBtn');
            const btnText = document.getElementById('btnText');
            const errorDiv = document.getElementById('jsErrorMessage');
            const successDiv = document.getElementById('jsSuccessMessage');

            // Reset states
            container.classList.remove('success-border', 'error-border');
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            // Show loading state
            overlay.classList.add('active');
            btn.classList.add('loading');
            btnText.textContent = 'Resetting Password...';
            
            // Use Fetch API for AJAX submission
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                setTimeout(() => {
                    overlay.classList.remove('active');
                    btn.classList.remove('loading');

                    if (data.status === 'success') {
                        // Success visual feedback
                        container.classList.add('success-border');
                        btnText.textContent = 'Password Reset!';
                        successDiv.textContent = '✅ Password reset successfully! Redirecting...';
                        successDiv.style.display = 'block';
                        
                        // Redirect after animation
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else {
                        // Error visual feedback
                        container.classList.add('error-border');
                        errorDiv.textContent = data.message;
                        errorDiv.style.display = 'block';
                        btnText.textContent = 'Reset Password';
                        
                        setTimeout(() => {
                            container.classList.remove('error-border');
                        }, 1000);
                    }
                }, 800);
            })
            .catch(error => {
                overlay.classList.remove('active');
                btn.classList.remove('loading');
                btnText.textContent = 'Reset Password';
                
                errorDiv.textContent = '❌ Something went wrong. Please try again.';
                errorDiv.style.display = 'block';
            });
        });
        
        // Auto-focus OTP input
        document.getElementById('otp').focus();
        
        // Add floating elements
        function createFloatingElements() {
            const container = document.querySelector('.floating-elements');
            const colors = ['rgba(108, 92, 231, 0.1)', 'rgba(162, 155, 254, 0.1)', 'rgba(255, 255, 255, 0.2)'];
            
            for (let i = 0; i < 5; i++) {
                const element = document.createElement('div');
                element.className = 'floating-element';
                
                const size = Math.random() * 100 + 50;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const delay = Math.random() * 5;
                const duration = Math.random() * 10 + 10;
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                element.style.width = `${size}px`;
                element.style.height = `${size}px`;
                element.style.top = `${posY}%`;
                element.style.left = `${posX}%`;
                element.style.animationDelay = `${delay}s`;
                element.style.animationDuration = `${duration}s`;
                element.style.background = color;
                
                container.appendChild(element);
            }
        }
        
        window.addEventListener('load', () => {
            createFloatingElements();
        });
    </script>
</body>
</html>