<?php

$base = '/medical-c2c-platform';
$currentPage = basename($_SERVER['PHP_SELF']);
$userName = $_SESSION['user_name'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

$profilePic = null;
if ($userId && isset($pdo)) {
    $picStmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $picStmt->execute([$userId]);
    $profilePic = $picStmt->fetchColumn();
}

// Active state helper
$activePages = [
    'dashboard' => ['dashboard.php'],
    'myaccount' => ['profile.php', 'edit-profile.php', 'my-products.php'],
];
$isDashboard = in_array($currentPage, $activePages['dashboard']);
$isMyAccount = in_array($currentPage, $activePages['myaccount']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>../assets/css/style.css">
</head>

<body>

    <nav style="background:#036873; position:sticky; top:0; z-index:100; box-shadow:0 2px 12px rgba(0,0,0,0.15);">
        <div style="display:flex; align-items:center; gap:8px; padding:0 30px; max-width:1400px; margin:0 auto; height:65px;">

            <!-- Logo -->
            <a href="<?php echo $base; ?>/index.php"
                style="display:flex; align-items:center; gap:10px; text-decoration:none; flex-shrink:0; margin-right:16px;">
                <img src="<?php echo $base; ?>/assets/images/Logo.jpg" alt="MedMarket" width="36" class="rounded" style="border:1px solid rgba(3,104,115,0.5); border-radius:20%;">
                <span style="font-family:'DM Serif Display',serif; color:white; font-size:1.15rem; font-style:italic;">
                    Med<em>Market</em>
                </span>
            </a>

            <!-- Nav Links -->
            <div style="display:flex; align-items:center; gap:4px; flex:1;">

                <a href="<?php echo $base; ?>/products/browse.php"
                    style="padding:8px 16px; border-radius:8px; text-decoration:none; font-family:'Poppins',sans-serif; font-size:0.9rem; font-weight:500;
                      color:<?php echo $currentPage === 'browse.php' ? 'white' : 'rgba(255,255,255,0.75)'; ?>;
                      background:<?php echo $currentPage === 'browse.php' ? 'rgba(255,255,255,0.15)' : 'transparent'; ?>;">
                    Browse
                </a>

                <a href="<?php echo $base; ?>/products/add-product.php"
                    style="padding:8px 16px; border-radius:8px; text-decoration:none; font-family:'Poppins',sans-serif; font-size:0.9rem; font-weight:500;
                      color:<?php echo $currentPage === 'add-product.php' ? 'white' : 'rgba(255,255,255,0.75)'; ?>;
                      background:<?php echo $currentPage === 'add-product.php' ? 'rgba(255,255,255,0.15)' : 'transparent'; ?>;">
                    Sell
                </a>

                <!-- Dashboard -->
                <a href="<?php echo $base; ?>/user/dashboard.php"
                    style="padding:8px 16px; border-radius:8px; text-decoration:none; font-family:'Poppins',sans-serif; font-size:0.9rem; font-weight:500;
                      color:<?php echo $isDashboard ? 'white' : 'rgba(255,255,255,0.75)'; ?>;
                      background:<?php echo $isDashboard ? 'rgba(255,255,255,0.15)' : 'transparent'; ?>;">
                    Dashboard
                </a>

                <a href="<?php echo $base; ?>/user/profile.php"
                    style="padding:8px 16px; border-radius:8px; text-decoration:none; font-family:'Poppins',sans-serif; font-size:0.9rem; font-weight:500;
                      color:<?php echo $isMyAccount ? 'white' : 'rgba(255,255,255,0.75)'; ?>;
                      background:<?php echo $isMyAccount ? 'rgba(255,255,255,0.15)' : 'transparent'; ?>;">
                    My Account
                </a>

                <a href="#"
                    style="padding:8px 16px; border-radius:8px; text-decoration:none; font-family:'Poppins',sans-serif; font-size:0.9rem; font-weight:500; color:rgba(255,255,255,0.75);">
                    Help
                </a>
            </div>


            <div style="display:flex; align-items:center; gap:16px; flex-shrink:0;">
                <a href="#" style="color:rgba(255,255,255,0.85); text-decoration:none; font-size:1.3rem;">
                    <i class="bi bi-bell"></i>
                </a>
                <a href="<?php echo $base; ?>/user/profile.php"
                    style="width:40px; height:40px; border-radius:50%; border:2px solid rgba(255,255,255,0.5); overflow:hidden;
                      background:rgba(255,255,255,0.25); display:flex; align-items:center; justify-content:center;
                      color:white; font-weight:700; font-size:1rem; text-decoration:none; flex-shrink:0;">
                    <?php if ($profilePic): ?>
                        <img src="<?php echo $base; ?>/uploads/profiles/<?php echo htmlspecialchars($profilePic); ?>"
                            style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

</body>

</html>