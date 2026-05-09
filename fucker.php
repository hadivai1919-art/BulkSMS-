<?php
// fixed_country_code.php
// This script uses a fixed country code (+880) for Bangladesh

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

// API endpoint for fixed country code
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['phone'])) {
    $phone = $_GET['phone'];
    $result = sendTelegramCodeFixed($phone);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// If no phone parameter provided
header('Content-Type: application/json');
echo json_encode([
    "success" => false,
    "error" => "Phone parameter is required. Usage: fixed_country_code.php?phone=01810886906"
]);
?>