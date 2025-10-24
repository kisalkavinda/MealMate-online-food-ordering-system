<?php
echo "Testing Gmail SMTP Connection...<br><br>";

$connected = @fsockopen("smtp.gmail.com", 587, $errno, $errstr, 10);
if ($connected) {
    echo "✅ SUCCESS: Can connect to Gmail SMTP on port 587!<br>";
    fclose($connected);
} else {
    echo "❌ FAILED: Cannot connect to Gmail SMTP on port 587<br>";
    echo "Error: $errstr ($errno)<br><br>";
}

// Test port 465 as well
$connected2 = @fsockopen("smtp.gmail.com", 465, $errno, $errstr, 10);
if ($connected2) {
    echo "✅ SUCCESS: Can connect to Gmail SMTP on port 465!<br>";
    fclose($connected2);
} else {
    echo "❌ FAILED: Cannot connect to Gmail SMTP on port 465<br>";
    echo "Error: $errstr ($errno)<br><br>";
}

// Test if we can resolve the hostname
$ip = gethostbyname("smtp.gmail.com");
if ($ip !== "smtp.gmail.com") {
    echo "✅ DNS Resolution: smtp.gmail.com resolves to $ip<br>";
} else {
    echo "❌ DNS Resolution: Cannot resolve smtp.gmail.com<br>";
}

echo "<br><strong>If you see SUCCESS messages above, PHPMailer should work!</strong>";
?>