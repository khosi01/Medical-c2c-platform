<?php
require_once 'admin-auth-check.php';
require_once '../config/db.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminRole = $_SESSION['user_role'] ?? 'admin';
$userId    = $_SESSION['user_id'];

// Fetch admin details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$admin = $stmt->fetch();

// Handle password change
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $admin['password'])) {
        $msg = "<div class='alert alert-danger'>Current password is incorrect.</div>";
    } elseif ($new !== $confirm) {
        $msg = "<div class='alert alert-danger'>New passwords do not match.</div>";
    } elseif (strlen($new) < 8) {
        $msg = "<div class='alert alert-danger'>Password must be at least 8 characters.</div>";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $userId]);
        $msg = "<div class='alert alert-success'>Password updated successfully.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile | MedMarket Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin-mobile.css">

    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f4f7f8;
        }

        .wrap {
            max-width: 600px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 32px;
            border: 1px solid #ddeaec;
        }

        .card h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.4rem;
            margin-bottom: 6px;
        }

        .card p {
            color: #5a8087;
            font-size: 0.88rem;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #ddeaec;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
        }

        input:focus {
            border-color: #036873;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #036873;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn:hover {
            background: #024a52;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 16px;
        }

        .alert-danger {
            background: #fdf0f0;
            color: #c0392b;
            border: 1px solid #f5c6c6;
        }

        .alert-success {
            background: #eaf6f2;
            color: #0f6e56;
            border: 1px solid #a8dece;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #036873;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f6f7;
            font-size: 0.88rem;
        }

        .info-row .label {
            color: #5a8087;
        }

        .info-row .value {
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <a href="dashboard.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>

        <div class="card" style="margin-bottom:20px;">
            <h2>My Profile</h2>
            <p>Your admin account details.</p>
            <div class="info-row"><span class="label">Full Name</span><span class="value"><?php echo htmlspecialchars($admin['full_name']); ?></span></div>
            <div class="info-row"><span class="label">Email</span><span class="value"><?php echo htmlspecialchars($admin['email']); ?></span></div>
            <div class="info-row"><span class="label">Role</span><span class="value" style="text-transform:capitalize;"><?php echo htmlspecialchars($admin['role']); ?></span></div>
            <div class="info-row" style="border:none;"><span class="label">Member Since</span><span class="value"><?php echo date('d F Y', strtotime($admin['created_at'])); ?></span></div>
        </div>

        <div class="card">
            <h2>Change Password</h2>
            <p>Update your admin account password.</p>
            <?php if ($msg) echo $msg; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" minlength="8" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" minlength="8" required>
                </div>
                <button type="submit" class="btn">Update Password</button>
            </form>
        </div>
    </div>
</body>

</html>