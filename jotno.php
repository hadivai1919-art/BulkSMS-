<?php
$number = isset($_GET['phn']) ? $_GET['phn'] : '';

if (empty($number)) {
    echo "দয়া করে ফোন নম্বর প্রদান করুন URL এ ?phn= দিয়ে।";
    exit;  // স্ক্রিপ্ট এখানেই থামিয়ে দাও
}

// এরপর +880 যোগ করার কাজ বা API কল করো
$url = "https://gw.jotno.net/auth/login/token";

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
  "userType": "CONSUMER",
  "username": "$number",
  "apiKey": "03AFcWeA6GlFc0TGFr0pGHLLQOfULtEKD2xuUX3ljZlq2PpgwzwIUXsS-9ZyC10Ea8MFM7LAKezBGS-sdSJqo7OLSV6CC_LPrcI9FDMkpgY_L2DhnPNbPdGf1S8C7K9WWPs9-vxLZ879z7KLcuxq_9WWpql7L_TzDKYoaniSHTf08i73lx316_GYAIhRQCZw7s7b1GqCMFlXK-6DCotVTrfv-82OLIiQxUVbouylfAygmzDFnRWMWOQIlT5_VUxhbx0lMhhKZKwP4cCI3YTwGRdBWo9oFpxS-iO345oB28mZSPwN9Tre3lOS5ceJX39xURE7J1-qA6Jrqm7oHpLM1MUA7GOGRSHPHLrMCGzb_w6xWeP7pypcc5KzmMOXwLFx6WKU6Zoi0jy1aA9Ro24wpcmw7CkAfRRdV54yxTHJLIRha0f10y3MXOGA7OkIqxKqpPgFANZb8phJ9QdN2p3Oi9zrgnmjR65JS-HWMQDxlmdDPfk8QxwK-nunMyKh88oQQu8N_1AC5QpMRruuJ9Ky35oNgPOXYxq3SQDl8TSEFNN_qi2Gy72_--CNOiRNslJZVvflyYjRMPKMGpSsdkMIQPXMqzqzsbCDPCEfjDSGf6IEkwfByYh4ZxVPvN9ODGVRKuxObfwGn1dsV0P_8ABodxhCqIlF4rPfOcAb7y3n8cJLH0beRQmS3AZJ-Lpt7z26oIdWCBeHf1SZ4bt0ndt5gbc9s14qOuuMTmGUcYvCXDvmJDnYbnrPOZb8kKM9Olmk_kPtLGgplVE6x0kCzgAYM9WBZsrZVjQjmmIORqV3t4r6DgmG_gd6VxcTuf8DKm64EvuhrGv2S6tSo94DTyEMTeFwS7Wq77d7cKr8C6npLkYVAEEkKpLj4TBpE4VW89JCTHeUVy8RBtDsGrO7FighdotdzZ30zhlvKvYNYdk2on1PpyNR39vqH-_5FxZKYlKIL6hQ91ehnQzcIqivLq-K10rowiPpurbQUyRMdJbLnsn602R-LihZkA8d08pGX5dbquWnJkVvXZ2_ymkkVMwGmouVIWcuWzSXPuwdXXfVoKs0a3CaBB1XHCo2QYU1VSjHcUMZ0rO8LDjjGrY16TYgRNnUg5pBbffN5UmvAragkSnuvK4z-nbnQiLt2vPtJv6Fz1tHGwJFwDH4N3PDHcCrfPmMI_jY8-MxvjF1RwlqrUP8JVPqq8cckWK_hFzMiMfMDNeZoSt656wQj7p-xHHs5fQuRJHUNQzV5BKzdB1kcmMVRSMXfKqpfvVQyPqcnt_7kqTrF9zXTujjJupACNSeLNueppm5-cvs9H9aYi_55tlh-62kIRmNnUDQUXD7fNVZ8Dtn9IqZVvQBX9orgyI5GZdTmgN14ZGxTr9xKlghAo65YVK7TXbbgcG2KkgFlXavPooZa1r3Z2NCgcEVNoFB9PIOQHILt8l2Hhz2zaMPZc3JeorkD_Vligtpaf7NVillE_uMum9jjT6zTMtyO4v6nzUM89H3tHda10TzojbtBXmB3VpVbdupSYc2BjLo5urICmBW6fDDzYi6UlKqaemaxFb4k1EkTLCpWxtWQtm8YzpKG2Hz_3dRS3J6tNFYHT4fQEl-wbPtAy1mlH44V4VFMWbGJ12_lpze4wfBaSsGeCVG8T5t8BtzS9knfWQrGYHxrbjry-J7GDWbnwfJ-1jHYi6hsk5pAkON_7SAj6sST56HPQYSL9zy3o1OVuVsPLC5AvqgJg8yN2z3evx6o64fLcfMF4P3A2EuAgCJi96PzvW4om4HZXL0ulGsZ6pLrM1TWfpbsfb92UDG30O1bbfRcFfVx5gpzDjjiyTWzXRaYOIeFefs1lHnib0NDXAs7UMaCE1DXmHeymI2aYDuZkciABuoERVxgksoaBYVm0IDyMDW8JeWOsjDtXS4CNf_o2fIOl3A2_1w9KL8DmN1CK4rN0k-KF9xdT8Pt2nGXcb6MbS-bvtAjRJLWjqTGSCVyxVgP0n9AoP0Uv9vQLOKvKAd4U5vAvTe50C8NbCWELXgVW_ZQGd72nfCUGMOEW3R6Rs6tcwzG-aE2Ht8HYihFSTPkfPMJBPukLOl-hJf27_p-nWqwKxnEwpxRRI1StsyD8quVClypiqv7LLKl-1rNwlcg385u_lKxI2gGmnEEDANDTFVGParTfhDS8yBypKk_IPfEGS94gh7Vck_DnrlQsoIo1h0Es1QfcSewkguobGEOmgcPdG-k0ydRrtUq--FV3uwShNjHKyo8PRgDReiN_JLquRj0ro1tesd4Sle-vTSYp5-UbmrBvxlBEq-taeKV8DVT4tznHQ_Ww3fsoFejobbadHZBnv1vztfj-pEn_2KiSh2CRhyrWislFiyqUzUD-9Pbd_F7ElY_xIfkMqjGYSPVmFGJhfciRCvLEN-EXYbEjx1EgQ-T4xdtJaxe0MOg20EELYGZ7DLCBtZ1RUsiBapqd7AlC1FtanK8u3Q"
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

