<?php
// File: custom_captcha.php
// আপনি এই ফাইলটিতে আপনার নিজের ইমেজ জেনারেশন কোড লিখতে পারেন
// register.php থেকে ইমেজ দেখানোর জন্য: <img src="custom_captcha.php">

session_start();

// Security headers
header('Content-type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Create image
$width = 320;
$height = 140;
$image = imagecreatetruecolor($width, $height);

// Background color
$bg_color = imagecolorallocate($image, 240, 240, 240);
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Generate random text (6-8 characters)
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_text = '';
$length = rand(6, 8);

for ($i = 0; $i < $length; $i++) {
    $captcha_text .= $chars[rand(0, strlen($chars) - 1)];
}

// Save to session (with encryption and IP binding)
$_SESSION['captcha_code'] = md5($captcha_text . 'YOUR_SECRET_KEY_' . $_SERVER['REMOTE_ADDR']);
$_SESSION['captcha_time'] = time();

// Add noise (dots)
for ($i = 0; $i < 100; $i++) {
    $dot_color = imagecolorallocate($image, rand(150, 220), rand(150, 220), rand(150, 220));
    imagesetpixel($image, rand(0, $width), rand(0, $height), $dot_color);
}

// Add noise (lines)
for ($i = 0; $i < 5; $i++) {
    $line_color = imagecolorallocate($image, rand(150, 200), rand(150, 200), rand(150, 200));
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Draw text with distortion
for ($i = 0; $i < $length; $i++) {
    $char = $captcha_text[$i];
    $angle = rand(-15, 15);
    $x = 20 + ($i * 28) + rand(-3, 3);
    $y = 45 + rand(-5, 5);
    
    // Random color for each character
    $text_color = imagecolorallocate($image, rand(30, 120), rand(30, 120), rand(30, 120));
    
    // Draw character
    imagestring($image, 5, $x, $y, $char, $text_color);
}

// Apply wave distortion
imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR, 0.5);

// Output image
imagepng($image);
imagedestroy($image);
?>