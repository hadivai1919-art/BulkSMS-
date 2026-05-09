<?php
header('Content-Type: application/json');

if (isset($_GET['receiver']) && isset($_GET['subject']) && isset($_GET['message']) && isset($_GET['from'])) {
    $receiver = $_GET['receiver'];
    $subject = $_GET['subject'];
    $message = $_GET['message'];
    $from = $_GET['from'];

    $headers = "From: $from\r\n"; 
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $mailSent = mail($receiver, $subject, $message, $headers);

    if ($mailSent) {
        echo json_encode([
            "status" => "success",
            "message" => "Email সফল ভাবে পাঠানো হয়েছে!",
            "Api Owner" => "@mhhacker1912",
            "Telegram Channel" => "@mhhadi015"
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Email সফল ভাবে পাঠানো হয়নি!",
            "Api Owner" => "@mhhacker1912",
            "Telegram Channel" => "@mhhadi015"
        ], JSON_PRETTY_PRINT);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "কোনো প্যারামিটার প্রদান করা হয়নি!receiver= &subject= &message= &from= এই প্যারামিটার ব্যবহার করুন!",
        "Api Owner" => "@mhhacker1912",
        "Telegram Channel" => "@mhhadi015"
    ], JSON_PRETTY_PRINT);
}
// Written By @mhhacker1912
?>