<?php
$number = isset($_GET['phn']) ? $_GET['phn'] : '';

if (empty($number)) {
    echo "দয়া করে ফোন নম্বর প্রদান করুন URL এ ?phn= দিয়ে।";
    exit;  // স্ক্রিপ্ট এখানেই থামিয়ে দাও
}

// এরপর +880 যোগ করার কাজ বা API কল করো
$url = "https://api.apex4u.com/api/auth/login";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "Content-Type: application/json",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = <<<DATA
{
  "phoneNumber": "$number"
}
DATA;

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

//for debug only!
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$resp = curl_exec($curl);
curl_close($curl);
var_dump($resp);

?>

