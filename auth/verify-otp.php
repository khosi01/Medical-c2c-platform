<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enteredOtp = trim($_POST['otp'] ?? '');
    $userId     = $_SESSION['temp_user_id'] ?? null;

    if (!$userId) {
        header("Location: login.php?error=session_expired");
        exit();
    }

    if (empty($enteredOtp)) {
        $error = "No code received. Please try again.";
    } else {

        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE id = ? 
            AND otp_expires_at > NOW()
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();



        if ($user && password_verify($enteredOtp, $user['otp_code'])) {

            $pdo->prepare("
    UPDATE users 
    SET 
        otp_code = NULL,
        otp_expires_at = NULL,
        is_verified = 1
    WHERE id = ?
")->execute([$userId]);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['profile_pic'] = $user['profile_pic'];

            unset($_SESSION['temp_user_id'], $_SESSION['redirect_after_login']);

            switch ($user['role']) {
                case 'admin':
                case 'it':
                case 'marketing':
                case 'support':
                    header("Location: ../admin/dashboard.php");
                    break;
                default:
                    $redirect = $_SESSION['redirect_after_login'] ?? '';
                    if (!empty($redirect) && strpos($redirect, '/medical-c2c-platform/') === 0) {
                        header("Location: " . $redirect);
                    } else {
                        header("Location: ../index.php");
                    }
            }
            exit();
        } else {
            $check = $pdo->prepare("SELECT otp_expires_at FROM users WHERE id = ?");
            $check->execute([$userId]);
            $row = $check->fetch();

            if ($row && $row['otp_expires_at'] < date('Y-m-d H:i:s')) {
                $error = "Your code has expired. Please request a new one.";
            } else {
                $error = "Invalid code. Please try again.";
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
    <title>Verify Identity | MedMarket</title>
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

            <p class="auth-eyebrow">Security check</p>
            <h1 class="auth-title">Verify Identity</h1>
            <p class="auth-sub">Enter the 6-digit code sent to your email address.</p>

            <?php if (isset($_GET['resent'])): ?>
                <div class="alert alert-success">A new code has been sent to your email.</div>
            <?php endif; ?>

            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="POST" action="verify-otp.php" id="otp-form">
                <input type="hidden" name="otp" id="final-otp">

                <div class="otp-row">
                    <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric" autocomplete="one-time-code">
                    <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
                    <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
                    <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
                    <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
                    <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
                </div>

                <button type="submit" class="btn-primary-auth">
                    Verify Account <i class="bi bi-shield-check"></i>
                </button>
            </form>

            <div class="auth-footer" style="margin-top:20px;">
                Didn't receive a code?
                <a id="resend-link" href="resend-otp.php" style="pointer-events:none; opacity:0.4;">
                    Resend in <span id="timer">02:00</span>
                </a>
            </div>

        </div>


        <div class="auth-right">
            <img src="../assets/images/otp-illustration.png"
                onerror="this.style.display='none'"
                alt="Security verification">
            <div class="auth-right-caption">
                <h3>Keeping you safe</h3>
                <p>Two-step verification protects your MedMarket account.</p>
            </div>
        </div>

    </div>

    <script>
        const fields = document.querySelectorAll('.otp-field');
        fields.forEach((field, i) => {
            field.addEventListener('input', () => {
                if (field.value.length === 1 && i < fields.length - 1) fields[i + 1].focus();
            });
            field.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !field.value && i > 0) fields[i - 1].focus();
            });
        });

        document.getElementById('otp-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const code = [...fields].map(f => f.value).join('');

            if (code.length < 6) {
                alert('Please enter all 6 digits.');
                return;
            }

            document.getElementById('final-otp').value = code;
            this.submit();
        });

        (function() {
            const link = document.getElementById('resend-link');
            const display = document.getElementById('timer');
            let secs = 120;
            const t = setInterval(() => {
                secs--;
                const m = String(Math.floor(secs / 60)).padStart(2, '0');
                const s = String(secs % 60).padStart(2, '0');
                display.textContent = m + ':' + s;
                if (secs <= 0) {
                    clearInterval(t);
                    display.textContent = '';
                    link.textContent = 'Resend code';
                    link.style.pointerEvents = 'auto';
                    link.style.opacity = '1';
                }
            }, 1000);
        })();
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>