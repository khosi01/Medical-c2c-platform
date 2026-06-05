<?php
session_start();
require_once '../config/db.php';

if (isset($_POST['add_to_cart'])) {
    $pid   = (int)$_POST['product_id'];
    $title = $_POST['product_title'];
    $price = (float)$_POST['product_price'];
    $image = $_POST['product_image'];

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $alreadyIn = false;
    foreach ($_SESSION['cart'] as $item) {
        if ($item['id'] === $pid) {
            $alreadyIn = true;
            break;
        }
    }

    if (!$alreadyIn) {
        $_SESSION['cart'][] = [
            'id'    => $pid,
            'title' => $title,
            'price' => $price,
            'image' => $image,
            'qty'   => 1,
        ];
        $cartMsg = 'success';
    } else {
        $cartMsg = 'exists';
    }
}

$base = '/medical-c2c-platform';

if (!isset($_GET['id'])) die("Product not found.");

$productId = $_GET['id'];
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name, u.profile_pic, u.profession, u.location, u.created_at as user_since
    FROM products p JOIN users u ON p.seller_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) die("Product not found.");

$imgStmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_cover DESC");
$imgStmt->execute([$productId]);
$productImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($productImages) && !empty($product['image_path'])) {
    $productImages = [$product['image_path']];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fdf5f8;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .breadcrumb-trail {
            font-size: 0.82rem;
            color: #aaa;
            margin-bottom: 22px;
        }

        .breadcrumb-trail a {
            color: #aaa;
            text-decoration: none;
        }

        .breadcrumb-trail .current {
            color: #555;
            font-weight: 600;
        }

        .gallery-wrap {
            background: #e1eff2;
            border-radius: 22px;
            position: relative;
            min-height: 370px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .gallery-wrap img {
            max-width: 100%;
            max-height: 340px;
            object-fit: contain;
        }

        .arrow-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
            color: #444;
            text-decoration: none;
        }

        .arrow-btn.left {
            left: 16px;
        }

        .arrow-btn.right {
            right: 16px;
        }

        .gallery-actions {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
        }

        .act-btn {
            width: 36px;
            height: 36px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .07);
            color: #036873;
            text-decoration: none;
            font-size: 1rem;
        }

        .thumb-row {
            display: flex;
            gap: 12px;
            margin: 14px 0 26px;
        }

        .thumb {
            width: 82px;
            height: 82px;
            border-radius: 12px;
            border: 1.5px solid #eee;
            background: #fff;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .thumb.active {
            border-color: #036873;
            border-width: 2px;
        }

        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 6px;
        }

        .desc-card {
            background: #fff;
            border-radius: 20px;
            padding: 28px 30px;
            box-shadow: 0 2px 16px rgba(3, 104, 115, .04);
        }

        .desc-card h4 {
            font-weight: 800;
            color: #036873;
            font-size: 1.2rem;
            margin-bottom: 14px;
        }

        .desc-card p {
            font-size: 0.88rem;
            color: #4a4a4a;
            line-height: 1.75;
        }

        .s-card {
            background: #fff;
            border-radius: 18px;
            padding: 20px 22px;
            box-shadow: 0 2px 14px rgba(3, 104, 115, .05);
            border: 1px solid #f0f6f7;
            margin-bottom: 16px;
        }

        .price {
            font-size: 1.75rem;
            font-weight: 800;
            color: #036873;
        }

        .cond-badge {
            background: #e4f7f9;
            color: #036873;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 11px;
            border-radius: 7px;
        }

        .btn-cart {
            width: 100%;
            background: #04a0af;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 14px 0 9px;
            cursor: pointer;
        }

        .btn-cart:hover {
            background: #028a97;
        }

        .btn-msg {
            width: 100%;
            background: #fff;
            color: #333;
            border: 1.5px solid #ddd;
            border-radius: 10px;
            padding: 11px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-msg:hover {
            border-color: #04a0af;
            color: #036873;
        }

        .meta-list {
            margin-top: 14px;
            display: flex;
            flex-direction: column;
            gap: 7px;
            font-size: 0.78rem;
            color: #666;
        }

        .meta-list span {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-list i {
            color: #04a0af;
        }

        .s-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #aaa;
            margin-bottom: 13px;
        }

        .seller-avatar {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            overflow: hidden;
            background: #036873;
            color: #fff;
            font-weight: 800;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .seller-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .seller-name {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0;
        }

        .seller-role {
            font-size: 0.72rem;
            color: #888;
            margin: 2px 0 5px;
        }

        .stars {
            color: #f4c542;
            font-size: 0.7rem;
        }

        .seller-stats {
            border-top: 1px solid #f0f6f7;
            margin-top: 13px;
            padding-top: 11px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.76rem;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
        }

        .stat-row .sk {
            color: #aaa;
        }

        .stat-row .sv {
            font-weight: 700;
        }

        .verified-pill {
            background: #e4f7f9;
            color: #036873;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 9px 14px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 12px;
        }

        .s-card.safety {
            background: #fafefe;
        }

        .safety-title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #036873;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
        }

        .safety ul {
            margin: 0;
            padding-left: 16px;
            font-size: 0.74rem;
            color: #666;
            line-height: 1.65;
        }
    </style>
</head>

<body Style="font-family: 'Poppins', DM SERIF;">

    <?php include '../includes/navbar-browse.php'; ?>

    <div class="container py-4">

        <div class="breadcrumb-trail">
            <a href="<?php echo $base; ?>">Home</a> /
            <a href="#"><?php echo htmlspecialchars($product['category'] ?? 'Equipment'); ?></a> /
            <span class="current"><?php echo htmlspecialchars($product['title']); ?></span>
        </div>

        <div class="row g-4">

            <!-- Left -->
            <div class="col-lg-7">
                <div class="gallery-wrap">
                    <?php if (!empty($product['image_path'])): ?>
                        <img id="mainImg" src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" alt="">
                    <?php else: ?>
                        <i class="bi bi-box-seam" style="font-size:5rem;color:rgba(3,104,115,.2);"></i>
                    <?php endif; ?>
                    <a href="#" class="arrow-btn left"><i class="bi bi-chevron-left"></i></a>
                    <a href="#" class="arrow-btn right"><i class="bi bi-chevron-right"></i></a>
                    <div class="gallery-actions">
                        <a href="#" class="act-btn" id="wishBtn"><i class="bi bi-heart"></i></a>
                        <a href="#" class="act-btn"><i class="bi bi-share"></i></a>
                    </div>
                </div>

                <div class="thumb-row">
                    <?php foreach ($productImages as $i => $img): ?>
                        <div class="thumb <?php echo $i === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($img); ?>"
                                onerror="this.closest('.thumb').style.display='none'"
                                onclick="document.getElementById('mainImg').src=this.src;
                      document.querySelectorAll('.thumb').forEach(t=>t.classList.remove('active'));
                      this.closest('.thumb').classList.add('active');">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="desc-card">
                    <h4>Product Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
            </div>

            <!-- Right -->
            <div class="col-lg-5">

                <div class="s-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="price">R<?php echo number_format($product['price'], 2); ?></span>
                        <span class="cond-badge"><?php echo htmlspecialchars($product['p_condition']); ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="add_to_cart" value="1">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="product_title" value="<?php echo htmlspecialchars($product['title']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                        <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>">
                        <button type="submit" class="btn-cart">
                            <i class="bi bi-cart-plus"></i>
                            <?php
                            $inCart = false;
                            if (isset($_SESSION['cart'])) {
                                foreach ($_SESSION['cart'] as $item) {
                                    if ($item['id'] == $product['id']) {
                                        $inCart = true;
                                        break;
                                    }
                                }
                            }
                            echo $inCart ? 'Added to Cart ✓' : 'Add to Cart';
                            ?>
                        </button>
                    </form>

                    <?php if (isset($cartMsg) && $cartMsg === 'success'): ?>
                        <div style="background:#eaf6f2;color:#0f6e56;border:1px solid #a8dece;border-radius:8px;padding:10px 14px;font-size:.85rem;font-weight:500;margin-top:8px;display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-check-circle-fill"></i> Added to cart!
                            <a href="<?php echo $base; ?>/transactions/cart.php" style="color:#036873;font-weight:700;margin-left:auto;">View Cart →</a>
                        </div>
                    <?php elseif (isset($cartMsg) && $cartMsg === 'exists'): ?>
                        <div style="background:#fff8e6;color:#9a6800;border:1px solid #f0d080;border-radius:8px;padding:10px 14px;font-size:.85rem;font-weight:500;margin-top:8px;">
                            <i class="bi bi-info-circle"></i> Already in your cart.
                            <a href="<?php echo $base; ?>/transactions/cart.php" style="color:#036873;font-weight:700;margin-left:auto;">View Cart →</a>
                        </div>
                    <?php endif; ?>
                    <a href="<?php echo $base; ?>/messages.php?with=<?php echo $product['seller_id']; ?>&prod=<?php echo $product['id']; ?>"
                        onclick="openMsgPopup(event)"
                        class="btn-msg">
                        <i class="bi bi-chat-dots"></i> Message Seller
                    </a>
                    <div class="meta-list">
                        <span><i class="bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($product['location'] ?? 'Johannesburg, Gauteng'); ?></span>
                        <span><i class="bi bi-shield-check"></i>Buyer Protection Included</span>
                        <span><i class="bi bi-tags-fill"></i>Competitive Market Pricing</span>
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-label">Seller Information</div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="seller-avatar">
                            <?php if (!empty($product['profile_pic'])): ?>
                                <img src="<?php echo $base; ?>/uploads/profiles/<?php echo htmlspecialchars($product['profile_pic']); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($product['full_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="<?php echo $base; ?>/products/public-profile.php?id=<?php echo $product['seller_id']; ?>" style="text-decoration:none; color:inherit;">
                                <p class="seller-name"><?php echo htmlspecialchars($product['full_name']); ?></p>
                            </a>
                            <p class="seller-role"><?php echo htmlspecialchars($product['profession'] ?? 'Medical Professional'); ?></p>
                            <div class="stars">
                                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                <span style="color:#aaa;font-size:.68rem;margin-left:4px;">(100 reviews)</span>
                            </div>
                        </div>
                    </div>
                    <div class="seller-stats">
                        <div class="stat-row"><span class="sk">Response Time</span><span class="sv">Within 3 hours</span></div>
                        <div class="stat-row"><span class="sk">Items Listed</span><span class="sv">12 Active</span></div>
                        <div class="stat-row"><span class="sk">Member Since</span><span class="sv"><?php echo date('F Y', strtotime($product['user_since'] ?? 'now')); ?></span></div>
                    </div>
                    <div class="verified-pill"><i class="bi bi-patch-check-fill"></i> Verified Seller</div>
                </div>

                <div class="s-card safety">
                    <div class="safety-title"><i class="bi bi-shield-shaded"></i> Safety Tips</div>
                    <ul>
                        <li>Meet in a secure public healthcare facility or public space.</li>
                        <li>Inspect item thoroughly before completing payment.</li>
                        <li>Transact safely inside our platform.</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <div id="msgPopup" style="
  display:none; position:fixed; inset:0;
  background:rgba(0,0,0,0.45); z-index:1000;
  align-items:center; justify-content:center;">

        <div style="
    background:white; border-radius:20px; padding:28px;
    width:100%; max-width:420px; margin:16px;
    box-shadow:0 20px 60px rgba(0,0,0,0.2); position:relative;">

            <button onclick="closeMsgPopup()" style="
      position:absolute; top:14px; right:16px;
      background:none; border:none; font-size:1.2rem;
      color:#aaa; cursor:pointer;">
                <i class="bi bi-x-lg"></i>
            </button>

            <!-- Header -->
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div style="
        width:44px; height:44px; border-radius:10px;
        background:#e8f6f7; display:flex; align-items:center;
        justify-content:center; color:#036873; font-size:1.3rem; flex-shrink:0;">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:0.95rem; color:#0d2e32;">Message Seller</div>
                    <div style="font-size:0.78rem; color:#5a8087;">
                        <?php echo htmlspecialchars($product['full_name']); ?> &middot;
                        <?php echo htmlspecialchars($product['title']); ?>
                    </div>
                </div>
            </div>
            <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#5a8087; margin-bottom:10px;">
                Choose a message
            </div>

            <div style="display:flex; flex-direction:column; gap:8px;" id="suggestionList">
                <?php
                $suggestions = [
                    "Is this item still available?",
                    "What's the lowest price you'd accept?",
                    "Can I see more photos of this item?",
                    "Is the price negotiable?",
                    "Can you deliver to my location?",
                    "What's included with this item?",
                ];
                foreach ($suggestions as $s): ?>
                    <button onclick="sendSuggestion('<?php echo addslashes($s); ?>')"
                        style="
          text-align:left; background:#f4f7f8; border:1.5px solid #e8f0f2;
          border-radius:10px; padding:11px 14px; font-family:'Poppins',sans-serif;
          font-size:0.85rem; color:#0d2e32; cursor:pointer;
          transition:border-color 0.15s, background 0.15s;
          display:flex; align-items:center; justify-content:space-between; gap:8px;"
                        onmouseover="this.style.borderColor='#036873';this.style.background='#e8f6f7';"
                        onmouseout="this.style.borderColor='#e8f0f2';this.style.background='#f4f7f8';">
                        <?php echo htmlspecialchars($s); ?>
                        <i class="bi bi-arrow-right" style="color:#036873; flex-shrink:0;"></i>
                    </button>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="/medical-c2c-platform/messages.php?with=<?php echo $product['seller_id']; ?>&prod=<?php echo $product['id']; ?>" id="quickMsgForm">
                <input type="hidden" name="receiver_id" value="<?php echo $product['seller_id']; ?>">
                <input type="hidden" name="product_context_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="message_text" id="quickMsgText">
            </form>

        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.thumb').forEach(t => {
            t.addEventListener('click', () => {
                document.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
                t.classList.add('active');
                const src = t.querySelector('img').src;
                const main = document.getElementById('mainImg');
                if (main) main.src = src;
            });
        });
        const thumbs = [...document.querySelectorAll('.thumb')];
        let currentIndex = 0;

        function goToImage(index) {
            if (index < 0 || index >= thumbs.length) return;
            currentIndex = index;
            thumbs.forEach(t => t.classList.remove('active'));
            thumbs[currentIndex].classList.add('active');
            const src = thumbs[currentIndex].querySelector('img')?.src;
            const main = document.getElementById('mainImg');
            if (main && src) main.src = src;
        }

        document.querySelector('.arrow-btn.left').addEventListener('click', e => {
            e.preventDefault();
            goToImage(currentIndex - 1);
        });

        document.querySelector('.arrow-btn.right').addEventListener('click', e => {
            e.preventDefault();
            goToImage(currentIndex + 1);
        });

        document.getElementById('wishBtn').addEventListener('click', e => {
            e.preventDefault();
            const i = e.currentTarget.querySelector('i');
            i.classList.toggle('bi-heart');
            i.classList.toggle('bi-heart-fill');
            i.style.color = i.classList.contains('bi-heart-fill') ? '#e05' : '';
        });

        function openMsgPopup(e) {
            e.preventDefault();
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = '/medical-c2c-platform/auth/login.php?redirect=' +
                    encodeURIComponent(window.location.pathname + window.location.search);
                return;
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $product['seller_id']): ?>
                alert("You can't message yourself.");
                return;
            <?php endif; ?>
            document.getElementById('msgPopup').style.display = 'flex';
        }

        function closeMsgPopup() {
            document.getElementById('msgPopup').style.display = 'none';
        }

        function sendSuggestion(text) {
            document.getElementById('quickMsgText').value = text;
            document.getElementById('quickMsgForm').submit();
        }

        document.getElementById('msgPopup').addEventListener('click', function(e) {
            if (e.target === this) closeMsgPopup();
        });
    </script>
</body>

</html>