
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coin Value Calculator | Professional</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --dark: #1f2937;
            --light: #f9fafb;
            --gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --gradient-hover: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 20px 50px -10px rgba(0, 0, 0, 0.2);
        }
        
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #f8fafc 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
        }
        
        .container {
            width: 100%;
            max-width: 520px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            animation: slideDown 0.6s ease-out;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            animation: pulse 2s infinite;
        }
        
        .logo-text {
            font-size: 28px;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .tagline {
            color: #6b7280;
            font-size: 16px;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .calculator-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.7s ease-out 0.2s both;
            transform: translateY(30px);
            opacity: 0;
        }
        
        .card-header {
            background: var(--gradient);
            color: white;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.3;
            animation: float 20s linear infinite;
        }
        
        .card-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .card-subtitle {
            font-size: 15px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .input-section {
            margin-bottom: 30px;
        }
        
        .input-label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
            font-size: 16px;
        }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .coin-icon-input {
            position: absolute;
            left: 18px;
            color: var(--primary);
            font-size: 20px;
            z-index: 2;
        }
        
        .coin-input {
            width: 100%;
            padding: 18px 20px 18px 55px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            transition: all 0.3s;
            background: white;
        }
        
        .coin-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .input-hint {
            margin-top: 8px;
            font-size: 14px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .rate-display {
            background: linear-gradient(135deg, #f0f9ff 0%, #f8fafc 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #e0f2fe;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: fadeIn 0.8s ease-out 0.4s both;
            opacity: 0;
        }
        
        .rate-label {
            font-size: 16px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .rate-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .result-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
            border: 1px solid #e0f2fe;
            animation: fadeIn 0.8s ease-out 0.6s both;
            opacity: 0;
        }
        
        .result-label {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .result-value {
            font-size: 42px;
            font-weight: 900;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-bottom: 5px;
            transition: all 0.5s;
        }
        
        .currency {
            font-size: 28px;
            color: var(--primary);
        }
        
        .result-coin {
            font-size: 16px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-telegram {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            background: var(--gradient);
            color: white;
            border: none;
            padding: 20px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            animation: fadeIn 0.8s ease-out 0.8s both;
            opacity: 0;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .btn-telegram:hover {
            background: var(--gradient-hover);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }
        
        .btn-telegram:active {
            transform: translateY(-1px);
        }
        
        .telegram-icon {
            font-size: 22px;
        }
        
        .footer {
            text-align: center;
            margin-top: 25px;
            color: #9ca3af;
            font-size: 14px;
            animation: fadeIn 1s ease-out 1s both;
            opacity: 0;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 25px;
            animation: fadeIn 0.8s ease-out 0.9s both;
            opacity: 0;
        }
        
        .feature {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid #f3f4f6;
            transition: transform 0.3s;
        }
        
        .feature:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .feature-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            100% {
                transform: translate(-20px, -20px) rotate(360deg);
            }
        }
        
        @keyframes coinFlip {
            0% {
                transform: rotateY(0);
            }
            100% {
                transform: rotateY(360deg);
            }
        }
        
        .coin-flip {
            animation: coinFlip 2s ease-out 0.5s;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .container {
                max-width: 100%;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .result-value {
                font-size: 36px;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="logo-text">CoinCalc Pro</div>
            </div>
            <p class="tagline">Professional coin valuation calculator with instant Telegram integration</p>
        </div>
        
        <div class="calculator-card">
            <div class="card-header">
                <h2 class="card-title">Coin Value Calculator</h2>
                <p class="card-subtitle">Calculate your coin value instantly at 0.10৳ per coin</p>
            </div>
            
            <div class="card-body">
                <form method="POST" id="calculatorForm">
                    <div class="input-section">
                        <label class="input-label">Number of Coins</label>
                        <div class="input-wrapper">
                            <div class="coin-icon-input">
                                <i class="fas fa-coins"></i>
                            </div>
                            <input 
                                type="number" 
                                class="coin-input" 
                                id="coinCount" 
                                name="coinCount" 
                                min="1" 
                                step="1" 
                                value="100" 
                                placeholder="Enter coin amount"
                                required
                            >
                        </div>
                        <div class="input-hint">
                            <i class="fas fa-info-circle"></i>
                            <span>Enter the number of coins you want to calculate</span>
                        </div>
                    </div>
                    
                                        
                    <div class="rate-display">
                        <div class="rate-label">Current Rate Per Coin</div>
                        <div class="rate-value">
                            <i class="fas fa-coins coin-flip"></i>
                            <span>0.10৳</span>
                        </div>
                    </div>
                    
                    <div class="result-section">
                        <div class="result-label">Total Value for 100 Coins</div>
                        <div class="result-value" id="totalValue">
                            <span class="currency">৳</span>
                            10.00                        </div>
                        <div class="result-coin">
                            <i class="fas fa-coins"></i>
                            <span>100 coins</span>
                        </div>
                    </div>
                    
                    <a 
                        href="https://t.me/XLAHR?text=I want to buy coin. The coin amount is 100.🪙 The coin price is 10৳.💵" 
                        target="_blank" 
                        class="btn-telegram"
                        id="telegramBtn"
                    >
                        <i class="fab fa-telegram telegram-icon"></i>
                        <span>Buy Now on Telegram</span>
                    </a>
                    
                    <div class="features">
                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="feature-text">Instant Calculation</div>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="feature-text">Secure Transaction</div>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <div class="feature-text">Fast Delivery</div>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="feature-text">24/7 Support</div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="footer">
            <p>© 2023 CoinCalc Pro. All calculations are in real-time. Rate: 1 Coin = 0.10৳</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const coinInput = document.getElementById('coinCount');
            const totalValue = document.getElementById('totalValue');
            const telegramBtn = document.getElementById('telegramBtn');
            
            // Auto-submit form on input change
            coinInput.addEventListener('input', function() {
                // Add a small delay to prevent too many requests
                clearTimeout(window.inputTimeout);
                window.inputTimeout = setTimeout(() => {
                    document.getElementById('calculatorForm').submit();
                }, 500);
                
                // Animate the value change
                totalValue.style.transform = 'scale(1.1)';
                totalValue.style.color = '#10b981';
                
                setTimeout(() => {
                    totalValue.style.transform = 'scale(1)';
                    totalValue.style.color = '';
                }, 300);
            });
            
            // Add focus effect to input
            coinInput.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            coinInput.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
            
            // Animate telegram button on hover
            telegramBtn.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.telegram-icon');
                icon.style.transform = 'rotate(15deg) scale(1.2)';
                icon.style.transition = 'transform 0.3s';
            });
            
            telegramBtn.addEventListener('mouseleave', function() {
                const icon = this.querySelector('.telegram-icon');
                icon.style.transform = 'rotate(0) scale(1)';
            });
            
            // Update telegram link with current values
            function updateTelegramLink() {
                const coinCount = coinInput.value;
                const coinValue = 0.10;
                const totalValue = (coinCount * coinValue).toFixed(2);
                
                const message = `I want to buy coin. The coin amount is ${coinCount}.🪙 The coin price is ${totalValue}৳.💵`;
                const encodedMessage = encodeURIComponent(message);
                
                telegramBtn.href = `https://t.me/XLAHR?text=${encodedMessage}`;
            }
            
            // Update telegram link when input changes
            coinInput.addEventListener('input', updateTelegramLink);
            
            // Initial update
            updateTelegramLink();
            
            // Add animation to result when page loads
            setTimeout(() => {
                totalValue.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    totalValue.style.transform = 'scale(1)';
                    totalValue.style.transition = 'transform 0.5s ease';
                }, 300);
            }, 1000);
        });
    </script>
</body>
</html>