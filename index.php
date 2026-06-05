<?php
session_start();
require_once 'config/db.php';

$userCount = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
$productCount = $pdo->query("SELECT count(*) FROM products")->fetchColumn();

// Browse by Category
$bookSCount = $pdo->query("SELECT count(*) FROM products WHERE category = 'Medical Books'")->fetchColumn();
$equipCount = $pdo->query("SELECT count(*) FROM products WHERE category = 'Equipment'")->fetchColumn();
$trainCount = $pdo->query("SELECT count(*) FROM products WHERE category = 'Training Materials'")->fetchColumn();
$labCount = $pdo->query("SELECT count(*) FROM products WHERE category = 'Lab Supplies'")->fetchColumn();

$stmt = $pdo->query("
    SELECT p.*, u.full_name AS seller_name, u.location AS seller_location 
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 6
");

$featuredProducts = $stmt->fetchAll();
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

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'timedout'): ?>
        <div class="alert alert-info text-center border-0 rounded-0" style="background-color: var(--bg-ice-blue); color: var(--dark-teal);">
            <i class="bi bi-emoji-frown me-2"></i>
            <strong>Sad to see you go!</strong> You were signed out due to inactivity. We'll be here when you're ready to come back!
        </div>
    <?php endif; ?>

    <section class="hero-section" style="background: linear-gradient(rgba(3, 104, 115, 0.4), rgba(3, 104, 115, 0.4)), url('assets/images/hero.jpg');">
        <div class="container py-5">
            <div class="row align-items-center">

                <div class="col-md-7 text-white">
                    <span class="badge rounded-pill bg-success-light mb-3" style="font-family: roboto; font-size: 16px; background-color: rgba(189, 248, 255, 0.4); padding: 10px"><span class="active-status" style="color:#04C98E; font-size: 20px;"> ● </span>LIVE IN SOUTH AFRICA</span>
                    <h1 class="display-4 fw-bold">Trusted Marketplace for <span class="text-info-med" style="color:#51EEFF;">Medical </span> <span class="text-info-resources" style="color:#90D4DB;">Resources</span></h1>
                    <p class="lead mb-4" style="font-family: DM Serif Display; font-style: italic;">Buy and sell medical books, equipment, and training materials with confidence.</p>

                    <div class="d-flex gap-3" style="margin: auto;">
                        <a href="products/browse.php" class="btn-browse"><i class="bi bi-search"></i> Browse Items</a>
                        <a href="products/add-product.php" class="btn-sell">Start Selling →</a>
                    </div>
                </div>


                <div class="col-md-5" style="margin: auto; padding: 20px;">
                    <div class="glass-stats-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; justify-items: center;">
                        <div class="stat-box" style="margin: auto; background-color: #90D4DB; border-radius: 20px; height: 100px; width:100px; padding: 10px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                            <h3 style="margin: 0;"><?php echo number_format($userCount); ?></h3>
                            <p style="margin: 0;">Active Users</p>
                        </div>
                        <div class="stat-box" style="margin: auto; background-color: #90D4DB; border-radius: 20px; height: 100px; width:100px; padding: 10px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                            <h3 style="margin: 0;"><?php echo number_format($productCount); ?></h3>
                            <p style="margin: 0;">Products</p>
                        </div>
                        <div class="stat-box" style="margin: auto; background-color: #90D4DB; border-radius: 20px; height: 100px; width:100px; padding: 10px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                            <h3 style="margin: 0;">98%</h3>
                            <p style="margin: 0;">Satisfaction</p>
                        </div>
                        <div class="stat-box" style="margin: auto; background-color: #90D4DB; border-radius: 20px; height: 100px; width:100px; padding: 10px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                            <h3 style="margin: 0;">24/7</h3>
                            <p style="margin: 0;">Support</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!--BROWSE BY CATEGORY-->
    <div class="bg-ice-light">
        <section class="container my-5 text-center">
            <h2 class="mb-5" style="color: var(--dark-teal); font-weight: 700;">Browse by Category</h2>

            <div class="row g-4 justify-content-center">
                <div class="col-md-3">
                    <a href="products/browse.php?cat=books" class="category-block h-100 d-block">
                        <div class="icon-circle"><i class="bi bi-book"></i></div>
                        <h5>Medical Books</h5>
                        <p><?php echo number_format($bookSCount); ?> items</p>
                    </a>
                </div>


                <div class="col-md-3">
                    <a href="products/browse.php?cat=equip" class="category-block h-100 d-block">
                        <div class="icon-circle"><i class="bi bi-heart-pulse"></i></div>
                        <h5>Equipment</h5>
                        <p><?php echo number_format($equipCount); ?> items</p>
                    </a>
                </div>


                <div class="col-md-3">
                    <a href="products/browse.php?cat=train" class="category-block h-100 d-block">
                        <div class="icon-circle"><i class="bi bi-mortarboard"></i></div>
                        <h5>Training Materials</h5>
                        <p><?php echo number_format($trainCount); ?> items</p>
                    </a>
                </div>


                <div class="col-md-3">
                    <a href="products/browse.php?cat=lab" class="category-block h-100 d-block">
                        <div class="icon-circle"><i class="bi bi-droplet"></i></div>
                        <h5>Lab Supplies</h5>
                        <p><?php echo number_format($labCount); ?> items</p>
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!--FEATURED PRODUCTS-->
    <div class="bg-white" style="padding: 50px 0;">
        <section class="container mt-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: var(--dark-teal); font-weight: bold;">Featured products</h2>
                <a href="products/browse.php" class="text-decoration-none" style="color: var(--primary-teal);">View All →</a>
            </div>

            <div class="row g-4">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-md-4">
                        <div class="product-card shadow-sm h-100">

                            <div class="product-img-container">
                                <span class="badge-featured">Featured</span>
                                <img src="uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            </div>

                            <div class="p-3">
                                <h6 class="product-title"><?php echo $product['title']; ?></h6>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="product-price">R<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="badge-condition"><?php echo $product['p_condition']; ?></span>
                                </div>

                                <div class="mt-3 pt-2 border-top d-flex justify-content-between text-muted small">
                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        <?php echo htmlspecialchars($product['seller_location'] ?? 'South Africa'); ?>
                                    </span>
                                    <span><?php echo htmlspecialchars($product['seller_name'] ?? ''); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>



    <!--WHY CHOOSE US?-->
    <div class="bg-ice-default">
        <section class="container py-5 text-center">
            <h2 class="mb-5" style="color: var(--dark-teal); font-weight: 700;">Why Choose MedMarket?</h2>

            <div class="row g-4">
                <div class="col-md-3">
                    <div class="feature-icon-wrap mb-3"><i class="bi bi-shield-lock"></i></div>
                    <h5 class="fw-bold">Secure Platform</h5>
                    <p class="text-muted small">Your Transactions are protected with bank-level security</p>
                </div>

                <div class="col-md-3">
                    <div class="feature-icon-wrap mb-3"><i class="bi bi-check-circle"></i></div>
                    <h5 class="fw-bold">Verified Users</h5>
                    <p class="text-muted small">All Sellers are verified medical professionals or students</p>
                </div>

                <div class="col-md-3">
                    <div class="feature-icon-wrap mb-3"><i class="bi bi-people"></i></div>
                    <h5 class="fw-bold">Community Trusted</h5>
                    <p class="text-muted small">Join thousands of satisfied medical professionals</p>
                </div>

                <div class="col-md-3">
                    <div class="feature-icon-wrap mb-3"><i class="bi bi-graph-up-arrow"></i></div>
                    <h5 class="fw-bold">Best Prices</h5>
                    <p class="text-muted small">Save up to 70% on medical books and equipment</p>
                </div>
            </div>
        </section>


        <!--CALL TO ACTION-->

        <section class="cta-section py-5 mt-5" style="background-color: #57A0A7; color: white;">
            <div class="container py-4">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h2 class="display-6 fw-bold mb-3">Ready to buy or sell?</h2>
                        <p class="lead opacity-75">Create your free account in minutes. Join over 14,000 verified healthcare professionals already using MedMarket.</p>
                    </div>
                    <div class="col-md-5 d-flex gap-3 justify-content-md-end mt-4 mt-md-0">
                        <a href="auth/register.php" class="btn-cta-white">Create Free Account <i class="bi bi-arrow-right"></i></a>
                        <a href="products/browse.php" class="btn-cta-outline">Browse Listings</a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>