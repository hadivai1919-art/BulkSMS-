<?php

function sendPhoneRequest($number) {
    // Clean the phone number
    $number = trim($number);
    
    if (empty($number)) {
        return [
            'error' => true,
            'message' => 'Phone number is required',
            'response' => null
        ];
    }
    
    // The API endpoint
    $url = "https://dashboard.awajdigital.com/profile/phone";
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set the URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br, zstd');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    
    // Use POST method
    curl_setopt($ch, CURLOPT_POST, true);
    
    // Prepare the JSON data
    $postData = json_encode(['phoneNumber' => $number]);
    
    // Set POST data
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    // Headers from your original cURL command
    $headers = [
        'Host: dashboard.awajdigital.com',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData),
        'sec-ch-ua-platform: "Android"',
        'x-xsrf-token: e:kX4rCblZWaRcU4IAExDdVjjczLGghhRUdkStfxkQAsBG54rPktLxdAoGBZTsaONKAa-YWQsC60CAPvma6Jczn3GeI1wxeHlWT6FPt5dM5q4.NHlHZ1kzMjJjT1ZLVms1cg.iaAEHDUMgEeyZxZ8u0dcQyrhLV0e2kVU02_9SOfurlo',
        'user-agent: Mozilla/5.0 (Linux; Android 14; Infinix X6532 Build/UP1A.231005.007) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.7499.192 Mobile Safari/537.36',
        'sec-ch-ua: "Android WebView";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
        'sec-ch-ua-mobile: ?1',
        'accept: */*',
        'origin: https://dashboard.awajdigital.com',
        'x-requested-with: mark.via.gp',
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://dashboard.awajdigital.com/profile',
        'accept-language: en-US,en;q=0.9',
        'priority: u=1, i',
        'Cookie: _fbp=fb.1.1769175701745.767186902137705796; adonis-session=s%3AeyJtZXNzYWdlIjoicG9taThsajA1Zjc3bWV3MjYyZ3ViZ3BqIiwicHVycG9zZSI6ImFkb25pcy1zZXNzaW9uIn0.MDy64_FUPYdV2I-NhB-tdBgu9Z0sAorsqHAAJfQcu0I; XSRF-TOKEN=e%3AkX4rCblZWaRcU4IAExDdVjjczLGghhRUdkStfxkQAsBG54rPktLxdAoGBZTsaONKAa-YWQsC60CAPvma6Jczn3GeI1wxeHlWT6FPt5dM5q4.NHlHZ1kzMjJjT1ZLVms1cg.iaAEHDUMgEeyZxZ8u0dcQyrhLV0e2kVU02_9SOfurlo; pomi8lj05f77mew262gubgpj=e%3ALaa0iuKYub2YkYj5bJ6x7qhK_RLY7xl41ad4ODBrCfQhPTT6VjfIUEA87r6pLXC_pa30INi4LdP-n0MF28qCCYkZoKYXvUcNzmeV1VvSKtdo9y3ZsoQtDVuVmItpg2AwJ7XR8T8zmhOywCcQF3SEKA.S1NLRjk4NzJRTk5xd3FhTg.bkeNKiL7u1DhpJAIqvecWCsRiXwPBFQMLhPrs3l7lD8'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute and get response
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // Get more details if needed
    $curlInfo = curl_getinfo($ch);
    
    curl_close($ch);
    
    // Return the complete result
    return [
        'phone_number' => $number,
        'http_code' => $httpCode,
        'error' => $error ?: false,
        'response' => $response,
        'response_headers' => $curlInfo,
        'request_data' => $postData
    ];
}

// Handle API request
if (isset($_GET['number']) || isset($_POST['number'])) {
    $number = isset($_GET['number']) ? $_GET['number'] : $_POST['number'];
    
    $result = sendPhoneRequest($number);
    
    // Display result
    header('Content-Type: application/json');
    
    // If you want to see raw HTML response, keep as is
    // If you want cleaner output, you can extract JSON from response
    $responseText = $result['response'];
    
    // Try to extract JSON if response is HTML with embedded JSON
    if (strpos($responseText, '<!DOCTYPE') === 0) {
        // Try to find JSON in the data-page attribute
        if (preg_match('/data-page="([^"]+)"/', $responseText, $matches)) {
            $jsonString = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            $result['extracted_json'] = json_decode($jsonString, true);
        }
    } else {
        // Try to parse as JSON directly
        $jsonResponse = json_decode($responseText, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $result['parsed_response'] = $jsonResponse;
        }
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} else {
    // Show usage
    echo "=== Phone Number API (POST Method) ===\n\n";
    echo "Usage:\n";
    echo "GET:  https://mhbulksms.mooo.com/call.php?number=0193896578\n";
    echo "POST: Send 'number' parameter via POST\n\n";
    echo "Example response structure:\n";
    echo json_encode([
        'phone_number' => '01938986578',
        'http_code' => 200,
        'error' => false,
        'response' => 'Server response here...',
        'parsed_response' => ['status' => 'success']
    ], JSON_PRETTY_PRINT);
}

?>