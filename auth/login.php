<?php
session_start();

require_once '../config/db.php';

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {

    $lines = file(
        $envFile,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

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

require '../vendor/PHPMailer-master/src/PHPMailer.php';
require '../vendor/PHPMailer-master/src/SMTP.php';
require '../vendor/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";

const DUMMY_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

function buildAlert(string $message): string
{
    return
        "<div class='alert alert-danger text-center'>"
        . htmlspecialchars($message)
        . "</div>";
}

function buildOtpEmail(
    string $name,
    string $otp
): string {

    return <<<TEXT
    Hello {$name},

Your MedMarket login verification code is:

{$otp}

This code expires in 10 minutes.

Do not share this code with anyone.

- MedMarket
TEXT;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || empty($password)
    ) {

        $error = buildAlert(
            "Invalid email or password."
        );
    } else {

        $stmt =
            $pdo->prepare(
                "SELECT * FROM users WHERE email = ?"
            );

        $stmt->execute([$email]);

        $user =
            $stmt->fetch();

        $hashToVerify =
            $user
            ? $user['password']
            : DUMMY_HASH;

        $passwordValid =
            password_verify(
                $password,
                $hashToVerify
            );

        if ($user && $passwordValid) {

            $otp =
                (string) random_int(100000, 999999);

            $otpHashed =
                password_hash(
                    $otp,
                    PASSWORD_BCRYPT
                );

            $otpExpiry =
                date(
                    'Y-m-d H:i:s',
                    time() + 600
                );

            $pdo->prepare("
                UPDATE users
                SET otp_code = ?,
                    otp_expires_at = ?
                WHERE id = ?
            ")->execute([
                $otpHashed,
                $otpExpiry,
                $user['id']
            ]);

            session_regenerate_id(true);

            $_SESSION['temp_user_id'] =
                $user['id'];

            $_SESSION['redirect_after_login'] =
                isset($_GET['redirect'])
                ? filter_var(
                    $_GET['redirect'],
                    FILTER_SANITIZE_URL
                )
                : '';

            $smtpUser = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');

            $smtpPass = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD');

            $mail =
                new PHPMailer(true);



            try {

                $mail->isSMTP();

                $mail->Host =
                    'smtp.gmail.com';

                $mail->SMTPAuth =
                    true;

                $mail->Username =
                    $smtpUser;

                $mail->Password =
                    $smtpPass;

                $mail->SMTPSecure =
                    PHPMailer::ENCRYPTION_STARTTLS;

                $mail->Port =
                    587;

                $mail->setFrom(
                    $smtpUser,
                    $_ENV['MAIL_FROM_NAME'] ?? 'MedMarket'
                );

                $mail->addAddress(
                    $user['email']
                );

                $mail->Subject =
                    'MedMarket - Login Verification';

                $mail->isHTML(false);

                $mail->Body =
                    buildOtpEmail(
                        $user['full_name'],
                        $otp
                    );


                $mail->send();

                header(
                    "Location: verify-otp.php"
                );

                exit();
            } catch (Exception $e) {


                unset(
                    $_SESSION['temp_user_id'],
                    $_SESSION['redirect_after_login']
                );

                $pdo->prepare("
                    UPDATE users
                    SET otp_code = NULL,
                        otp_expires_at = NULL
                    WHERE id = ?
                ")->execute([
                    $user['id']
                ]);

                error_log(
                    "PHPMailer error: "
                        . $e->getMessage()
                );

                $error =
                    buildAlert(
                        "Failed to send verification email."
                    );
            }
        } else {

            $error =
                buildAlert(
                    "Invalid email or password."
                );
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Login | MedMarket
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sign In | MedMarket</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/auth.css">
    </head>

<body>

    <div class="auth-wrap">

        <!--Left-->
        <div class="auth-left">
            <a href="../index.php" class="auth-brand">
                <img src="../assets/images/Logo.jpg" alt="MedMarket logo">
                <span>Med<em>Market</em></span>
            </a>

            <p class="auth-eyebrow">Welcome back</p>
            <h1 class="auth-title">Sign In</h1>
            <p class="auth-sub">Access your MedMarket account.</p>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'password_reset'): ?>
                <div class="alert alert-success">Password updated successfully. Please sign in.</div>
            <?php endif; ?>

            <?php if (!empty($error)) echo $error; ?>

            <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">

                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="you@example.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label class="form-label" for="password" style="margin-bottom:0;">Password</label>
                        <a href="reset-password.php" class="forgot-link">Forgot password?</a>
                    </div>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="toggle-pass" onclick="togglePass(this)" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary-auth">
                    Sign In <i class="bi bi-arrow-right"></i>
                </button>

            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Create one</a>
            </div>

        </div>

        <!--Right-->
        <div class="auth-right">
            <img src="../assets/images/accuray.jpg"
                onerror="this.style.display='none'"
                alt="Doctor using laptop">
            <div class="auth-right-caption">
                <h3>Your medical marketplace</h3>
                <p>Trusted by healthcare professionals across South Africa.</p>
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