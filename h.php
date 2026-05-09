<?php

header('Content-Type: application/json');

$url = "https://crbbb.com/api/webapi/GetNoaverageEmerdList";

$headers = [
    "User-Agent: Mozilla/5.0 (Linux; Android 14; 23053RN02A Build/UP1A.231005.007) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.6834.163 Mobile Safari/537.36",
    "Accept: application/json, text/plain, */*",
    "Accept-Encoding: identity", 
    "Content-Type: application/json;charset=UTF-8",
    "sec-ch-ua-platform: \"Android\"",
    "sec-ch-ua: \"Not A(Brand\";v=\"8\", \"Chromium\";v=\"132\", \"Android WebView\";v=\"132\"",
    "sec-ch-ua-mobile: ?1",
    "ar-origin: https://hgzy.org",
    "origin: https://hgzy.org",
    "x-requested-with: com.xbrowser.play",
    "sec-fetch-site: cross-site",
    "sec-fetch-mode: cors",
    "sec-fetch-dest: empty",
    "referer: https://hgzy.org/",
    "accept-language: en-GB,en-US;q=0.9,en;q=0.8",
    "priority: u=1, i"
];

$data = [
    "pageSize" => 10,
    "pageNo" => 1,
    "typeId" => 30,
    "language" => 0,
    "random" => "3812890336064d088dc8b178f1658269",
    "signature" => "7181744E2C81E8B168074CCDAB5ABB16",
    "timestamp" => 1739027785
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
// Written By @Owner_Of_DCS
?>