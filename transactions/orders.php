<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base   = '/medical-c2c-platform';
$userId = $_SESSION['user_id'];

// Fetch user's orders
$stmt = $pdo->prepare("
    SELECT o.id, o.status, o.order_date, o.delivery_address, o.total_price,
           p.title AS product_title, p.image_path, p.price AS product_price,
           u.full_name AS seller_name
    FROM orders o
    JOIN products p ON p.id = o.product_id
    JOIN users    u ON u.id = p.seller_id
    WHERE o.buyer_id = ?
    ORDER BY o.order_date DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Stats
$totalOrders     = count($orders);
$pendingOrders   = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
$deliveredOrders = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f4f7f8;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .page-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.8rem;
            color: #0d2e32;
            margin-bottom: 4px;
        }

        .page-sub {
            color: #5a8087;
            font-size: 0.88rem;
            margin-bottom: 28px;
        }

        .stat-row {
            display: flex;
            gap: 14px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border: 1px solid #e8f0f2;
            border-radius: 12px;
            padding: 16px 22px;
            flex: 1;
            min-width: 120px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .stat-val {
            font-size: 1.4rem;
            font-weight: 800;
            color: #0d2e32;
            line-height: 1;
        }

        .stat-lbl {
            font-size: 0.72rem;
            color: #5a8087;
            margin-top: 2px;
        }

        .order-card {
            background: white;
            border: 1px solid #e8f0f2;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 14px;
            transition: box-shadow 0.2s;
        }

        .order-card:hover {
            box-shadow: 0 4px 20px rgba(3, 104, 115, 0.08);
        }

        .order-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid #f0f6f7;
            background: #fafefe;
            flex-wrap: wrap;
            gap: 8px;
        }

        .order-id {
            font-size: 0.8rem;
            font-weight: 700;
            color: #5a8087;
        }

        .order-date {
            font-size: 0.78rem;
            color: #aaa;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-pending {
            background: #fff8e6;
            color: #9a6800;
        }

        .badge-delivered {
            background: #eaf6f2;
            color: #0f6e56;
        }

        .badge-cancelled {
            background: #fdf0f0;
            color: #c0392b;
        }

        .order-body {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            flex-wrap: wrap;
        }

        .order-img {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background: #e8f6f7;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .order-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-img i {
            font-size: 1.8rem;
            color: rgba(3, 104, 115, .2);
        }

        .order-info {
            flex: 1;
            min-width: 0;
        }

        .order-title {
            font-weight: 700;
            font-size: 0.92rem;
            color: #0d2e32;
        }

        .order-seller {
            font-size: 0.78rem;
            color: #5a8087;
            margin-top: 3px;
        }

        .order-addr {
            font-size: 0.75rem;
            color: #aaa;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .order-price {
            font-size: 1.1rem;
            font-weight: 800;
            color: #036873;
            white-space: nowrap;
        }

        .order-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            border-top: 1px solid #f0f6f7;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn-track {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f6f7;
            color: #036873;
            border: none;
            border-radius: 8px;
            padding: 7px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }

        .btn-track:hover {
            background: #c2eaed;
            color: #036873;
        }

        .btn-msg {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: none;
            color: #5a8087;
            border: 1px solid #e8f0f2;
            border-radius: 8px;
            padding: 7px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.15s;
        }

        .btn-msg:hover {
            border-color: #036873;
            color: #036873;
        }

        .empty-state {
            text-align: center;
            padding: 64px 20px;
            background: white;
            border-radius: 16px;
            border: 1px solid #e8f0f2;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #c2eaed;
            display: block;
            margin-bottom: 16px;
        }

        .empty-state h4 {
            font-weight: 700;
            color: #0d2e32;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #5a8087;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }

        .btn-browse {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #036873;
            color: white;
            border-radius: 10px;
            padding: 11px 24px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-browse:hover {
            background: #024a52;
            color: white;
        }

        /* Progress tracker */
        .progress-track {
            display: flex;
            align-items: center;
            gap: 0;
            margin-top: 4px;
        }

        .prog-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            font-size: 0.62rem;
            color: #bbb;
            font-weight: 600;
        }

        .prog-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e8f0f2;
        }

        .prog-dot.done {
            background: #036873;
        }

        .prog-line {
            width: 32px;
            height: 2px;
            background: #e8f0f2;
        }

        .prog-line.done {
            background: #036873;
        }
    </style>
</head>

