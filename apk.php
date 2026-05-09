<?php
// download_curl.php
function downloadApk() {
    $url = 'https://mhbulksms.mooo.com/sms.apk';
    $filename = 'sms_app.apk';
    
    // CURL ইনিশিয়ালাইজ
    $ch = curl_init($url);
    
    // CURL অপশন সেট
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL ভেরিফিকেশন বন্ধ (শুধু টেস্টিং এর জন্য)
    
    // রেসপন্স নাও
    $file_data = curl_exec($ch);
    
    // CURL এরর চেক
    if(curl_errno($ch)) {
        echo 'CURL Error: ' . curl_error($ch);
        curl_close($ch);
        return false;
    }
    
    // HTTP কোড চেক
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($http_code != 200) {
        echo "HTTP Error: $http_code";
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // হেডার সেট করো
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($file_data));
    header('Cache-Control: public, must-revalidate');
    header('Pragma: no-cache');
    
    // ফাইল আউটপুট
    echo $file_data;
    return true;
}

// ফাংশন কল
downloadApk();
exit();
?>