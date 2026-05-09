<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['number']) || empty($_GET['number'])) {
    echo json_encode([
        'error' => 'Phone number parameter is required',
        'message' => 'Use: ?number=8801791234227',
        'Api Owner' => '@hadi_vai1'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$number = $_GET['number'];
$responses = [];

// 1. প্রথম API (Facebook ID)
$facebookResponse = getFacebookId($number);
$responses[] = [
    'type' => 'facebook_id',
    'data' => $facebookResponse
];

// 2. দ্বিতীয় API (ছবি/ইমেজ)
$imageResponse = getImage($number);
$responses[] = [
    'type' => 'image',
    'data' => $imageResponse
];

// 3. তৃতীয় API (নাম)
$nameResponse = getName($number);
$responses[] = [
    'type' => 'name',
    'data' => $nameResponse
];

// ফাইনাল রেসপন্স
$finalResponse = [
    'status' => 'success',
    'number' => $number,
    'data' => [
        'facebook_id' => $facebookResponse['facebook_id'] ?? null,
        'image_url' => $imageResponse['image_url'] ?? null,
        'name' => $nameResponse['name'] ?? null
    ],
    'details' => $responses,
    'Api Owner' => '@hadi_vai1'
];

echo json_encode($finalResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// ফাংশন: Facebook ID পাওয়ার জন্য
function getFacebookId($phone) {
    $url = "https://api.eyecon-app.com/app/pic?cli={$phone}&is_callerid=true&size=big&type=0&src=MenifaFragment&cancelfresh=0&cv=vc_727_vn_4.2026.01.04.1543_a";
    
    $headers = [
        'e-auth-v: e1',
        'e-auth: 9b1bca8f-c093-4355-b414-9f9641ab7582',
        'e-auth-c: 45',
        'e-auth-k: PgdtSBeR0MumR7fO',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36',
        'Host: api.eyecon-app.com',
        'Connection: Keep-Alive',
        'Accept-Encoding: gzip'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Facebook ID এক্সট্র্যাক্ট করার লজিক
    $facebookId = extractFacebookId($response);
    
    return [
        'status_code' => $httpCode,
        'status' => $httpCode == 200 ? 'success' : 'error',
        'facebook_id' => $facebookId,
        'message' => $facebookId ? 'Facebook ID found' : 'Facebook ID not found',
        'api_url' => $url,
        'raw_response_length' => strlen($response)
    ];
}

// ফাংশন: Facebook ID এক্সট্র্যাক্ট
function extractFacebookId($response) {
    // Method 1: JSON থেকে Facebook ID খোঁজা
    if (strpos($response, '{') !== false) {
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($json['fb_id'])) {
                return $json['fb_id'];
            }
            if (isset($json['facebook_id'])) {
                return $json['facebook_id'];
            }
            if (isset($json['id'])) {
                return $json['id'];
            }
        }
    }
    
    // Method 2: রেসপন্স থেকে Facebook প্যাটার্ন খোঁজা
    // Facebook ID সাধারণত সংখ্যা হয় (15-20 ডিজিট)
    preg_match('/\b\d{15,20}\b/', $response, $matches);
    if (!empty($matches)) {
        return $matches[0];
    }
    
    // Method 3: URL থেকে Facebook ID
    preg_match('/facebook\.com\/(?:profile\.php\?id=)?([a-zA-Z0-9\.]+)/', $response, $matches);
    if (!empty($matches[1])) {
        return $matches[1];
    }
    
    return null;
}

// ফাংশন: ছবি পাওয়ার জন্য
function getImage($phone) {
    // দ্বিতীয় API (অন্য নম্বর দিয়ে - 8801763131746)
    $url = "https://api.eyecon-app.com/app/pic?cli=8801763131746&is_callerid=true&size=big&type=0&src=MenifaFragment&cancelfresh=0&cv=vc_727_vn_4.2026.01.04.1543_a";
    
    $headers = [
        'e-auth-v: e1',
        'e-auth: 9b1bca8f-c093-4355-b414-9f9641ab7582',
        'e-auth-c: 45',
        'e-auth-k: PgdtSBeR0MumR7fO',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36',
        'Host: api.eyecon-app.com',
        'Connection: Keep-Alive',
        'Accept-Encoding: gzip'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true); // হেডার সহ রেসপন্স পেতে
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    // হেডার এবং বডি আলাদা করা
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // ইমেজ URL তৈরি
    $imageUrl = null;
    $imageData = null;
    
    if ($httpCode == 200 && !empty($body)) {
        // যদি সরাসরি ইমেজ ডাটা আসে
        if (strlen($body) > 100 && (strpos($headers, 'image/') !== false || isImageData($body))) {
            $imageData = base64_encode($body);
            // আপনার ডোমেনে ইমেজ ভিউয়ার URL
            $imageUrl = "https://yourdomain.com/view_image.php?data=" . urlencode($imageData);
        } 
        // যদি JSON রেসপন্স আসে যাতে image_url থাকে
        elseif (strpos($body, '{') !== false) {
            $json = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($json['image_url'])) {
                    $imageUrl = $json['image_url'];
                } elseif (isset($json['photo_url'])) {
                    $imageUrl = $json['photo_url'];
                } elseif (isset($json['url'])) {
                    $imageUrl = $json['url'];
                }
            }
        }
    }
    
    return [
        'status_code' => $httpCode,
        'status' => $httpCode == 200 && $imageUrl ? 'success' : 'error',
        'image_url' => $imageUrl,
        'direct_api_url' => $url,
        'has_image_data' => !empty($imageData),
        'message' => $imageUrl ? 'Image retrieved successfully' : 'Image not found',
        'response_type' => getResponseType($headers)
    ];
}

// ফাংশন: রেসপন্স টাইপ চেক করার জন্য
function getResponseType($headers) {
    if (strpos($headers, 'image/jpeg') !== false) return 'image/jpeg';
    if (strpos($headers, 'image/png') !== false) return 'image/png';
    if (strpos($headers, 'image/') !== false) return 'image/*';
    if (strpos($headers, 'application/json') !== false) return 'json';
    return 'unknown';
}

// ফাংশন: ইমেজ ডাটা চেক করার জন্য
function isImageData($data) {
    // ইমেজ সিগনেচার চেক
    $signatures = [
        "\xFF\xD8\xFF", // JPEG
        "\x89\x50\x4E\x47", // PNG
        "GIF", // GIF
        "BM", // BMP
    ];
    
    foreach ($signatures as $sig) {
        if (strpos($data, $sig) === 0) {
            return true;
        }
    }
    return false;
}

// ফাংশন: নাম পাওয়ার জন্য
function getName($phone) {
    $url = "https://api.eyecon-app.com/app/getnames.jsp?cli={$phone}&lang=en&is_callerid=true&is_ic=true&cv=vc_727_vn_4.2026.01.04.1543_a&requestApi=URLconnection&source=MenifaFragment";
    
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36',
        'accept: application/json',
        'e-auth-v: e1',
        'e-auth: 33f97979-8b6b-4b88-a6f3-6ee700dd23ec',
        'e-auth-c: 31',
        'e-auth-k: PgdtSBeR0MumR7fO',
        'accept-charset: UTF-8',
        'content-type: application/x-www-form-urlencoded; charset=utf-8',
        'Host: api.eyecon-app.com',
        'Connection: Keep-Alive',
        'Accept-Encoding: gzip'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $name = null;
    $details = [];
    
    if ($httpCode == 200 && !empty($response)) {
        $json = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && $json) {
            // নাম এক্সট্র্যাক্ট
            if (isset($json['name'])) {
                $name = $json['name'];
            } elseif (isset($json['full_name'])) {
                $name = $json['full_name'];
            } elseif (isset($json['display_name'])) {
                $name = $json['display_name'];
            }
            
            // অন্যান্য তথ্য
            $details = [
                'gender' => $json['gender'] ?? null,
                'type' => $json['type'] ?? null,
                'location' => $json['location'] ?? null,
                'verified' => $json['verified'] ?? null
            ];
        }
    }
    
    return [
        'status_code' => $httpCode,
        'status' => $httpCode == 200 && $name ? 'success' : 'error',
        'name' => $name,
        'details' => $details,
        'api_url' => $url,
        'message' => $name ? 'Name found successfully' : 'Name not found'
    ];
}
?>