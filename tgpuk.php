<?php
// Script 1: Fixed Country Code (+880)
function sendTelegramCodeFixed($phone) {
    // Remove any non-digit characters from phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Remove leading zero if present
    if (substr($phone, 0, 1) === '0') {
        $phone = substr($phone, 1);
    }
    
    // Add Bangladesh country code
    $full_phone = '+880' . $phone;
    
    // Prepare the POST data
    $post_data = "phone=" . urlencode($full_phone);
    $content_length = strlen($post_data);
    
    // Set up cURL request
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://my.telegram.org/auth/send_password",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => [
            "Host: my.telegram.org",
            "content-length: " . $content_length,
            "sec-ch-ua-platform: \"Android\"",
            "x-requested-with: XMLHttpRequest",
            "user-agent: Mozilla/5.0 (Linux; Android 8.1.0; TECNO CA7 Build/O11019) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.7103.87 Mobile Safari/537.36",
            "accept: application/json, text/javascript, */*; q=0.01",
            "sec-ch-ua: \"Chromium\";v=\"136\", \"Android WebView\";v=\"136\", \"Not.A/Brand\";v=\"99\"",
            "content-type: application/x-www-form-urlencoded; charset=UTF-8",
            "sec-ch-ua-mobile: ?1",
            "origin: https://my.telegram.org",
            "sec-fetch-site: same-origin",
            "sec-fetch-mode: cors",
            "sec-fetch-dest: empty",
            "referer: https://my.telegram.org/auth",
            "accept-encoding: gzip, deflate, br, zstd",
            "accept-language: en-US,en;q=0.9,bn-BD;q=0.8,bn;q=0.7",
            "priority: u=1, i"
        ],
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        "success" => $http_code === 200,
        "http_code" => $http_code,
        "response" => json_decode($response, true),
        "phone_sent" => $full_phone,
        "error" => $error
    ];
}

// Script 2: Custom Country Code
function sendTelegramCodeCustom($phone, $country_code) {
    // Remove any non-digit characters from phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Remove leading zero if present
    if (substr($phone, 0, 1) === '0') {
        $phone = substr($phone, 1);
    }
    
    // Add country code
    $full_phone = $country_code . $phone;
    
    // Prepare the POST data
    $post_data = "phone=" . urlencode($full_phone);
    $content_length = strlen($post_data);
    
    // Set up cURL request
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://my.telegram.org/auth/send_password",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => [
            "Host: my.telegram.org",
            "content-length: " . $content_length,
            "sec-ch-ua-platform: \"Android\"",
            "x-requested-with: XMLHttpRequest",
            "user-agent: Mozilla/5.0 (Linux; Android 8.1.0; TECNO CA7 Build/O11019) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.7103.87 Mobile Safari/537.36",
            "accept: application/json, text/javascript, */*; q=0.01",
            "sec-ch-ua: \"Chromium\";v=\"136\", \"Android WebView\";v=\"136\", \"Not.A/Brand\";v=\"99\"",
            "content-type: application/x-www-form-urlencoded; charset=UTF-8",
            "sec-ch-ua-mobile: ?1",
            "origin: https://my.telegram.org",
            "sec-fetch-site: same-origin",
            "sec-fetch-mode: cors",
            "sec-fetch-dest: empty",
            "referer: https://my.telegram.org/auth",
            "accept-encoding: gzip, deflate, br, zstd",
            "accept-language: en-US,en;q=0.9,bn-BD;q=0.8,bn;q=0.7",
            "priority: u=1, i"
        ],
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        "success" => $http_code === 200,
        "http_code" => $http_code,
        "response" => json_decode($response, true),
        "phone_sent" => $full_phone,
        "error" => $error
    ];
}

// Handle form submission
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['phone'])) {
        $phone = $_POST['phone'];
        
        if (isset($_POST['country_code']) && !empty($_POST['country_code'])) {
            // Use custom country code
            $country_code = $_POST['country_code'];
            $result = sendTelegramCodeCustom($phone, $country_code);
        } else {
            // Use fixed country code
            $result = sendTelegramCodeFixed($phone);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Code Sender</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(90deg, #08aeea 0%, #2af598 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .form-container {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus {
            border-color: #08aeea;
            outline: none;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input {
            margin-right: 10px;
        }
        
        .country-code {
            display: none;
            margin-top: 10px;
        }
        
        button {
            background: linear-gradient(90deg, #08aeea 0%, #2af598 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .phone-example {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .instructions {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            color: #333;
        }
        
        .instructions h3 {
            margin-bottom: 10px;
            color: #08aeea;
        }
        
        .instructions ul {
            padding-left: 20px;
            margin-bottom: 10px;
        }
        
        .instructions li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Telegram Code Sender</h1>
            <p>Send verification code to your Telegram account</p>
        </div>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="Enter your phone number" required>
                    <div class="phone-example">Example: 01810886906 or 1810886906</div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="custom_country" name="custom_country">
                    <label for="custom_country">Use custom country code</label>
                </div>
                
                <div class="form-group country-code" id="country_code_group">
                    <label for="country_code">Country Code</label>
                    <input type="text" id="country_code" name="country_code" placeholder="+880">
                    <div class="phone-example">Example: +1 for USA, +91 for India, +44 for UK</div>
                </div>
                
                <button type="submit">Send Verification Code</button>
            </form>
            
            <?php if ($result): ?>
                <div class="result <?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <?php if ($result['success']): ?>
                        <p><strong>Success!</strong> Code sent to <?php echo htmlspecialchars($result['phone_sent']); ?></p>
                        <?php if (isset($result['response'])): ?>
                            <p>Response: <?php echo json_encode($result['response']); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><strong>Error!</strong> Failed to send code to <?php echo htmlspecialchars($result['phone_sent']); ?></p>
                        <p>HTTP Code: <?php echo $result['http_code']; ?></p>
                        <?php if ($result['error']): ?>
                            <p>Error: <?php echo htmlspecialchars($result['error']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="instructions">
                <h3>How to use:</h3>
                <ul>
                    <li>Enter your phone number without country code to use the default +880 (Bangladesh)</li>
                    <li>Check "Use custom country code" if you want to use a different country code</li>
                    <li>Click "Send Verification Code" to send the code to your Telegram account</li>
                </ul>
                <p><strong>Note:</strong> This is for demonstration purposes. The actual Telegram API might have restrictions.</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('custom_country').addEventListener('change', function() {
            const countryCodeGroup = document.getElementById('country_code_group');
            countryCodeGroup.style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html>