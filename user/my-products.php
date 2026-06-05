<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base = '/medical-c2c-platform';
$userId = $_SESSION['user_id'];

// Fetch user
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Filters
$search   = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = $_GET['sort']          ?? 'newest';

$where  = ["seller_id = ?"];
$params = [$userId];

if ($search) {
    $where[]  = "title LIKE ?";
    $params[] = "%$search%";
}
if ($category) {
    $where[]  = "category = ?";
    $params[] = $category;
}

$orderSQL = match ($sort) {
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'oldest'     => 'created_at ASC',
    default      => 'created_at DESC',
};

$whereSQL = implode(' AND ', $where);
$prodStmt = $pdo->prepare("SELECT * FROM products WHERE $whereSQL ORDER BY $orderSQL");
$prodStmt->execute($params);
$products = $prodStmt->fetchAll();

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
    <style>
        body {
            background: #f0fbfc;
            font-family: 'Poppins', sans-serif;
        }

        /* Page Header */
        .page-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-top h3 {
            font-weight: 800;
            color: #036873;
            margin: 0;
        }

        .page-top p {
            font-size: 0.85rem;
            color: #aaa;
            margin: 4px 0 0;
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #036873;
            color: white;
            padding: 10px 20px;
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

        /* Toolbar */
        .toolbar {
            background: white;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(3, 104, 115, 0.06);
            flex-wrap: wrap;
        }

        .toolbar-search {
            flex: 1;
            position: relative;
            min-width: 200px;
        }

        .toolbar-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #8bbfc4;
        }

        .toolbar-search input {
            width: 100%;
            padding: 9px 14px 9px 36px;
            border: 1.5px solid #e0f4f6;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.86rem;
            outline: none;
            color: #333;
        }

        .toolbar-search input:focus {
            border-color: #036873;
        }

        .toolbar-select {
            padding: 8px 14px;
            border: 1.5px solid #e0f4f6;
            border-radius: 50px;
            font-size: 0.86rem;
            font-family: 'Poppins', sans-serif;
            color: #333;
            background: white;
            outline: none;
            cursor: pointer;
        }

        .toolbar-select:focus {
            border-color: #036873;
        }

        .listing-count {
            font-size: 0.82rem;
            color: #aaa;
            white-space: nowrap;
        }

        /* Stats Row */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-mini {
            background: white;
            border-radius: 14px;
            padding: 16px 20px;
            box-shadow: 0 2px 12px rgba(3, 104, 115, 0.06);
        }

        .stat-mini-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: #036873;
            margin: 0;
        }

        .stat-mini-label {
            font-size: 0.75rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 3px 0 0;
        }

        /* Product Table/Grid toggle */
        .view-toggle {
            display: flex;
            gap: 4px;
        }

        .view-btn {
            width: 32px;
            height: 32px;
            border: 1.5px solid #e0f4f6;
            border-radius: 8px;
            background: white;
            color: #aaa;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .view-btn.active,
        .view-btn:hover {
            border-color: #036873;
            color: #036873;
            background: #f0fbfc;
        }

        /* Grid View */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .my-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e8f5f7;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .my-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(3, 104, 115, 0.1);
        }

        .my-card-img {
            height: 150px;
            overflow: hidden;
            position: relative;
        }

        .my-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .my-card-status {
            position: absolute;
            top: 8px;
            left: 8px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .status-active {
            background: #d4f8e8;
            color: #0a7c42;
        }

        .status-sold {
            background: #ffe4e4;
            color: #c0392b;
        }

        .my-card-body {
            padding: 12px 14px;
        }

        .my-card-title {
            font-weight: 600;
            font-size: 0.88rem;
            color: #1a1a1a;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .my-card-price {
            font-weight: 800;
            font-size: 1rem;
            color: #036873;
            margin-bottom: 10px;
        }

        .my-card-meta {
            font-size: 0.75rem;
            color: #aaa;
            margin-bottom: 10px;
            display: flex;
            gap: 10px;
        }

        .my-card-actions {
            display: flex;
            gap: 6px;
        }

        .btn-action {
            flex: 1;
            padding: 6px;
            text-align: center;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
            border: 1.5px solid;
            cursor: pointer;
        }

        .btn-action-edit {
            color: #036873;
            border-color: #c8edf0;
            background: white;
        }

        .btn-action-edit:hover {
            background: #036873;
            color: white;
            border-color: #036873;
        }

        .btn-action-view {
            color: #888;
            border-color: #e0e0e0;
            background: white;
        }

        .btn-action-view:hover {
            background: #888;
            color: white;
            border-color: #888;
        }

        .btn-action-delete {
            color: #e74c3c;
            border-color: #ffdddd;
            background: white;
        }

        .btn-action-delete:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }

        /* List View */
        .products-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .list-row {
            background: white;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid #e8f5f7;
            transition: box-shadow 0.2s;
        }

        .list-row:hover {
            box-shadow: 0 4px 16px rgba(3, 104, 115, 0.08);
        }

        .list-img {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .list-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .list-info {
            flex: 1;
            min-width: 0;
        }

        .list-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a1a1a;
            margin: 0 0 3px;
        }

        .list-meta {
            font-size: 0.78rem;
            color: #aaa;
            margin: 0;
        }

        .list-price {
            font-weight: 800;
            font-size: 1rem;
            color: #036873;
            flex-shrink: 0;
        }

        .list-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        /* Empty */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
        }

        .empty-state i {
            font-size: 3rem;
            color: #c8edf0;
            margin-bottom: 16px;
            display: block;
        }

        .empty-state h5 {
            color: #555;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        @media (max-width:992px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width:600px) {
            .products-grid {
                grid-template-columns: 1fr;
            }

            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .dash-nav-links {
                display: none;
            }
        }
    </style>
