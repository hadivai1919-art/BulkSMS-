<?php
session_start();
$number = $_GET['number'] ?? '';

// Generate random OTP if not already generated
if (!isset($_SESSION['generated_otp']) || empty($_SESSION['generated_otp'])) {
    $otp = rand(100000, 999999);
    $_SESSION['generated_otp'] = $otp;
    $_SESSION['otp_time'] = time();
    
    // Save OTP to file (for verification)
    file_put_contents("otp/$number.txt", $otp);
}

$generated_otp = $_SESSION['generated_otp'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered = $_POST['otp'] ?? '';
    $saved = trim(file_get_contents("otp/$number.txt"));

    // Check if OTP is expired (5 minutes)
    if (time() - $_SESSION['otp_time'] > 300) {
        echo json_encode(['status' => 'error', 'message' => 'OTP expired! Please request a new one.']);
        exit;
    }

    if ($entered == $saved) {
        $userdata = json_decode(file_get_contents("otp/{$number}_user.txt"), true);
        file_put_contents("users/{$userdata['username']}.txt", json_encode($userdata));
        unlink("otp/$number.txt");
        unlink("otp/{$number}_user.txt");
        
        // Clear session OTP data
        unset($_SESSION['generated_otp']);
        unset($_SESSION['otp_time']);
        
        // Return JSON for AJAX handling
        echo json_encode(['status' => 'success', 'redirect' => 'login.php?success=registered']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP!']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification | Premium System</title>
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
        
        .otp-container {
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
            border: 2px solid transparent;
        }

        /* Success and Error Border Styles */
        .otp-container.success-border {
            border: 3px solid var(--success);
            animation: wavy-green 2s infinite ease-in-out;
        }

        .otp-container.error-border {
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
        
        .otp-container::before {
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
        
        .otp-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .otp-header h1 {
            color: var(--dark);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(to right, #6c5ce7, #a29bfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .otp-header p {
            color: #636e72;
            font-size: 15px;
            margin-bottom: 5px;
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
        }

        .otp-timer {
            font-size: 13px;
            color: rgba(255,255,255,0.8);
            margin-top: 10px;
            font-weight: 500;
        }

        .otp-timer.expired {
            color: #ff7675;
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

        .warning-note i {
            color: var(--warning);
            margin-right: 8px;
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
            text-align: center;
            letter-spacing: 10px;
            font-size: 24px;
            font-weight: 600;
            font-family: 'Courier New', monospace;
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
            margin-top: 10px;
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
            opacity: 0.8;
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
        
        .phone-number {
            font-weight: 600;
            color: var(--primary);
            background: rgba(108, 92, 231, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 5px;
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
            .otp-container {
                padding: 40px 25px;
            }
            
            .otp-header h1 {
                font-size: 28px;
            }
            
            .otp-code {
                font-size: 32px;
                letter-spacing: 8px;
            }
            
            .form-control {
                font-size: 20px;
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
    
    <div class="otp-container animate__animated animate__fadeInUp" id="otpContainer">
        <div id="jsErrorMessage" class="error-message animate__animated animate__bounceIn"></div>
        <div id="jsSuccessMessage" class="success-message animate__animated animate__bounceIn"></div>
        
        <div class="otp-header">
            <h1>OTP Verification</h1>
            <p>We've sent a verification code to</p>
            <div class="phone-number"><?= htmlspecialchars($number) ?></div>
        </div>
        
        <!-- OTP Display Box -->
        <div class="otp-display-box">
            <h3>Your OTP Code is:</h3>
            <div class="otp-code" id="displayOtp"><?= $generated_otp ?></div>
            <button type="button" class="copy-btn" id="copyBtn" onclick="copyOTP()">
                📋 Copy OTP
            </button>
            <div class="otp-timer" id="otpTimer">
                ⏳ Expires in: <span id="countdown">05:00</span>
            </div>
        </div>

        <div class="warning-note">
            ⚠️ <strong>Important:</strong> Enter the OTP shown above to verify your account. This OTP will expire in 5 minutes.
        </div>
        
        <form id="otpForm">
            <div class="form-group">
                <label for="otp">Enter 6-digit OTP</label>
                <input type="text" class="form-control" id="otp" name="otp" required maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000000">
            </div>
            
            <button type="submit" class="btn" id="verifyBtn">
                <span class="btn-text" id="btnText">Verify OTP & Create Account</span>
            </button>
        </form>
        
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loader"></div>
        </div>
    </div>

    <script>
        const generatedOTP = "<?= $generated_otp ?>";
        let timerInterval;
        let timeLeft = 300; // 5 minutes in seconds

        // Countdown Timer Function
        function startTimer() {
            timerInterval = setInterval(() => {
                timeLeft--;
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                document.getElementById('countdown').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Change color when less than 1 minute
                const timerElement = document.getElementById('otpTimer');
                if (timeLeft < 60) {
                    timerElement.classList.add('expired');
                }
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById('countdown').textContent = '00:00';
                    document.getElementById('otpTimer').innerHTML = '❌ OTP Expired!';
                    
                    // Disable form submission
                    document.getElementById('verifyBtn').disabled = true;
                    document.getElementById('verifyBtn').style.opacity = '0.5';
                    document.getElementById('verifyBtn').style.cursor = 'not-allowed';
                }
            }, 1000);
        }

        // Copy OTP to clipboard
        function copyOTP() {
            navigator.clipboard.writeText(generatedOTP).then(() => {
                const copyBtn = document.getElementById('copyBtn');
                const originalText = copyBtn.innerHTML;
                copyBtn.innerHTML = '✅ Copied!';
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                    copyBtn.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy OTP: ', err);
            });
        }

        // Auto-fill OTP when clicked on displayed OTP
        document.getElementById('displayOtp').addEventListener('click', function() {
            document.getElementById('otp').value = generatedOTP;
            document.getElementById('otp').focus();
        });

        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if OTP expired
            if (timeLeft <= 0) {
                document.getElementById('jsErrorMessage').textContent = '❌ OTP has expired! Please request a new one.';
                document.getElementById('jsErrorMessage').style.display = 'block';
                return;
            }
            
            const container = document.getElementById('otpContainer');
            const overlay = document.getElementById('loadingOverlay');
            const btn = document.getElementById('verifyBtn');
            const btnText = document.getElementById('btnText');
            const errorDiv = document.getElementById('jsErrorMessage');
            const successDiv = document.getElementById('jsSuccessMessage');
            const formData = new FormData(this);

            // Reset states
            container.classList.remove('success-border', 'error-border', 'animate__pulse');
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            // Show loading state
            overlay.classList.add('active');
            btn.classList.add('loading');
            btnText.textContent = 'Creating Account...';
            
            // Use Fetch API for AJAX submission
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                setTimeout(() => {
                    overlay.classList.remove('active');
                    btn.classList.remove('loading');

                    if (data.status === 'success') {
                        // Success visual feedback
                        container.classList.add('success-border');
                        btnText.textContent = 'Account Created!';
                        successDiv.textContent = '✅ Account created successfully! Redirecting...';
                        successDiv.style.display = 'block';
                        
                        // Clear timer
                        clearInterval(timerInterval);
                        
                        // Redirect after animation
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else {
                        // Error visual feedback
                        container.classList.add('error-border');
                        errorDiv.textContent = '❌ ' + data.message;
                        errorDiv.style.display = 'block';
                        btnText.textContent = 'Verify OTP & Create Account';
                        
                        // Remove error border after animation
                        setTimeout(() => {
                            container.classList.remove('error-border');
                        }, 1000);
                    }
                }, 800);
            })
            .catch(error => {
                overlay.classList.remove('active');
                btn.classList.remove('loading');
                btnText.textContent = 'Verify OTP & Create Account';
                errorDiv.textContent = '❌ Something went wrong. Please try again.';
                errorDiv.style.display = 'block';
            });
        });
        
        // Auto-focus OTP input
        document.getElementById('otp').focus();
        
        // Start the countdown timer
        startTimer();
        
        // Add floating elements dynamically
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