<body>

    <?php include '../includes/navbar-browse.php'; ?>

    <div class="container py-4" style="max-width: 820px;">

        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; flex-wrap:wrap; gap:12px;">
            <h1 class="page-title" style="margin-bottom:0;">My Orders</h1>
            <a href="<?php echo $base; ?>/products/browse.php" class="btn-browse" style="padding:8px 18px; font-size:0.82rem;">
                <i class="bi bi-search"></i> Browse More
            </a>
        </div>
        <p class="page-sub"><?php echo $totalOrders; ?> order<?php echo $totalOrders !== 1 ? 's' : ''; ?> placed</p>

        <!-- Stats -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f6f7; color:#036873;"><i class="bi bi-bag-check"></i></div>
                <div>
                    <div class="stat-val"><?php echo $totalOrders; ?></div>
                    <div class="stat-lbl">Total Orders</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e6; color:#9a6800;"><i class="bi bi-clock"></i></div>
                <div>
                    <div class="stat-val"><?php echo $pendingOrders; ?></div>
                    <div class="stat-lbl">Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf6f2; color:#0f6e56;"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-val"><?php echo $deliveredOrders; ?></div>
                    <div class="stat-lbl">Delivered</div>
                </div>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="bi bi-bag-x"></i>
                <h4>No orders yet</h4>
                <p>You haven't placed any orders. Start browsing!</p>
                <a href="<?php echo $base; ?>/products/browse.php"
                    class="btn-browse"
                    style="padding:7px 14px; font-size:0.78rem; border-radius:8px;">
                    <i class="bi bi-search"></i> Browse Listings
                </a>
            </div>

        <?php else: ?>
            <?php foreach ($orders as $o):
                $status = strtolower($o['status'] ?? 'pending');
                $badgeClass = match ($status) {
                    'delivered' => 'badge-delivered',
                    'cancelled' => 'badge-cancelled',
                    default     => 'badge-pending',
                };
                $statusIcon = match ($status) {
                    'delivered' => 'bi-check-circle-fill',
                    'cancelled' => 'bi-x-circle-fill',
                    default     => 'bi-clock-fill',
                };
                // Progress steps
                $steps = ['Ordered', 'Processing', 'Shipped', 'Delivered'];
                $stepsDone = match ($status) {
                    'pending'   => 1,
                    'shipped'   => 3,
                    'delivered' => 4,
                    default     => 1,
                };
            ?>
                <div class="order-card">

                    <!-- Header -->
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?php echo str_pad($o['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            <div class="order-date"><?php echo date('d F Y, H:i', strtotime($o['order_date'])); ?></div>
                        </div>
                        <span class="badge-status <?php echo $badgeClass; ?>">
                            <i class="bi <?php echo $statusIcon; ?>"></i>
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>

                    <!-- Body -->
                    <div class="order-body">
                        <div class="order-img">
                            <?php if (!empty($o['image_path'])): ?>
                                <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($o['image_path']); ?>"
                                    onerror="this.closest('.order-img').innerHTML='<i class=\'bi bi-box-seam\'></i>'">
                            <?php else: ?>
                                <i class="bi bi-box-seam"></i>
                            <?php endif; ?>
                        </div>

                        <div class="order-info">
                            <div class="order-title"><?php echo htmlspecialchars($o['product_title']); ?></div>
                            <div class="order-seller">Sold by <?php echo htmlspecialchars($o['seller_name']); ?></div>
                            <?php if (!empty($o['delivery_address'])): ?>
                                <div class="order-addr">
                                    <i class="bi bi-geo-alt" style="color:#036873;"></i>
                                    <?php echo htmlspecialchars($o['delivery_address']); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Progress tracker -->
                            <?php if ($status !== 'cancelled'): ?>
                                <div class="progress-track" style="margin-top:10px;">
                                    <?php foreach ($steps as $si => $step): ?>
                                        <div class="prog-step">
                                            <div class="prog-dot <?php echo ($si + 1) <= $stepsDone ? 'done' : ''; ?>"></div>
                                            <span style="color:<?php echo ($si + 1) <= $stepsDone ? '#036873' : '#bbb'; ?>">
                                                <?php echo $step; ?>
                                            </span>
                                        </div>
                                        <?php if ($si < count($steps) - 1): ?>
                                            <div class="prog-line <?php echo ($si + 1) < $stepsDone ? 'done' : ''; ?>"></div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="order-price">
                            R<?php echo number_format($o['total_price'] ?? $o['product_price'], 2); ?>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="order-footer">
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="<?php echo $base; ?>/products/view-product.php?id=<?php echo $o['id']; ?>" class="btn-track">
                                <i class="bi bi-eye"></i> View Product
                            </a>
                            <a href="<?php echo $base; ?>/messages.php?with=<?php echo $o['id']; ?>" class="btn-msg">
                                <i class="bi bi-chat-dots"></i> Contact Seller
                            </a>
                        </div>
                        <span style="font-size:0.75rem; color:#bbb;">
                            <?php
                            $days = floor((time() - strtotime($o['order_date'])) / 86400);
                            echo $days === 0 ? 'Today' : "$days day" . ($days > 1 ? 's' : '') . " ago";
                            ?>
                        </span>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>