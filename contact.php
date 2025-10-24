<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'mealmatefoods1@gmail.com';
    $mail->Password = 'pzjpctvwmtoyibqr';
    
    // Use port 465 with SSL (not TLS)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    
    // SSL context options
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Connection settings
    $mail->Timeout = 30;
    $mail->SMTPKeepAlive = false;
    $mail->SMTPAutoTLS = false;
    
    // Verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        error_log("SMTP Debug level $level; message: $str");
    };

    // Recipients
    $mail->setFrom('mealmatefoods1@gmail.com', 'MealMate Contact Form');
    $mail->addAddress('mealmatefoods1@gmail.com', 'MealMate Admin');
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'New Contact Form Message from ' . $name;
    $mail->Body = '
        <html>
        <body>
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Message:</strong></p>
            <div style="background:#f9f9f9;padding:15px;border-radius:5px;border-left:4px solid #4CAF50;">
                ' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '
            </div>
        </body>
        </html>
    ';
    $mail->AltBody = "Name: $name\nEmail: $email\n\nMessage:\n$message";

    $mail->send();
    echo json_encode([
        'status' => 'success', 
        'message' => 'Message sent successfully! We will get back to you soon.'
    ]);
    
} catch (Exception $e) {
    error_log("PHPMailer Exception: " . $e->getMessage());
    error_log("PHPMailer Error Info: " . $mail->ErrorInfo);
    
    echo json_encode([
        'status' => 'error', 
        'message' => 'Unable to send message. Please try again later.'
    ]);
}
?>