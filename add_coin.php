<?php
// র্যান্ডম কালার জেনারেট করার ফাংশন
function getRandomColor() {
    $colors = ['#FF5733', '#33FF57', '#3357FF', '#F033FF', '#FF33A1', '#33FFF6', '#FFD833', '#33FF96'];
    return $colors[array_rand($colors)];
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>গেম সার্ভিস স্ট্যাটাস</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .message-container {
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 90%;
        }
        .main-message {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
            transition: color 0.5s ease;
        }
        .support-message {
            font-size: 24px;
            margin-top: 20px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <div class="main-message" id="color-changing-text">
            আপাতত আমাদের গেম সার্ভিস অফ আছে। কিছুক্ষণ পরে অন করে দেয়া হবে।
        </div>
        <div class="support-message">
            কোনো সমস্যা হলে সাপোর্ট এ কথা বলুন।
        </div>
    </div>

    <script>
        // DOM কন্টেন্ট লোড হওয়ার পর স্ক্রিপ্ট এক্সিকিউট হবে
        document.addEventListener('DOMContentLoaded', function() {
            const textElement = document.getElementById('color-changing-text');
            
            // প্রতি ৩ সেকেন্ডে রঙ পরিবর্তন করার ফাংশন
            setInterval(function() {
                // র্যান্ডম কালার জেনারেট করতে PHP ফাংশন ব্যবহার করা সম্ভব নয়, তাই JavaScript দিয়ে করছি
                const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16);
                textElement.style.color = randomColor;
            }, 3000);
        });
    </script>
</body>
</html>