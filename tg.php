<?php
// Telegram প্রোফাইলে রিডাইরেক্ট করার PHP স্ক্রিপ্ট
$telegram_url = "https://t.me/hadi_vai1";

// HTTP হেডার ব্যবহার করে রিডাইরেক্ট
header("Location: " . $telegram_url);
exit();
?>