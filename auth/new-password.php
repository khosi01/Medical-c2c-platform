<?php
session_start();
require_once '../config/db.php';

function buildAlert(string $msg, string $type = 'danger'): string
{
    return "<div class='alert alert-$type text-center'>"
        . htmlspecialchars($msg)
        . "</div>";
}

$message = "";
$validToken = false;
$userId = null;
$token  = trim($_GET['token'] ?? '');
$userId = (int) ($_GET['id']  ?? 0);

if (empty($token) || $userId <= 0) {
    $message = buildAlert("Invalid or missing reset link.");
} else {

    $stmt = $pdo->prepare("
        SELECT id, reset_token, reset_expires
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = buildAlert("Invalid reset link.");
    } elseif (strtotime($user['reset_expires']) < time()) {
        $message = buildAlert("This reset link has expired. Please request a new one.");
    } elseif (!password_verify($token, $user['reset_token'])) {
        $message = buildAlert("Invalid reset link.");
    } else {
        // Token is valid
        $validToken = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {

    $newPassword     = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        $message = buildAlert("Password must be at least 8 characters.");
        // keep form visible
    } elseif ($newPassword !== $confirmPassword) {
        $message = buildAlert("Passwords do not match.");
    } else {

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);


        $pdo->prepare("
            UPDATE users
            SET password = ?, reset_token = NULL, reset_expires = NULL
            WHERE id = ?
        ")->execute([$hashed, $userId]);


        header("Location: login.php?msg=password_reset");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password | MedMarket</title>
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

            <p class="auth-eyebrow">Almost done</p>
            <h1 class="auth-title">New Password</h1>
            <p class="auth-sub">Choose a strong password for your account.</p>

            <?php if (!empty($message)) echo $message; ?>

            <?php if ($validToken): ?>
                <form method="POST" action="new-password.php?token=<?php echo htmlspecialchars($token); ?>&id=<?php echo $userId; ?>">

                    <div class="form-group">
                        <label class="form-label" for="password">New password</label>
                        <div class="input-wrap">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Minimum 8 characters" minlength="8" required autocomplete="new-password">
                            <button type="button" class="toggle-pass" onclick="togglePass(this)" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm password</label>
                        <div class="input-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                placeholder="Repeat your new password" minlength="8" required autocomplete="new-password">
                            <button type="button" class="toggle-pass" onclick="togglePass(this)" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- password strength bar -->
                    <div style="margin-bottom:18px;">
                        <div style="height:4px; border-radius:4px; background:#e0ecee; overflow:hidden;">
                            <div id="strength-bar" style="height:100%; width:0; border-radius:4px; transition:width 0.3s, background 0.3s;"></div>
                        </div>
                        <p id="strength-label" style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;"></p>
                    </div>

                    <button type="submit" class="btn-primary-auth">
                        Update Password <i class="bi bi-check-lg"></i>
                    </button>

                </form>

            <?php else: ?>
                <div class="auth-footer">
                    <a href="reset-password.php">Request a new reset link</a>
                </div>
            <?php endif; ?>

            <div class="auth-footer">
                <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
            </div>

        </div>


        <div class="auth-right">
            <img src="../assets/images/lock.jpg"
                onerror="this.style.display='none'"
                alt="Set new password">
            <div class="auth-right-caption">
                <h3>Secure your account</h3>
                <p>Use a unique password you don't use anywhere else.</p>
            </div>
        </div>

    </div>

    <script>
        function togglePass(btn) {
            const inp = btn.closest('.input-wrap').querySelector('input');
            const icon = btn.querySelector('i');
            inp.type = inp.type === 'password' ? 'text' : 'password';
            icon.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        }

        document.getElementById('password')?.addEventListener('input', function() {
            const val = this.value;
            const bar = document.getElementById('strength-bar');
            const lbl = document.getElementById('strength-label');
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            const levels = [{
                    w: '0%',
                    bg: 'transparent',
                    t: ''
                },
                {
                    w: '25%',
                    bg: '#e74c3c',
                    t: 'Weak'
                },
                {
                    w: '50%',
                    bg: '#e67e22',
                    t: 'Fair'
                },
                {
                    w: '75%',
                    bg: '#f1c40f',
                    t: 'Good'
                },
                {
                    w: '100%',
                    bg: '#036873',
                    t: 'Strong'
                },
            ];
            const l = levels[score];
            bar.style.width = l.w;
            bar.style.background = l.bg;
            lbl.textContent = l.t;
        });
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>