</head>

<body Style="font-family: 'Poppins', DM SERIF;">

    <!-- Navbar -->
    <?php include '../includes/navbar-dashboard.php'; ?>

    <div class="container py-4" style="max-width:1100px;">

        <?php if ($success === '1'): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
                <i class="bi bi-check-circle-fill"></i> Product listed successfully!
            </div>
        <?php endif; ?>

        <!-- Page Top -->
        <div class="page-top">
            <div>
                <h3>My Listings</h3>
                <p>Manage your products and equipment on MedMarket</p>
            </div>
            <a href="<?php echo $base; ?>/products/add-product.php" class="btn-add">
                <i class="bi bi-plus-lg"></i> Add Listing
            </a>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-mini">
                <p class="stat-mini-value"><?php echo count($products); ?></p>
                <p class="stat-mini-label">Total Listings</p>
            </div>
            <div class="stat-mini">
                <p class="stat-mini-value"><?php echo count(array_filter($products, fn($p) => ($p['status'] ?? 'active') === 'active')); ?></p>
                <p class="stat-mini-label">Active</p>
            </div>
            <div class="stat-mini">
                <p class="stat-mini-value"><?php echo count(array_filter($products, fn($p) => ($p['status'] ?? '') === 'sold')); ?></p>
                <p class="stat-mini-label">Sold</p>
            </div>
            <div class="stat-mini">
                <p class="stat-mini-value">R<?php
                                            $total = array_sum(array_column($products, 'price'));
                                            echo $total >= 1000 ? round($total / 1000) . 'K' : number_format($total);
                                            ?></p>
                <p class="stat-mini-label">Total Value</p>
            </div>
        </div>

        <!-- Toolbar -->
        <form method="GET" action="my-products.php">
            <div class="toolbar">
                <div class="toolbar-search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" placeholder="Search your listings..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select name="category" class="toolbar-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach (['Medical Books', 'Equipment', 'Training Materials', 'Lab Supplies'] as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="sort" class="toolbar-select" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort === 'newest'    ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort === 'oldest'    ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low–High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High–Low</option>
                </select>
                <div class="view-toggle">
                    <button type="button" class="view-btn active" id="grid-btn" onclick="setView('grid')" title="Grid view">
                        <i class="bi bi-grid"></i>
                    </button>
                    <button type="button" class="view-btn" id="list-btn" onclick="setView('list')" title="List view">
                        <i class="bi bi-list-ul"></i>
                    </button>
                </div>
                <span class="listing-count"><?php echo count($products); ?> listing<?php echo count($products) !== 1 ? 's' : ''; ?></span>
            </div>
        </form>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="bi bi-box-seam"></i>
                <h5>No listings yet</h5>
                <p>Start selling your medical books, equipment, and supplies.</p>
                <a href="<?php echo $base; ?>/products/add-product.php" class="btn-add">
                    <i class="bi bi-plus-lg"></i> Add Your First Listing
                </a>
            </div>

        <?php else: ?>

            <!-- Grid View -->
            <div class="products-grid" id="grid-view">
                <?php foreach ($products as $product):
                    $status = $product['status'] ?? 'active';
                ?>
                    <div class="my-card">
                        <div class="my-card-img">
                            <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>"
                                onerror="this.src='<?php echo $base; ?>/assets/images/placeholder.jpg'"
                                alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <span class="my-card-status <?php echo $status === 'sold' ? 'status-sold' : 'status-active'; ?>">
                                <?php echo $status === 'sold' ? 'Sold' : 'Active'; ?>
                            </span>
                        </div>
                        <div class="my-card-body">
                            <div class="my-card-title"><?php echo htmlspecialchars($product['title']); ?></div>
                            <div class="my-card-price">R<?php echo number_format($product['price'], 2); ?></div>
                            <div class="my-card-meta">
                                <span><i class="bi bi-tag"></i> <?php echo htmlspecialchars($product['category']); ?></span>
                                <span><i class="bi bi-circle-half"></i> <?php echo htmlspecialchars($product['p_condition']); ?></span>
                            </div>
                            <div class="my-card-actions">
                                <a href="<?php echo $base; ?>/products/edit-product.php?id=<?php echo $product['id']; ?>" class="btn-action btn-action-edit">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="<?php echo $base; ?>/products/view-product.php?id=<?php echo $product['id']; ?>" class="btn-action btn-action-view">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form method="POST" action="<?php echo $base; ?>/products/delete-product.php" style="flex:0;"
                                    onsubmit="return confirm('Delete this listing?')">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn-action btn-action-delete" style="width:100%;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- List View -->
            <div class="products-list" id="list-view" style="display:none;">
                <?php foreach ($products as $product):
                    $status = $product['status'] ?? 'active';
                ?>
                    <div class="list-row">
                        <div class="list-img">
                            <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>"
                                onerror="this.src='<?php echo $base; ?>/assets/images/placeholder.jpg'"
                                alt="<?php echo htmlspecialchars($product['title']); ?>">
                        </div>
                        <div class="list-info">
                            <p class="list-title"><?php echo htmlspecialchars($product['title']); ?></p>
                            <p class="list-meta">
                                <?php echo htmlspecialchars($product['category']); ?> &bull;
                                <?php echo htmlspecialchars($product['p_condition']); ?> &bull;
                                <span class="<?php echo $status === 'sold' ? 'text-danger' : 'text-success'; ?> fw-semibold">
                                    <?php echo $status === 'sold' ? 'Sold' : 'Active'; ?>
                                </span>
                            </p>
                        </div>
                        <div class="list-price">R<?php echo number_format($product['price'], 2); ?></div>
                        <div class="list-actions">
                            <a href="<?php echo $base; ?>/products/edit-product.php?id=<?php echo $product['id']; ?>" class="btn-action btn-action-edit">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="<?php echo $base; ?>/products/view-product.php?id=<?php echo $product['id']; ?>" class="btn-action btn-action-view">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="POST" action="<?php echo $base; ?>/products/delete-product.php"
                                onsubmit="return confirm('Delete this listing?')" style="margin:0;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn-action btn-action-delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <script>
        function setView(view) {
            const grid = document.getElementById('grid-view');
            const list = document.getElementById('list-view');
            const gridBtn = document.getElementById('grid-btn');
            const listBtn = document.getElementById('list-btn');

            if (view === 'grid') {
                grid.style.display = 'grid';
                list.style.display = 'none';
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
                localStorage.setItem('myproducts_view', 'grid');
            } else {
                grid.style.display = 'none';
                list.style.display = 'flex';
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
                localStorage.setItem('myproducts_view', 'list');
            }
        }

        const savedView = localStorage.getItem('myproducts_view') || 'grid';
        setView(savedView);
    </script>

</body>

</html>