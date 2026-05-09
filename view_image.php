<?php
// view_image.php
if (isset($_GET['data'])) {
    $imageData = base64_decode(urldecode($_GET['data']));
    
    // কন্টেন্ট টাইপ সেট করা
    if (strpos($imageData, "\xFF\xD8\xFF") === 0) {
        header('Content-Type: image/jpeg');
    } elseif (strpos($imageData, "\x89\x50\x4E\x47") === 0) {
        header('Content-Type: image/png');
    } elseif (strpos($imageData, "GIF") === 0) {
        header('Content-Type: image/gif');
    } else {
        header('Content-Type: image/jpeg');
    }
    
    echo $imageData;
    exit;
}

// যদি সরাসরি URL দেওয়া হয়
if (isset($_GET['url'])) {
    $imageUrl = urldecode($_GET['url']);
    $imageData = file_get_contents($imageUrl);
    
    if ($imageData) {
        header('Content-Type: image/jpeg');
        echo $imageData;
        exit;
    }
}

// ডিফল্ট ইমেজ বা এরর
header('Content-Type: text/html');
echo '<h3>No image data provided</h3>';
echo '<p>Use: view_image.php?data=base64_encoded_image_data</p>';
?>