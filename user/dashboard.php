<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base = '/medical-c2c-platform';
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: $base/auth/login.php");
    exit();
}
if (in_array($_SESSION['user_role'], ['admin', 'it', 'marketing', 'support'])) {
    header("Location: /medical-c2c-platform/admin/dashboard.php");
    exit();
}

try {
    $listingStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
    $listingStmt->execute([$userId]);
    $activeListings = $listingStmt->fetchColumn();

    $salesStmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders o
        INNER JOIN products p ON o.product_id = p.id
        WHERE p.seller_id = ?
    ");
    $salesStmt->execute([$userId]);
    $salesCount = $salesStmt->fetchColumn();

    $revenueStmt = $pdo->prepare("
        SELECT COALESCE(SUM(p.price), 0) FROM orders o
        INNER JOIN products p ON o.product_id = p.id
        WHERE p.seller_id = ? AND o.status = 'completed'
    ");
    $revenueStmt->execute([$userId]);
    $totalRevenue = $revenueStmt->fetchColumn();

    $prodStmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT 3");
    $prodStmt->execute([$userId]);
    $recentProducts = $prodStmt->fetchAll();
} catch (\Exception $e) {
    $activeListings = 0;
    $salesCount = 0;
    $totalRevenue = 0;
    $recentProducts = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
    <style>
        body {
            background: #f0fbfc;
            font-family: 'Poppins', sans-serif;
        }

        /* Metric Cards */
        .dash-card {
            background: white;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 2px 16px rgba(3, 104, 115, 0.06);
            height: 100%;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            background: #e4f7f9;
            color: #036873;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .metric-value {
            font-size: 1.7rem;
            font-weight: 800;
            color: #036873;
            margin: 0;
            line-height: 1.2;
        }

        .metric-label {
            font-size: 0.75rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 3px 0 0;
        }

        /* Section */
        .section-title {
            font-weight: 700;
            color: #036873;
            font-size: 1rem;
            margin-bottom: 14px;
        }

        /* Recent listing row */
        .listing-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .listing-row:last-child {
            border-bottom: none;
        }

        .listing-thumb {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            overflow: hidden;
            background: #eee;
            flex-shrink: 0;
        }

        .listing-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .listing-title {
            font-weight: 600;
            font-size: 0.88rem;
            color: #1a1a1a;
            margin: 0 0 2px;
        }

        .listing-date {
            font-size: 0.75rem;
            color: #aaa;
            margin: 0;
        }

        .listing-price {
            font-weight: 800;
            font-size: 0.95rem;
            color: #036873;
            white-space: nowrap;
        }

        /*action buttons */
        .quick-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border: 1px solid #e8f5f7;
            border-radius: 12px;
            background: #fafefe;
            color: #036873;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s;
        }

        .quick-action:hover {
            background: #e4f7f9;
            border-color: #b2e4ea;
            color: #036873;
        }

        .quick-action i {
            font-size: 1rem;
        }

        /* Add listing btn */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #036873;
            color: white;
            padding: 10px 22px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            transition: background 0.2s;
        }

        .btn-add:hover {
            background: #024f58;
            color: white;
        }

        /* Welcome banner */
        .welcome-banner {
            background: linear-gradient(135deg, #036873, #059fb0);
            border-radius: 16px;
            padding: 24px 28px;
            color: white;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        .welcome-banner h4 {
            font-weight: 800;
            margin: 0 0 4px;
            font-size: 1.25rem;
        }

        .welcome-banner p {
            margin: 0;
            font-size: 0.88rem;
            opacity: 0.85;
        }

        .btn-add-white {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: white;
            color: #036873;
            padding: 10px 22px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            transition: box-shadow 0.2s;
            flex-shrink: 0;
        }

        .btn-add-white:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            color: #036873;
        }
    </style>
</head>

<body Style="font-family: 'Poppins', DM SERIF;">

    <?php include '../includes/navbar-dashboard.php'; ?>

    <div class="container py-4" style="max-width:1050px;">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div>
                <h4>Welcome back, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>!</h4>
                <p>Here's what's happening with your MedMarket account today.</p>
            </div>
            <a href="<?php echo $base; ?>/products/add-product.php" class="btn-add-white">
                <i class="bi bi-plus-lg"></i> Create Listing
            </a>
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="dash-card d-flex align-items-center gap-3">
                    <div class="metric-icon"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <p class="metric-value"><?php echo $activeListings; ?></p>
                        <p class="metric-label">Active Listings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dash-card d-flex align-items-center gap-3">
                    <div class="metric-icon"><i class="bi bi-cart-check"></i></div>
                    <div>
                        <p class="metric-value"><?php echo $salesCount; ?></p>
                        <p class="metric-label">Successful Sales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dash-card d-flex align-items-center gap-3">
                    <div class="metric-icon"><i class="bi bi-currency-exchange"></i></div>
                    <div>
                        <p class="metric-value">R<?php echo number_format($totalRevenue); ?></p>
                        <p class="metric-label">Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>


        <div class="row g-4">
            <!-- Recent Listings -->
            <div class="col-lg-7">
                <div class="dash-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0">Recent Listings</h5>
                        <a href="<?php echo $base; ?>/user/my-products.php" class="small text-decoration-none" style="color:#036873;">View All →</a>
                    </div>

                    <?php if (empty($recentProducts)): ?>
                        <div style="text-align:center; padding:40px 20px; color:#aaa;">
                            <i class="bi bi-box-seam" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                            <p style="font-size:0.88rem;">No listings yet.</p>
                            <a href="<?php echo $base; ?>/products/add-product.php" class="btn-add">
                                <i class="bi bi-plus-lg"></i> Add Your First Listing
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentProducts as $prod): ?>
                            <div class="listing-row">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="listing-thumb">
                                        <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($prod['image_path'] ?? ''); ?>"
                                            onerror="this.src='<?php echo $base; ?>/assets/images/placeholder.jpg'">
                                    </div>
                                    <div>
                                        <p class="listing-title"><?php echo htmlspecialchars($prod['title']); ?></p>
                                        <p class="listing-date">Listed <?php echo date('d M Y', strtotime($prod['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="listing-price">R<?php echo number_format($prod['price'], 2); ?></span>
                                    <a href="<?php echo $base; ?>/products/edit-product.php?id=<?php echo $prod['id']; ?>"
                                        style="font-size:0.78rem; color:#036873; text-decoration:none; border:1px solid #c8edf0; padding:4px 10px; border-radius:20px;">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-5">
                <div class="dash-card">
                    <h5 class="section-title">Quick Actions</h5>
                    <div class="d-flex flex-column gap-2">
                        <a href="<?php echo $base; ?>/products/add-product.php" class="quick-action">
                            <span><i class="bi bi-plus-circle me-2"></i> Add New Listing</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?php echo $base; ?>/user/my-products.php" class="quick-action">
                            <span><i class="bi bi-box-seam me-2"></i> Manage My Listings</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?php echo $base; ?>/messages.php" class="quick-action">
                            <span><i class="bi bi-chat-text me-2"></i> Message Inbox</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?php echo $base; ?>/products/browse.php" class="quick-action">
                            <span><i class="bi bi-search me-2"></i> Browse Marketplace</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?php echo $base; ?>/user/edit-profile.php" class="quick-action">
                            <span><i class="bi bi-person me-2"></i> Edit My Profile</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?php echo $base; ?>/auth/logout.php" class="quick-action" style="color:#e74c3c; border-color:#ffdddd; background:#fff8f8;">
                            <span><i class="bi bi-box-arrow-right me-2"></i> Sign Out</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>

</html>