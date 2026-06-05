<?php
session_start();
require_once '../config/db.php';

require '../vendor/PHPMailer-master/src/PHPMailer.php';
require '../vendor/PHPMailer-master/src/SMTP.php';
require '../vendor/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function buildAlert(string $msg): string
{
    return "<div class='alert alert-danger text-center'>" . htmlspecialchars($msg) . "</div>";
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name  = trim($_POST['full_name']  ?? '');
    $email = trim($_POST['email']      ?? '');
    $prof  = trim($_POST['profession'] ?? '');
    $pass  = $_POST['password']        ?? '';

    // Basic validation
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($prof) || empty($pass)) {
        $msg = buildAlert("Please fill in all fields correctly.");
    } else {

        $hashedPass = password_hash($pass, PASSWORD_DEFAULT);

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $msg = buildAlert("Email already registered.");
        } else {

            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, password, profession)
                VALUES (?, ?, ?, ?)
            ");

            if ($stmt->execute([$name, $email, $hashedPass, $prof])) {

                $userId    = $pdo->lastInsertId();
                $otp       = (string) random_int(100000, 999999);
                $otpHashed = password_hash($otp, PASSWORD_BCRYPT);
                $otpExpiry = date('Y-m-d H:i:s', time() + 600);

                $pdo->prepare("
                    UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?
                ")->execute([$otpHashed, $otpExpiry, $userId]);

                session_regenerate_id(true);
                $_SESSION['temp_user_id']        = $userId;
                $_SESSION['redirect_after_login'] = '';
                $smtpUser = $_ENV['MAIL_USERNAME'];
                $smtpPass = $_ENV['MAIL_PASSWORD'];

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtpUser;
                    $mail->Password   = $smtpPass;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->setFrom($smtpUser, 'MedMarket');
                    $mail->addAddress($email);
                    $mail->Subject  = 'MedMarket - Verify Your Account';
                    $mail->isHTML(false);
                    $mail->Body     = "Hi $name,\n\nWelcome to MedMarket!\n\nYour verification code is: $otp\n\nThis code expires in 10 minutes.\n\nIf you did not register, please ignore this email.\n\n— MedMarket";
                    $mail->send();
                } catch (Exception $e) {
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
                    unset($_SESSION['temp_user_id']);
                    error_log("Register mailer error for $email: " . $e->getMessage());
                    $msg = buildAlert("Failed to send verification email. Please try again.");

                    goto end;
                }

                header("Location: verify-otp.php");
                exit();
            }
        }
    }
}
end:
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | MedMarket</title>
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

            <p class="auth-eyebrow">Join MedMarket</p>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-sub">For verified healthcare professionals only.</p>

            <?php if (!empty($msg)) echo $msg; ?>

            <form method="POST" action="register.php">

                <div class="form-group">
                    <label class="form-label" for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                        placeholder="Your name" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="name@hospital.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="profession">Profession</label>
                    <select id="profession" name="profession" class="form-select" required>
                        <option value="" disabled selected>Select your profession</option>
                        <option value="Doctor/Specialist">Doctor / Specialist</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Medical Student">Medical Student</option>
                        <option value="Other Healthcare Professional">Other Healthcare Professional</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="Minimum 8 characters" minlength="8" required autocomplete="new-password">
                        <button type="button" class="toggle-pass" onclick="togglePass(this)" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary-auth">
                    Create Account <i class="bi bi-arrow-right"></i>
                </button>

            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign in</a>
            </div>

        </div>

        <div class="auth-right">
            <img src="../assets/images/register-illustration.png"
                onerror="this.style.display='none'"
                alt="Medical professional">
            <div class="auth-right-caption">
                <h3>Built for healthcare</h3>
                <p>Buy and sell trusted medical equipment and resources.</p>
            </div>
        </div>

    </div>

    <script>
        function togglePass(btn) {
            const inp = btn.closest('.input-wrap').querySelector('input');
            const icon = btn.querySelector('i');
            if (inp.type === 'password') {
                inp.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                inp.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>