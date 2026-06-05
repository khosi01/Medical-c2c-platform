<?php
session_start();
require_once '../config/db.php';

$base = '/medical-c2c-platform';

$profileId = (int)($_GET['id'] ?? 0);
if (!$profileId) die("User not found.");

// Fetch user
$stmt = $pdo->prepare("SELECT id, full_name, profession, bio, profile_pic, created_at FROM users WHERE id = ? ");
$stmt->execute([$profileId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) die("User not found.");
$listings = $pdo->prepare("SELECT id, title, price, p_condition, image_path, created_at FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$listings->execute([$profileId]);
$products = $listings->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalListings  = count($products);
$memberSince    = date('F Y', strtotime($profile['created_at']));
$responseRate   = '95%';
$isOwnProfile   = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profileId;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['full_name']); ?> | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f4f7f8;
            font-family: 'Poppins', 'DM Serif Display', sans-serif;
            font-size: 14px;
        }

        .profile-hero {
            background: linear-gradient(135deg, #036873 0%, #024a52 100%);
            padding: 48px 0 80px;
            position: relative;
        }

        .profile-hero::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 40px;
            background: #f4f7f8;
            border-radius: 40px 40px 0 0;
        }

        .avatar-wrap {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.4);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: white;
            flex-shrink: 0;
        }

        .avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-family: 'DM Serif Display', serif;
            font-size: 1.6rem;
            color: white;
            margin-bottom: 4px;
        }

        .profile-profession {
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.88rem;
            font-weight: 500;
        }

        .stat-pill {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 6px 16px;
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .stat-pill i {
            font-size: 0.9rem;
        }

        .verified-tag {
            background: #e4f7f9;
            color: #036873;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid #e8f0f2;
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #5a8087;
            margin-bottom: 16px;
        }

        .bio-text {
            font-size: 0.9rem;
            color: #444;
            line-height: 1.75;
        }

        .bio-empty {
            color: #bbb;
            font-style: italic;
            font-size: 0.88rem;
        }

        .stat-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-box {
            flex: 1;
            min-width: 100px;
            background: #f4f7f8;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .stat-box .val {
            font-size: 1.4rem;
            font-weight: 800;
            color: #036873;
        }

        .stat-box .lbl {
            font-size: 0.72rem;
            color: #5a8087;
            font-weight: 500;
            margin-top: 2px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
        }

        .mini-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e8f0f2;
            text-decoration: none;
            transition: box-shadow 0.2s;
        }

        .mini-card:hover {
            box-shadow: 0 4px 16px rgba(3, 104, 115, 0.1);
        }

        .mini-card-img {
            height: 120px;
            background: #e8f6f7;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .mini-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mini-card-img i {
            font-size: 2.5rem;
            color: rgba(3, 104, 115, 0.2);
        }

        .mini-card-body {
            padding: 10px 12px;
        }

        .mini-card-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: #0d2e32;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mini-card-price {
            font-size: 0.85rem;
            font-weight: 800;
            color: #036873;
            margin-top: 2px;
        }

        .btn-message {
            background: #036873;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 11px 24px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-message:hover {
            background: #024a52;
            color: white;
        }

        .empty-listings {
            text-align: center;
            padding: 32px;
            color: #bbb;
        }

        .empty-listings i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>

<body>

    <?php include '../includes/navbar-browse.php'; ?>

    <!-- Hero -->
    <div class="profile-hero">
        <div class="container">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="avatar-wrap">
                    <?php if (!empty($profile['profile_pic'])): ?>
                        <img src="<?php echo $base; ?>/uploads/profiles/<?php echo htmlspecialchars($profile['profile_pic']); ?>">
                    <?php else: ?>
                        <?php echo strtoupper(substr($profile['full_name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="profile-name"><?php echo htmlspecialchars($profile['full_name']); ?></div>
                    <div class="profile-profession"><?php echo htmlspecialchars($profile['profession'] ?? 'Healthcare Professional'); ?></div>
                    <div class="mt-2"><span class="verified-tag"><i class="bi bi-patch-check-fill"></i> Verified Member</span></div>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="stat-pill"><i class="bi bi-box-seam"></i> <?php echo $totalListings; ?> listings</span>
                <span class="stat-pill"><i class="bi bi-calendar3"></i> Since <?php echo $memberSince; ?></span>
                <span class="stat-pill"><i class="bi bi-lightning-charge"></i> <?php echo $responseRate; ?> response rate</span>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row g-4">

            <!-- Stats -->
            <div class="col-lg-4">
                <div class="section-card">
                    <div class="section-title">Stats</div>
                    <div class="stat-row">
                        <div class="stat-box">
                            <div class="val"><?php echo $totalListings; ?></div>
                            <div class="lbl">Listings</div>
                        </div>
                        <div class="stat-box">
                            <div class="val"><?php echo $responseRate; ?></div>
                            <div class="lbl">Response</div>
                        </div>
                        <div class="stat-box">
                            <div class="val"><?php echo date('Y') - date('Y', strtotime($profile['created_at'])); ?>y</div>
                            <div class="lbl">Member</div>
                        </div>
                    </div>
                </div>

                <!-- Bio -->
                <div class="section-card">
                    <div class="section-title">About</div>
                    <?php if (!empty($profile['bio'])): ?>
                        <p class="bio-text"><?php echo nl2br(htmlspecialchars($profile['bio'])); ?></p>
                    <?php else: ?>
                        <p class="bio-empty">No bio added yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Action -->
                <?php if (!$isOwnProfile && isset($_SESSION['user_id'])): ?>
                    <div class="section-card">
                        <a href="<?php echo $base; ?>/messages.php?with=<?php echo $profile['id']; ?>" class="btn-message w-100 justify-content-center">
                            <i class="bi bi-chat-dots"></i> Message <?php echo htmlspecialchars(explode(' ', $profile['full_name'])[0]); ?>
                        </a>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right -->
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="section-title">Active Listings (<?php echo $totalListings; ?>)</div>
                    <?php if (empty($products)): ?>
                        <div class="empty-listings">
                            <i class="bi bi-box-seam"></i>
                            <p>No active listings yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="product-grid">
                            <?php foreach ($products as $p): ?>
                                <a href="<?php echo $base; ?>/products/view-product.php?id=<?php echo $p['id']; ?>" class="mini-card">
                                    <div class="mini-card-img">
                                        <?php if (!empty($p['image_path'])): ?>
                                            <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($p['image_path']); ?>"
                                                onerror="this.closest('.mini-card-img').innerHTML='<i class=\'bi bi-box-seam\'></i>'">
                                        <?php else: ?>
                                            <i class="bi bi-box-seam"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mini-card-body">
                                        <div class="mini-card-title"><?php echo htmlspecialchars($p['title']); ?></div>
                                        <div class="mini-card-price">R<?php echo number_format($p['price'], 2); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>