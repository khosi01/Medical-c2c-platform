<?php
session_start();
require_once '../config/db.php';

require '../vendor/PHPMailer-master/src/PHPMailer.php';
require '../vendor/PHPMailer-master/src/SMTP.php';
require '../vendor/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

if (empty($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['temp_user_id'];

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

// Generate and store new OTP
$otp       = (string) random_int(100000, 999999);
$otpHashed = password_hash($otp, PASSWORD_BCRYPT);
$otpExpiry = date('Y-m-d H:i:s', time() + 600);

$pdo->prepare("
    UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?
")->execute([$otpHashed, $otpExpiry, $userId]);

$smtpUser = $_ENV['MAIL_USERNAME']     ?? getenv('MAIL_USERNAME');
$smtpPass = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD');

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($smtpUser, $_ENV['MAIL_FROM_NAME'] ?? 'MedMarket');
    $mail->addAddress($user['email']);
    $mail->Subject = 'MedMarket - New Verification Code';
    $mail->isHTML(false);
    $mail->Body = "
Hello {$user['full_name']},

Your new MedMarket verification code is:

{$otp}

This code expires in 10 minutes. Do not share it with anyone.

If you did not request this, please secure your account immediately.
    ";

    $mail->send();

    // Redirect back to verify page with success message
    header("Location: verify-otp.php?resent=1");
    exit();
} catch (Exception $e) {
    error_log("Resend OTP error for user {$userId}: " . $e->getMessage());
    header("Location: verify-otp.php?error=resend_failed");
    exit();
}
