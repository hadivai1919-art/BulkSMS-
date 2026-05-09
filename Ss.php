<?php
header('Content-Type: application/json');

// 1. Check if required parameters are present
if (empty($_GET['msg']) || empty($_GET['number'])) {
    echo json_encode([
        'code' => 400,
        'status' => false,
        'message' => 'Phone number or message parameter missing!'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 2. Login process
$loginUrl = 'https://bacollege.biddalay.net/Account/Login';
$username = 'admin';
$password = 'welcome';

// Get login page
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Extract CSRF token
$csrf_token = '';
if (preg_match('/__RequestVerificationToken.*?value="([^"]+)"/s', $body, $matches)) {
    $csrf_token = $matches[1];
}

// Extract cookies
$cookies = [];
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
foreach ($matches[1] as $cookie) {
    $parts = explode('=', $cookie, 2);
    if (count($parts) == 2) {
        $cookies[trim($parts[0])] = $parts[1];
    }
}

// Prepare login POST data
$postData = [
    '__RequestVerificationToken' => $csrf_token,
    'UserName' => $username,
    'Password' => $password,
    'RememberMe' => 'false'
];

// Set up cURL for login POST
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

// Set cookies
if (!empty($cookies)) {
    $cookieString = '';
    foreach ($cookies as $name => $value) {
        $cookieString .= $name . '=' . $value . '; ';
    }
    curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookieString, '; '));
}

$loginResponse = curl_exec($ch);
$loginHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$loginHeaders = substr($loginResponse, 0, $loginHeaderSize);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Extract cookies after login
$loginCookies = [];
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $loginHeaders, $matches);
foreach ($matches[1] as $cookie) {
    $parts = explode('=', $cookie, 2);
    if (count($parts) == 2) {
        $loginCookies[trim($parts[0])] = $parts[1];
    }
}

curl_close($ch);

$aspNetCoreCookies = $loginCookies['.AspNetCore.Cookies'] ?? '';
$antiforgeryCookie = $cookies['.AspNetCore.Antiforgery.4Rhv8qpOwXc'] ?? '';

// Check if login was successful by checking if we got the auth cookie
if (!empty($aspNetCoreCookies)) {
    // Login successful! Now send SMS
    
    // 3. Prepare SMS data
    $smsText = $_GET['msg'];
    $contactNumber = $_GET['number'];

    // Format phone number
    if (strlen($contactNumber) == 11 && substr($contactNumber, 0, 2) === '01') {
        $contactNumber = '88' . $contactNumber;
    } elseif (strlen($contactNumber) == 10 && substr($contactNumber, 0, 1) === '1') {
        $contactNumber = '880' . $contactNumber;
    }

    // 4. First, get the SMS page to get a fresh token
    $smsPageUrl = 'https://bacollege.biddalay.net/sms/sendsms';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $smsPageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIE, '.AspNetCore.Cookies=' . $aspNetCoreCookies . '; .AspNetCore.Antiforgery.4Rhv8qpOwXc=' . $antiforgeryCookie);

    $smsPageResponse = curl_exec($ch);

    // Extract token from SMS page
    $smsCsrfToken = '';
    if (preg_match('/__RequestVerificationToken.*?value="([^"]+)"/s', $smsPageResponse, $matches)) {
        $smsCsrfToken = $matches[1];
    }

    curl_close($ch);

    // 5. Send SMS
    $smsSendUrl = 'https://bacollege.biddalay.net/Sms/Send';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $smsSendUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    // Based on your original code, use the correct parameter names
    $postData = [
        '__RequestVerificationToken' => $smsCsrfToken,
        'vmSms[SMSText]' => $smsText,
        'vmSms[TemplateId]' => '',
        'vmSms[ContactList][0][ContactNumber]' => $contactNumber
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

    // Set headers and cookies
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Accept-Language: en-US,en;q=0.9',
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With: XMLHttpRequest',
        'Origin: https://bacollege.biddalay.net',
        'Referer: https://bacollege.biddalay.net/sms/sendsms'
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_COOKIE, '.AspNetCore.Cookies=' . $aspNetCoreCookies . '; .AspNetCore.Antiforgery.4Rhv8qpOwXc=' . $antiforgeryCookie);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $smsResponse = curl_exec($ch);
    $smsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // 6. Return response
    if ($smsHttpCode === 200) {
        $responseData = json_decode($smsResponse, true);
        
        if (isset($responseData['success']) && $responseData['success'] === true) {
            echo json_encode([
                'code' => 200,
                'status' => true,
                'message' => 'SMS sent successfully!',
                'debug' => [
                    'login_cookies_received' => true,
                    'sms_token_found' => !empty($smsCsrfToken),
                    'sms_http_code' => $smsHttpCode
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode([
                'code' => 500,
                'status' => false,
                'message' => 'SMS sending failed. Response: ' . $smsResponse,
                'debug' => [
                    'login_cookies_received' => true,
                    'sms_response' => $smsResponse,
                    'sms_http_code' => $smsHttpCode
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        echo json_encode([
            'code' => $smsHttpCode,
            'status' => false,
            'message' => 'SMS sending failed. HTTP Code: ' . $smsHttpCode,
            'error' => $error,
            'debug' => [
                'login_cookies_received' => true,
                'antiforgery_cookie' => substr($antiforgeryCookie, 0, 20) . '...',
                'aspnet_cookie' => substr($aspNetCoreCookies, 0, 20) . '...'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
} else {
    echo json_encode([
        'code' => 401,
        'status' => false,
        'message' => 'Login failed! Could not retrieve authentication cookie.',
        'debug' => [
            'cookies_received' => $loginCookies,
            'aspnet_cookie_empty' => empty($aspNetCoreCookies),
            'http_code' => $httpCode
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>