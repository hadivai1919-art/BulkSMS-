<?php
session_start();
session_unset(); // সব সেশন ভ্যারিয়েবল মুছে ফেলে
session_destroy(); // সেশন সম্পূর্ণভাবে ধ্বংস করে

// ইউজারকে লগইন পেজে রিডাইরেক্ট করে
header("Location: login.php");
exit();
?>