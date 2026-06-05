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

$prodStmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$prodStmt->execute([$userId]);
$products = $prodStmt->fetchAll();
$listingCount = count($products);

try {
    $salesStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM orders o
        INNER JOIN products p ON o.product_id = p.id
        WHERE p.seller_id = ?
    ");

    $salesStmt->execute([$userId]);
    $salesCount = $salesStmt->fetchColumn();
    $purchaseStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM orders
        WHERE buyer_id = ?
    ");

    $purchaseStmt->execute([$userId]);
    $purchaseCount = $purchaseStmt->fetchColumn();

    $revenueStmt = $pdo->prepare("
    SELECT COALESCE(SUM(o.total_price), 0) FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE p.seller_id = ?
");

    $revenueStmt->execute([$userId]);
    $revenue = $revenueStmt->fetchColumn() ?? 0;
} catch (Exception $e) {

    $salesCount = 0;
    $purchaseCount = 0;
    $revenue = 0;
}

$activeTab = $_GET['tab'] ?? 'listings';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['full_name']); ?> | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
    <style>
        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(3, 104, 115, 0.08);
            margin-bottom: 20px;
        }

        .cover-photo {
            height: 180px;
            background: linear-gradient(135deg, #036873, #04a0af);
            position: relative;
            overflow: hidden;
        }

        .cover-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cover-upload-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.4);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.78rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
        }

        .cover-upload-btn:hover {
            background: rgba(0, 0, 0, 0.6);
            color: white;
        }

        .profile-info-row {
            padding: 0 28px 24px;
        }

        .profile-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
        }

        .profile-avatar-wrap {
            position: relative;
            display: inline-block;
            margin-top: -40px;
            margin-bottom: 12px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            background: #036873;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #036873;
            border: 2px solid white;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            text-decoration: none;
        }

        .profile-name {
            font-size: 1.25rem;
            font-weight: 800;
            color: #036873;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 6px;
        }

        .profession-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e4f7f9;
            color: #036873;
            border: 1px solid #b2e4ea;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .profile-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 0.8rem;
            color: #888;
            margin-top: 6px;
        }

        .profile-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }

        .btn-wishlist {
            padding: 8px 18px;
            border-radius: 50px;
            border: 1.5px solid #036873;
            background: white;
            color: #036873;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s;
        }

        .btn-wishlist:hover {
            background: #f0fbfc;
            color: #036873;
        }

        .btn-new-listing {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center;
            gap: 8px;
            width: auto !important;
            height: auto !important;
            padding: 10px 22px;
            border-radius: 50px;
            background: #036873;
            color: white;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            transition: background 0.2s;
        }

        .btn-new-listing:hover {
            background: #024f58;
            color: white;
        }

        .btn-new-listing i,
        .btn-wishlist i {
            font-size: 1rem;
            line-height: 1;
            display: inline-block;
        }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid #f0f0f0;
            margin-top: 16px;
        }

        .stat-item {
            text-align: center;
            padding: 18px 10px;
            border-right: 1px solid #f0f0f0;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #036873;
            margin: 0;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 4px 0 0;
        }

        /* Tabs */
        .profile-tabs {
            background: white;
            border-radius: 16px;
            display: flex;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(3, 104, 115, 0.08);
            margin-bottom: 20px;
        }

        .profile-tab {
            flex: 1;
            padding: 16px;
            text-align: center;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            color: #888;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .profile-tab:hover {
            color: #036873;
            background: #f8feff;
        }

        .profile-tab.active {
            color: #036873;
            font-weight: 700;
            border-bottom-color: #036873;
            background: #f8feff;
        }

        /* Section */
        .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 16px rgba(3, 104, 115, 0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .section-header h5 {
            font-weight: 800;
            color: #036873;
            margin: 0;
            font-size: 1.1rem;
        }

        .section-subtitle {
            font-size: 0.82rem;
            color: #aaa;
            margin-bottom: 20px;
        }

        .btn-add-listing {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #036873;
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
        }

        .btn-add-listing:hover {
            background: #024f58;
            color: white;
        }

        /* Product Grid */
        .my-products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .my-product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e8f5f7;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .my-product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(3, 104, 115, 0.1);
        }

        .my-product-img {
            height: 140px;
            overflow: hidden;
        }

        .my-product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .my-product-body {
            padding: 12px;
        }

        .my-product-title {
            font-weight: 600;
            font-size: 0.85rem;
            color: #1a1a1a;
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .my-product-price {
            font-weight: 800;
            font-size: 1rem;
            color: #036873;
            margin-bottom: 8px;
        }

        .my-product-actions {
            display: flex;
            gap: 6px;
        }

        .btn-edit-sm {
            flex: 1;
            padding: 5px;
            text-align: center;
            border: 1.5px solid #c8edf0;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #036873;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-edit-sm:hover {
            background: #036873;
            color: white;
            border-color: #036873;
        }

        .btn-delete-sm {
            padding: 5px 10px;
            border: 1.5px solid #ffdddd;
            border-radius: 8px;
            font-size: 0.75rem;
            color: #e74c3c;
            background: none;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-delete-sm:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }

        .empty-state-tab {
            text-align: center;
            padding: 50px 20px;
            color: #aaa;
        }

        .empty-state-tab i {
            font-size: 2.5rem;
            margin-bottom: 12px;
            display: block;
        }

        @media (max-width:768px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .my-products-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .dash-nav-links {
                display: none;
            }
        }

        @media (max-width:480px) {
            .my-products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body Style="font-family: 'Poppins', DM SERIF;">
    <?php include '../includes/navbar-dashboard.php'; ?>

    <div class=" container py-4" style="max-width:900px;">

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="cover-photo">
                <?php if (!empty($user['background_pic'])): ?>
                    <img src="<?php echo $base; ?>/uploads/covers/<?php echo htmlspecialchars($user['background_pic']); ?>" alt="Cover">
                <?php endif; ?>
                <a href="<?php echo $base; ?>/user/edit-profile.php" class="cover-upload-btn">
                    <i class="bi bi-camera"></i> Edit Cover
                </a>
            </div>

            <div class="profile-info-row">
                <div class="profile-top">
                    <div>
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar">
                                <?php if (!empty($user['profile_pic'])): ?>
                                    <img src="<?php echo $base; ?>/uploads/profiles/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo $base; ?>/user/edit-profile.php" class="avatar-edit-btn">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <div class="profession-badge">
                            <i class="bi bi-patch-check-fill"></i>
                            <?php echo htmlspecialchars($user['profession'] ?? 'Medical Professional'); ?>
                        </div>
                        <div class="profile-meta">
                            <?php if (!empty($user['location'])): ?>
                                <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($user['location']); ?></span>
                            <?php endif; ?>
                            <span><i class="bi bi-calendar3"></i> Member since <?php echo date('M Y', strtotime($user['member_since'] ?? 'now')); ?></span>
                            <span><i class="bi bi-star-fill" style="color:#f4c542;"></i> 5.0 rating</span>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <a href="#" class="btn-wishlist">Wishlist</a>
                        <a href="<?php echo $base; ?>/products/add-product.php" class="btn-new-listing">
                            <i class="bi bi-plus-lg"></i> New Listing
                        </a>
                    </div>
                </div>

                <div class="stats-row">
                    <div class="stat-item">
                        <p class="stat-value"><?php echo $listingCount; ?></p>
                        <p class="stat-label">Listings</p>
                    </div>
                    <div class="stat-item">
                        <p class="stat-value"><?php echo $salesCount; ?></p>
                        <p class="stat-label">Sales</p>
                    </div>
                    <div class="stat-item">
                        <p class="stat-value"><?php echo $purchaseCount; ?></p>
                        <p class="stat-label">Purchases</p>
                    </div>
                    <div class="stat-item">
                        <p class="stat-value">R<?php echo $revenue >= 1000 ? round($revenue / 1000) . 'K' : number_format($revenue); ?></p>
                        <p class="stat-label">Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="profile-tabs">
            <a href="?tab=listings" class="profile-tab <?php echo $activeTab === 'listings' ? 'active' : ''; ?>">Listings</a>
            <a href="?tab=purchases" class="profile-tab <?php echo $activeTab === 'purchases' ? 'active' : ''; ?>">Purchases</a>
            <a href="?tab=messages" class="profile-tab <?php echo $activeTab === 'messages' ? 'active' : ''; ?>">Messages</a>
            <a href="?tab=settings" class="profile-tab <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">Settings</a>
        </div>

        <!-- Tab Content -->
        <?php if ($activeTab === 'listings'): ?>
            <div class="section-card">
                <div class="section-header">
                    <h5>My Listings</h5>
                    <a href="<?php echo $base; ?>/products/add-product.php" class="btn-add-listing"><i class="bi bi-plus-lg"></i> Add Listing</a>
                </div>
                <p class="section-subtitle">Manage your products and equipment on MedMarket</p>
                <?php if (empty($products)): ?>
                    <div class="empty-state-tab">
                        <i class="bi bi-box-seam"></i>
                        <p>You haven't listed any products yet.</p>
                        <a href="<?php echo $base; ?>/products/add-product.php" class="btn-new-listing"><i class="bi bi-plus-lg"></i> Add Your First Listing</a>
                    </div>
                <?php else: ?>
                    <div class="my-products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="my-product-card">
                                <div class="my-product-img">
                                    <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>"
                                        onerror="this.src='<?php echo $base; ?>/assets/images/placeholder.jpg'"
                                        alt="<?php echo htmlspecialchars($product['title']); ?>">
                                </div>
                                <div class="my-product-body">
                                    <div class="my-product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                                    <div class="my-product-price">R<?php echo number_format($product['price'], 2); ?></div>
                                    <div class="my-product-actions">
                                        <a href="<?php echo $base; ?>/products/edit-product.php?id=<?php echo $product['id']; ?>" class="btn-edit-sm"><i class="bi bi-pencil"></i> Edit</a>
                                        <a href="<?php echo $base; ?>/products/view-product.php?id=<?php echo $product['id']; ?>" class="btn-edit-sm"><i class="bi bi-eye"></i> View</a>
                                        <form method="POST" action="<?php echo $base; ?>/products/delete-product.php" style="margin:0;" onsubmit="return confirm('Delete this listing?')">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn-delete-sm"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($activeTab === 'purchases'): ?>
            <div class="section-card">
                <h5 style="color:#036873;font-weight:800;margin-bottom:4px;">My Purchases</h5>
                <p class="section-subtitle">Items you have bought on MedMarket</p>
                <div class="empty-state-tab"><i class="bi bi-bag mb-3"></i>
                    <p>No purchases yet.</p><a href="<?php echo $base; ?>/products/browse.php" class="btn-new-listing"><i class="bi bi-search"></i> Browse Products</a>
                </div>
            </div>

        <?php elseif ($activeTab === 'messages'): ?>
            <div class="section-card">
                <h5 style="color:#036873;font-weight:800;margin-bottom:4px;">Messages</h5>
                <p class="section-subtitle">Your conversations with buyers and sellers</p>
                <div class="empty-state-tab"><i class="bi bi-chat-square-text mb-3"></i>
                    <p>No messages yet.</p><a href="<?php echo $base; ?>/messages.php" class="btn-new-listing"><i class="bi bi-chat"></i> Go to Messages</a>
                </div>
            </div>

        <?php elseif ($activeTab === 'settings'): ?>
            <div class="section-card">
                <h5 style="color:#036873;font-weight:800;margin-bottom:4px;">Settings</h5>
                <p class="section-subtitle">Manage your account preferences</p>
                <div class="d-flex flex-column gap-3" style="max-width:360px;">
                    <a href="<?php echo $base; ?>/user/edit-profile.php" class="btn-wishlist" style="text-align:center;"><i class="bi bi-pencil me-2"></i>Edit Profile</a>
                    <a href="#" class="btn-wishlist" style="text-align:center;"><i class="bi bi-lock me-2"></i>Change Password</a>
                    <a href="<?php echo $base; ?>/auth/logout.php" class="btn-wishlist" style="text-align:center;color:#e74c3c;border-color:#e74c3c;"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>