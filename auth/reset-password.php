<?php
session_start();
require_once '../config/db.php';

require '../vendor/PHPMailer-master/src/PHPMailer.php';
require '../vendor/PHPMailer-master/src/SMTP.php';
require '../vendor/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function buildAlert(string $msg, string $type = 'danger'): string
{
    return "<div class='alert alert-$type text-center'>"
        . htmlspecialchars($msg)
        . "</div>";
}

$message = "";

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);

        $_ENV[trim($key)] = trim($value);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = buildAlert("Please enter a valid email address.");
    } else {

        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user) {

            $message = buildAlert("No account found with that email.");
        } else {

            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_BCRYPT);

            $expires = date('Y-m-d H:i:s', time() + 3600);

            $pdo->prepare("
                UPDATE users
                SET reset_token = ?, reset_expires = ?
                WHERE id = ?
            ")->execute([$hashedToken, $expires, $user['id']]);

            $resetLink = "http://localhost/medical-c2c-platform/auth/new-password.php?token=$token&id=" . $user['id'];

            $smtpUser = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
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

                $mail->setFrom($smtpUser, $_ENV['MAIL_FROM_NAME']);
                $mail->addAddress($user['email']);

                $mail->Subject = 'MedMarket Password Reset';
                $mail->isHTML(false);

                $mail->Body =
                    "Hello {$user['full_name']},

We received a request to reset your MedMarket password.

Click the link below to reset your password:

$resetLink

This link expires in 1 hour.

If you did not request this reset, please ignore this email.

- MedMarket";

                $mail->send();

                $message = buildAlert(
                    "Password reset link sent successfully.",
                    "success"
                );
            } catch (Exception $e) {

                error_log("Reset password mail error: " . $e->getMessage());

                $message = buildAlert(
                    "Failed to send reset email. Please try again."
                );
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

    <div class="auth-wrap">
        <div class="auth-left">
            <a href="../index.php" class="auth-brand">
                <img src="../assets/images/Logo.jpg" alt="MedMarket logo">
                <span>Med<em>Market</em></span>
            </a>

            <p class="auth-eyebrow">Account recovery</p>
            <h1 class="auth-title">Reset Password</h1>
            <p class="auth-sub">Enter your email and we'll send you a reset link.</p>

            <?php if (!empty($message)) echo $message; ?>

            <form method="POST" action="reset-password.php">

                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="you@example.com" required autocomplete="email">
                </div>

                <button type="submit" class="btn-primary-auth">
                    Send Reset Link <i class="bi bi-send"></i>
                </button>

            </form>

            <div class="auth-footer">
                <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
            </div>

        </div>

        <div class="auth-right">
            <img src="../assets/images/reset-illustration.png"
                onerror="this.style.display='none'"
                alt="Password recovery">
            <div class="auth-right-caption">
                <h3>Happens to everyone</h3>
                <p>We'll get you back into your account in no time.</p>
            </div>
        </div>

    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>