<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base   = '/medical-c2c-platform';
$userId = $_SESSION['user_id'];

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$cart  = $_SESSION['cart'];
$total = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

// Fetch user details 
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName   = trim($_POST['full_name']   ?? '');
    $email      = trim($_POST['email']       ?? '');
    $phone      = trim($_POST['phone']       ?? '');
    $address    = trim($_POST['address']     ?? '');
    $city       = trim($_POST['city']        ?? '');
    $province   = trim($_POST['province']    ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $delivery   = trim($_POST['delivery']    ?? '');

    if (empty($fullName) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($delivery)) {
        $error = "<div class='err-msg'><i class='bi bi-exclamation-circle'></i> Please fill in all required fields.</div>";
    } else {
        $_SESSION['checkout'] = [
            'full_name'    => $fullName,
            'email'        => $email,
            'phone'        => $phone,
            'address'      => "$address, $city, $province, $postalCode",
            'delivery'     => $delivery,
            'total'        => $total,
        ];
        header("Location: payment-success.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | MedMarket</title>
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

        .form-card {
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
            font-size: 1rem;
        }

        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #0d2e32;
            margin-bottom: 5px;
        }

        .form-control,
        .form-select {
            border: 1.5px solid #e8f0f2;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            padding: 10px 14px;
            background: #f9fbfc;
            color: #333;
            transition: border-color 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #036873;
            box-shadow: 0 0 0 3px rgba(3, 104, 115, 0.08);
            background: white;
        }

        .delivery-option {
            border: 1.5px solid #e8f0f2;
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .delivery-option:hover {
            border-color: #036873;
            background: #f4fcfd;
        }

        .delivery-option input[type="radio"] {
            accent-color: #036873;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .delivery-option.selected {
            border-color: #036873;
            background: #e8f6f7;
        }

        .delivery-name {
            font-weight: 600;
            font-size: 0.88rem;
            color: #0d2e32;
        }

        .delivery-desc {
            font-size: 0.75rem;
            color: #5a8087;
            margin-top: 2px;
        }

        .err-msg {
            background: #fdf0f0;
            color: #c0392b;
            border: 1px solid #f5c6c6;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
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

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f6f7;
            gap: 8px;
        }

        .summary-item:last-of-type {
            border-bottom: none;
        }

        .item-thumb {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #e8f6f7;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            font-size: 1.05rem;
            color: #0d2e32;
            border-top: 1px solid #e8f0f2;
            padding-top: 14px;
            margin-top: 8px;
        }

        .summary-total span:last-child {
            color: #036873;
        }

        .btn-pay {
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
            transition: background 0.2s;
        }

        .btn-pay:hover {
            background: #024a52;
        }

        .secure-note {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: #5a8087;
            margin-top: 12px;
            justify-content: center;
        }

        .secure-note i {
            color: #036873;
        }
    </style>
</head>

<body>

    <?php include '../includes/navbar-browse.php'; ?>

    <div class="container py-4" style="max-width: 960px;">

        <h1 class="page-title">Checkout</h1>
        <p class="page-sub">Fill in your delivery details to complete your order.</p>

        <!-- Step bar -->
        <div class="step-bar">
            <div class="step done">
                <div class="step-num"><i class="bi bi-check"></i></div><span>Cart</span>
            </div>
            <div class="step-line done"></div>
            <div class="step active">
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

        <?php if ($error) echo $error; ?>

        <form method="POST">
            <div class="row g-4">

                <!-- Contact details -->
                <div class="col-lg-7">
                    <div class="form-card">
                        <div class="section-title"><i class="bi bi-person"></i> Contact Details</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control"
                                    value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" placeholder="e.g. 071 234 5678" required>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery address -->
                    <div class="form-card">
                        <div class="section-title"><i class="bi bi-geo-alt"></i> Delivery Address</div>
                        <div class="row g-3">
                            <div class=" col-12">
                                <label class="form-label">Street Address *</label>
                                <input type="text" name="address" class="form-control" placeholder="e.g. 12 June Street" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">City *</label>
                                <input type="text" name="city" class="form-control" placeholder="e.g. Johannesburg" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Province</label>
                                <select name="province" class="form-select">
                                    <option value="">Select</option>
                                    <?php foreach (['Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Limpopo', 'Mpumalanga', 'North West', 'Free State', 'Northern Cape'] as $p): ?>
                                        <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2" style="margin:auto;">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" placeholder="2000">
                            </div>
                        </div>
                    </div>

                    <!-- Delivery method -->
                    <div class="form-card">
                        <div class="section-title"><i class="bi bi-truck"></i> Delivery Method</div>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <?php
                            $deliveryOptions = [
                                ['Paxi',    'Pep to Pep',        'R65',  'bi-shop'],
                                ['Aramex',  'Store to Door',      'R89',  'bi-box-seam'],
                                ['Postnet', 'Postnet to Postnet', 'R75',  'bi-envelope'],
                                ['Courier', 'The Courier Guy',    'R99',  'bi-truck'],
                                ['Pickup',  'Local Pickup',       'Free', 'bi-person-walking'],
                            ];
                            foreach ($deliveryOptions as [$val, $desc, $price, $icon]): ?>
                                <label class="delivery-option" onclick="this.classList.add('selected'); document.querySelectorAll('.delivery-option').forEach(o=>{ if(o!==this) o.classList.remove('selected'); })">
                                    <input type="radio" name="delivery" value="<?php echo $val; ?>" <?php echo $val === 'Paxi' ? 'checked' : ''; ?>>
                                    <i class="bi <?php echo $icon; ?>" style="color:#036873; font-size:1.1rem; flex-shrink:0;"></i>
                                    <div style="flex:1;">
                                        <div class="delivery-name"><?php echo $val; ?></div>
                                        <div class="delivery-desc"><?php echo $desc; ?></div>
                                    </div>
                                    <span style="font-weight:700; color:#036873; font-size:0.85rem;"><?php echo $price; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <!-- order Summary -->
                <div class="col-lg-5">
                    <div class="summary-card">
                        <div class="summary-title">Order Summary</div>

                        <?php foreach ($cart as $item): ?>
                            <div class="summary-item">
                                <div class="item-thumb">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($item['image']); ?>"
                                            onerror="this.closest('.item-thumb').innerHTML='<i class=\'bi bi-box-seam\' style=\'color:#036873;\'></i>'">
                                    <?php else: ?>
                                        <i class="bi bi-box-seam" style="color:#036873;"></i>
                                    <?php endif; ?>
                                </div>
                                <span style="flex:1; font-size:0.83rem; font-weight:500; color:#0d2e32; padding:0 8px;">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </span>
                                <span style="font-weight:700; color:#036873; white-space:nowrap;">
                                    R<?php echo number_format($item['price'], 2); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <div style="display:flex;justify-content:space-between;font-size:.85rem;color:#5a8087;margin-top:12px;">
                            <span>Subtotal</span><span>R<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:.85rem;color:#5a8087;margin-top:6px;">
                            <span>Delivery</span><span style="color:#0f6e56;font-weight:600;">TBD</span>
                        </div>

                        <div class="summary-total">
                            <span>Total</span>
                            <span>R<?php echo number_format($total, 2); ?></span>
                        </div>

                        <button type="submit" class="btn-pay">
                            <i class="bi bi-lock-fill"></i> Continue to Payment
                        </button>

                        <div class="secure-note">
                            <i class="bi bi-shield-check"></i>
                            Your information is encrypted and secure
                        </div>

                        <div style="text-align:center; margin-top:12px;">
                            <a href="cart.php" style="color:#5a8087; font-size:0.78rem; text-decoration:none;">
                                <i class="bi bi-arrow-left"></i> Back to Cart
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.delivery-option')?.classList.add('selected');
    </script>
</body>

</html>