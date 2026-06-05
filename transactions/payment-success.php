<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base = '/medical-c2c-platform';

if (empty($_SESSION['checkout']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$checkout = $_SESSION['checkout'];
$cart     = $_SESSION['cart'];
$total    = $checkout['total'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($cart as $item) {
        $pdo->prepare("
            INSERT INTO orders (buyer_id, product_id, status, order_date, delivery_address, total_price)
            VALUES (?, ?, 'pending', NOW(), ?, ?)
        ")->execute([
            $_SESSION['user_id'],
            $item['id'],
            $checkout['address'],
            $item['price'],
        ]);
    }

    unset($_SESSION['cart'], $_SESSION['checkout']);

    header("Location: payment-success.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .step.done .step-num {
            background: #036873;
            color: white;
        }

        .step.active .step-num {
            background: #036873;
            color: white;
        }

        .step.inactive .step-num {
            background: #f0f0f0;
            color: #aaa;
        }

        .step.done span {
            color: #036873;
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

        .step-line.done {
            background: #036873;
        }

        .pay-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e8f0f2;
            padding: 28px;
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: 700;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #5a8087;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #036873;
        }

        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #0d2e32;
            margin-bottom: 5px;
        }

        .form-control {
            border: 1.5px solid #e8f0f2;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            padding: 10px 14px;
            background: #f9fbfc;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: #036873;
            box-shadow: 0 0 0 3px rgba(3, 104, 115, 0.08);
            background: white;
            outline: none;
        }

        .card-preview {
            background: linear-gradient(135deg, #036873, #024a52);
            border-radius: 16px;
            padding: 24px;
            color: white;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
            min-height: 160px;
        }

        .card-preview::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
        }

        .card-preview::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -20px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
        }

        .card-chip {
            width: 36px;
            height: 26px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .card-number-display {
            font-size: 1.05rem;
            letter-spacing: 0.15em;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .card-bottom {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
        }

        .card-label {
            opacity: 0.6;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .payment-methods {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .pay-method {
            border: 1.5px solid #e8f0f2;
            border-radius: 10px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            color: #5a8087;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pay-method.active {
            border-color: #036873;
            color: #036873;
            background: #e8f6f7;
        }

        .btn-pay {
            width: 100%;
            background: #036873;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.97rem;
            cursor: pointer;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-pay:hover {
            background: #024a52;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e8f0f2;
            padding: 24px;
            position: sticky;
            top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #5a8087;
            margin-bottom: 10px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            font-size: 1.05rem;
            color: #0d2e32;
            border-top: 1px solid #e8f0f2;
            padding-top: 12px;
            margin-top: 4px;
        }

        .summary-total span:last-child {
            color: #036873;
        }

        .demo-notice {
            background: #fff8e6;
            border: 1px solid #f0d080;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.8rem;
            color: #9a6800;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>

<body>

    <?php include '../includes/navbar-browse.php'; ?>

    <div class="container py-4" style="max-width: 960px;">

        <h1 class="page-title">Payment</h1>
        <p class="page-sub">Complete your payment to place the order.</p>

        <!-- Step bar -->
        <div class="step-bar">
            <div class="step done">
                <div class="step-num"><i class="bi bi-check"></i></div><span>Cart</span>
            </div>
            <div class="step-line done"></div>
            <div class="step done">
                <div class="step-num"><i class="bi bi-check"></i></div><span>Checkout</span>
            </div>
            <div class="step-line done"></div>
            <div class="step active">
                <div class="step-num">3</div><span>Payment</span>
            </div>
            <div class="step-line"></div>
            <div class="step inactive">
                <div class="step-num">4</div><span>Confirmation</span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">

                <div class="demo-notice">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><strong>Demo mode:</strong> No real payment will be processed. Use any card details to simulate a successful payment.</span>
                </div>

                <!-- Payment method-->
                <div class="payment-methods">
                    <div class="pay-method active" onclick="setMethod(this)"><i class="bi bi-credit-card"></i> Card</div>
                    <div class="pay-method" onclick="setMethod(this)"><i class="bi bi-bank"></i> EFT</div>
                    <div class="pay-method" onclick="setMethod(this)"><i class="bi bi-phone"></i> SnapScan</div>
                </div>

                <div class="pay-card">
                    <div class="section-title"><i class="bi bi-credit-card"></i> Card Details</div>

                    <!--card preview -->
                    <div class="card-preview">
                        <div class="card-chip"></div>
                        <div class="card-number-display" id="cardDisplay">•••• •••• •••• ••••</div>
                        <div class="card-bottom">
                            <div>
                                <div class="card-label">Card Holder</div>
                                <div id="cardName"><?php echo htmlspecialchars($checkout['full_name']); ?></div>
                            </div>
                            <div>
                                <div class="card-label">Expires</div>
                                <div id="cardExpiry">MM/YY</div>
                            </div>
                            <div style="font-size:1.4rem; opacity:0.8;">
                                <i class="bi bi-credit-card-2-back"></i>
                            </div>
                        </div>
                    </div>

                    <form method="POST" id="paymentForm">
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="cardNum" placeholder="1234 5678 9012 3456"
                                maxlength="19" oninput="formatCard(this)" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Cardholder Name</label>
                                <input type="text" class="form-control" placeholder="As on card"
                                    value="<?php echo htmlspecialchars($checkout['full_name']); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Expiry</label>
                                <input type="text" class="form-control" id="expiry" placeholder="MM/YY"
                                    maxlength="5" oninput="formatExpiry(this)" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CVV</label>
                                <input type="password" class="form-control" placeholder="•••" maxlength="4" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-pay" id="payBtn">
                            <i class="bi bi-lock-fill"></i>
                            Pay R<?php echo number_format($total, 2); ?>
                        </button>
                    </form>

                    <div style="display:flex; align-items:center; justify-content:center; gap:8px; margin-top:14px; font-size:.75rem; color:#5a8087;">
                        <i class="bi bi-shield-lock-fill" style="color:#036873;"></i>
                        256-bit SSL encryption · Powered by PayFast
                    </div>
                </div>

            </div>

            <div class="col-lg-5">
                <div class="summary-card">
                    <div class="summary-title" style="font-weight:700;font-size:1rem;color:#0d2e32;margin-bottom:16px;">Order Summary</div>

                    <?php foreach ($cart as $item): ?>
                        <div class="summary-row">
                            <span style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </span>
                            <span style="font-weight:600;">R<?php echo number_format($item['price'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div class="summary-row"><span>Delivery (<?php echo htmlspecialchars($checkout['delivery']); ?>)</span><span>TBD</span></div>
                    <div class="summary-total"><span>Total</span><span>R<?php echo number_format($total, 2); ?></span></div>

                    <!-- Delivery details -->
                    <div style="margin-top:16px; padding-top:16px; border-top:1px solid #e8f0f2;">
                        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#5a8087;margin-bottom:8px;">Delivering to</div>
                        <div style="font-size:.83rem;color:#0d2e32;font-weight:600;"><?php echo htmlspecialchars($checkout['full_name']); ?></div>
                        <div style="font-size:.8rem;color:#5a8087;margin-top:2px;"><?php echo htmlspecialchars($checkout['address']); ?></div>
                        <div style="font-size:.8rem;color:#5a8087;"><?php echo htmlspecialchars($checkout['phone']); ?></div>
                    </div>

                    <div style="text-align:center; margin-top:16px;">
                        <a href="checkout.php" style="color:#5a8087; font-size:.78rem; text-decoration:none;">
                            <i class="bi bi-arrow-left"></i> Back to Checkout
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function formatCard(input) {
            let v = input.value.replace(/\D/g, '').substring(0, 16);
            input.value = v.replace(/(.{4})/g, '$1 ').trim();
            document.getElementById('cardDisplay').textContent =
                (v + '################').substring(0, 16).replace(/(.{4})/g, '$1 ').trim().replace(/\d/g, (c, i) => i < 12 ? '•' : c);
        }

        function formatExpiry(input) {
            let v = input.value.replace(/\D/g, '');
            if (v.length >= 2) v = v.substring(0, 2) + '/' + v.substring(2, 4);
            input.value = v;
            document.getElementById('cardExpiry').textContent = input.value || 'MM/YY';
        }

        function setMethod(el) {
            document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('active'));
            el.classList.add('active');
        }

        document.getElementById('paymentForm').addEventListener('submit', function() {
            const btn = document.getElementById('payBtn');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
            btn.disabled = true;
        });
    </script>
</body>

</html>