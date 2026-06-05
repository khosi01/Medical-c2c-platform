<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base = '/medical-c2c-platform';

if (isset($_POST['remove']) && isset($_SESSION['cart'])) {
    $removeId = (int)$_POST['remove_id'];
    $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($i) => $i['id'] !== $removeId);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}
if (isset($_POST['clear_cart'])) {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}

$cart  = $_SESSION['cart'] ?? [];
$total = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | MedMarket</title>
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

        .cart-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e8f0f2;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid #f0f6f7;
            transition: background 0.15s;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item:hover {
            background: #fafefe;
        }

        .item-img {
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

        .item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-img i {
            font-size: 1.8rem;
            color: rgba(3, 104, 115, .25);
        }

        .item-info {
            flex: 1;
            min-width: 0;
        }

        .item-title {
            font-weight: 700;
            font-size: 0.92rem;
            color: #0d2e32;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-meta {
            font-size: 0.78rem;
            color: #5a8087;
            margin-top: 3px;
        }

        .item-price {
            font-size: 1.05rem;
            font-weight: 800;
            color: #036873;
            white-space: nowrap;
        }

        .btn-remove {
            background: none;
            border: none;
            color: #ccc;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 4px;
            transition: color 0.15s;
            flex-shrink: 0;
        }

        .btn-remove:hover {
            color: #c0392b;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e8f0f2;
            padding: 24px;
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-weight: 700;
            font-size: 1rem;
            color: #0d2e32;
            margin-bottom: 18px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #5a8087;
            margin-bottom: 10px;
        }

        .summary-row.total {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0d2e32;
            border-top: 1px solid #e8f0f2;
            padding-top: 12px;
            margin-top: 4px;
        }

        .summary-row.total span:last-child {
            color: #036873;
        }

        .btn-checkout {
            width: 100%;
            background: #036873;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-checkout:hover {
            background: #024a52;
            color: white;
        }

        .btn-clear {
            width: 100%;
            background: none;
            color: #c0392b;
            border: 1px solid #f5c6c6;
            border-radius: 10px;
            padding: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.15s;
        }

        .btn-clear:hover {
            background: #fdf0f0;
        }

        .empty-cart {
            text-align: center;
            padding: 64px 20px;
            background: white;
            border-radius: 16px;
            border: 1px solid #e8f0f2;
        }

        .empty-cart i {
            font-size: 3.5rem;
            color: #c2eaed;
            display: block;
            margin-bottom: 16px;
        }

        .empty-cart h4 {
            font-weight: 700;
            color: #0d2e32;
            margin-bottom: 8px;
        }

        .empty-cart p {
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

        .step-bar {
            display: flex;
            align-items: center;
            margin-bottom: 32px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .step.active .step-num {
            background: #036873;
            color: white;
        }

        .step.inactive .step-num {
            background: #f0f0f0;
            color: #aaa;
        }

        .step.active span {
            color: #0d2e32;
        }

        .step.inactive span {
            color: #bbb;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #e8f0f2;
            margin: 0 8px;
        }
    </style>
</head>

<body>

    <?php include '../includes/navbar-browse.php'; ?>

    <div class="container py-4" style="max-width: 960px;">

        <h1 class="page-title">My Cart</h1>
        <p class="page-sub"><?php echo count($cart); ?> item<?php echo count($cart) !== 1 ? 's' : ''; ?> in your cart</p>

        <!-- Step bar -->
        <div class="step-bar">
            <div class="step active">
                <div class="step-num">1</div><span>Cart</span>
            </div>
            <div class="step-line"></div>
            <div class="step inactive">
                <div class="step-num">2</div><span>Checkout</span>
            </div>
            <div class="step-line"></div>
            <div class="step inactive">
                <div class="step-num">3</div><span>Payment</span>
            </div>
            <div class="step-line"></div>
            <div class="step inactive">
                <div class="step-num">4</div><span>Confirmation</span>
            </div>
        </div>

        <?php if (empty($cart)): ?>
            <div class="empty-cart">
                <i class="bi bi-cart-x"></i>
                <h4>Your cart is empty</h4>
                <p>Browse listings and add items to your cart.</p>
                <a href="<?php echo $base; ?>/products/browse.php" class="btn-browse">
                    <i class="bi bi-search" style="padding:7px 14px; font-size:0.78rem; border-radius:8px;"></i> Browse Listings
                </a>
            </div>

        <?php else: ?>
            <div class=" row g-4">

                <!-- Cart items -->
                <div class="col-lg-8">
                    <div class="cart-card">
                        <?php foreach ($cart as $item): ?>
                            <div class="cart-item">
                                <div class="item-img">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($item['image']); ?>"
                                            onerror="this.closest('.item-img').innerHTML='<i class=\'bi bi-box-seam\'></i>'">
                                    <?php else: ?>
                                        <i class="bi bi-box-seam"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="item-info">
                                    <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="item-meta">Qty: <?php echo $item['qty']; ?></div>
                                </div>
                                <div class="item-price">R<?php echo number_format($item['price'], 2); ?></div>
                                <form method="POST">
                                    <input type="hidden" name="remove" value="1">
                                    <input type="hidden" name="remove_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-remove" title="Remove item"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?php echo $base; ?>/products/browse.php"
                        style="color:#036873;font-size:.85rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                    </a>
                </div>

                <!-- order Summary -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <div class="summary-title">Order Summary</div>
                        <?php foreach ($cart as $item): ?>
                            <div class="summary-row">
                                <span style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($item['title']); ?></span>
                                <span>R<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="summary-row"><span>Subtotal</span><span>R<?php echo number_format($total, 2); ?></span></div>
                        <div class="summary-row"><span>Delivery</span><span style="color:#0f6e56;font-weight:600;">TBD</span></div>
                        <div class="summary-row total"><span>Total</span><span>R<?php echo number_format($total, 2); ?></span></div>
                        <a href="<?php echo $base; ?>/transactions/checkout.php" class="btn-checkout">
                            Proceed to Checkout <i class="bi bi-arrow-right"></i>
                        </a>
                        <form method="POST">
                            <button type="submit" name="clear_cart" class="btn-clear"><i class="bi bi-trash"></i> Clear Cart</button>
                        </form>
                        <div style="display:flex;align-items:center;gap:6px;margin-top:16px;font-size:.75rem;color:#5a8087;">
                            <i class="bi bi-shield-check" style="color:#036873;"></i> Secure checkout powered by MedMarket
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>