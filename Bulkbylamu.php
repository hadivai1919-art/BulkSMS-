<?php

// ১. প্যারামিটারগুলো রিসিভ করা (URL থেকে)
$number = isset($_GET['number']) ? $_GET['number'] : '';
$message = isset($_GET['msg']) ? $_GET['msg'] : '';

// ২. ইনপুট চেক করা
if (empty($number) || empty($message)) {
    echo json_encode(["status" => "error", "message" => "Number and Message are required!"]);
    exit;
}

// ৩. মেসেজটি URL এর উপযোগী করে তৈরি করা (Space বা বিশেষ ক্যারেক্টারের জন্য)
$encoded_message = urlencode($message);

// ৪. আপনার নির্দিষ্ট API URL টি তৈরি করা
$api_key = "SMS_6607250676_2efb2e20e89966e7937e6b54ca2a32d1";
$sender_id = "ANNOOR+INST";

$api_url = "https://darktube.serv00.net/api?api_key=$api_key&sender_id=$sender_id&number=$number&message=$encoded_message";

// ৫. cURL ব্যবহার করে API কল করা
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

// ৬. এরর চেক করা এবং আউটপুট দেখানো
if(curl_errno($ch)){
    echo 'Error:' . curl_error($ch);
} else {
    echo "Response from API: " . $response;
}

curl_close($ch);

?>