<?php
session_start();
require_once '../config/db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$base = '/medical-c2c-platform';

$search     = trim($_GET['q']          ?? '');
$category   = trim($_GET['category']   ?? '');
$condition  = $_GET['condition']       ?? [];
$profession = trim($_GET['profession'] ?? '');
$location   = trim($_GET['location']   ?? '');
$minPrice   = $_GET['min_price']       ?? 0;
$maxPrice   = $_GET['max_price']       ?? 10000;
$sort       = $_GET['sort']            ?? 'newest';

if (!is_array($condition)) $condition = [];

$where  = ["1=1"];
$params = [];

if ($search) {
    $where[]  = "(p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $where[]  = "p.category = ?";
    $params[] = $category;
}
if (!empty($condition)) {
    $placeholders = implode(',', array_fill(0, count($condition), '?'));
    $where[]  = "p.p_condition IN ($placeholders)";
    $params   = array_merge($params, $condition);
}
if ($profession) {
    $where[]  = "p.profession = ?";
    $params[] = $profession;
}
if ($location) {
    $where[]  = "u.location LIKE ?";
    $params[] = "%$location%";
}
$where[]  = "p.price >= ?";
$params[] = $minPrice;
$where[]  = "p.price <= ?";
$params[] = $maxPrice;

$whereSQL = implode(' AND ', $where);
$orderSQL = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'oldest'     => 'p.created_at ASC',
    default      => 'p.created_at DESC',
};

$stmt = $pdo->prepare("
    SELECT p.*, u.full_name AS seller_name, u.location AS seller_location
    FROM products p JOIN users u ON p.seller_id = u.id
    WHERE $whereSQL ORDER BY $orderSQL
");
$stmt->execute($params);
$products   = $stmt->fetchAll();
$totalCount = count($products);

$categories = [
    'Medical Books'      => $pdo->query("SELECT COUNT(*) FROM products WHERE category='Medical Books'")->fetchColumn(),
    'Equipment'          => $pdo->query("SELECT COUNT(*) FROM products WHERE category='Equipment'")->fetchColumn(),
    'Training Materials' => $pdo->query("SELECT COUNT(*) FROM products WHERE category='Training Materials'")->fetchColumn(),
    'Lab Supplies'       => $pdo->query("SELECT COUNT(*) FROM products WHERE category='Lab Supplies'")->fetchColumn(),
];

$activeTags = [];
if ($category)          $activeTags['category']  = $category;
if (!empty($condition)) $activeTags['condition']  = implode(', ', $condition);
if ($profession)        $activeTags['profession'] = $profession;
if ($location)          $activeTags['location']   = $location;
if ($minPrice > 0 || $maxPrice < 10000) $activeTags['price'] = "R$minPrice – R$maxPrice";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>../assets/css/style.css">
</head>

<body Style="font-family: 'Poppins', DM SERIF;">



    <?php include '../includes/navbar-browse.php'; ?>

    <div style="padding: 12px 24px; font-size: 0.82rem; color: #aaa;">
        <a href="<?php echo $base; ?>/index.php" style="color:#aaa; text-decoration:none;">Home</a>
        <span style="margin: 0 6px;">/</span>

        <?php if ($category): ?>
            <a href="<?php echo $base; ?>/products/browse.php" style="color:#aaa; text-decoration:none;">Browse</a>
            <span style="margin: 0 6px;">/</span>
            <span style="color:#555; font-weight:600;"><?php echo htmlspecialchars($category); ?></span>

        <?php elseif ($search): ?>
            <a href="<?php echo $base; ?>/products/browse.php" style="color:#aaa; text-decoration:none;">Browse</a>
            <span style="margin: 0 6px;">/</span>
            <span style="color:#555; font-weight:600;">Search: "<?php echo htmlspecialchars($search); ?>"</span>

        <?php else: ?>
            <span style="color:#555; font-weight:600;">Browse</span>
        <?php endif; ?>
    </div>

    <div class="browse-layout">

        <aside class="filter-sidebar">
            <form method="GET" action="browse.php" id="filter-form">
                <?php if ($search): ?><input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                <div class="filter-header">
                    <span>Filters</span>
                    <a href="browse.php" class="clear-btn">Clear all</a>
                </div>

                <div class="filter-group">
                    <div class="filter-group-title">Category</div>
                    <?php
                    $catIcons = ['Medical Books' => 'bi-book', 'Equipment' => 'bi-heart-pulse', 'Training Materials' => 'bi-mortarboard', 'Lab Supplies' => 'bi-droplet'];
                    foreach ($categories as $cat => $count):
                        $isActive = $category === $cat;
                        $catUrl   = http_build_query(array_merge($_GET, ['category' => $cat]));
                    ?>
                        <a href="browse.php?<?php echo $catUrl; ?>" class="cat-item <?php echo $isActive ? 'active' : ''; ?>">
                            <span class="cat-left"><i class="bi <?php echo $catIcons[$cat] ?? 'bi-grid'; ?>"></i><?php echo htmlspecialchars($cat); ?></span>
                            <span class="cat-count"><?php echo $count; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <div class="filter-group-title">Price Range</div>
                    <div class="price-inputs">
                        <div class="price-box"><span>R</span><input type="number" name="min_price" id="min-price" value="<?php echo $minPrice; ?>" min="0" max="10000"></div>
                        <div class="price-box"><span>R</span><input type="number" name="max_price" id="max-price" value="<?php echo $maxPrice; ?>" min="0" max="10000"></div>
                    </div>
                    <input type="range" id="price-slider" min="0" max="10000" value="<?php echo $maxPrice; ?>">
                </div>

                <div class="filter-group">
                    <div class="filter-group-title">Condition</div>
                    <?php foreach (['New', 'Like New', 'Good', 'Fair'] as $cond): ?>
                        <label class="check-label">
                            <input type="checkbox" name="condition[]" value="<?php echo $cond; ?>" <?php echo in_array($cond, $condition) ? 'checked' : ''; ?>>
                            <?php echo $cond; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <div class="filter-group-title">Location</div>
                    <input type="text" name="location" class="filter-input" placeholder="e.g. Johannesburg" value="<?php echo htmlspecialchars($location); ?>">
                </div>

                <div class="filter-group">
                    <div class="filter-group-title">Profession</div>
                    <select name="profession" class="filter-select">
                        <option value="">All Professions</option>
                        <?php foreach (['Doctor/Specialist', 'Nurse', 'Medical Student', 'Other Healthcare Professional'] as $prof): ?>
                            <option value="<?php echo $prof; ?>" <?php echo $profession === $prof ? 'selected' : ''; ?>><?php echo $prof; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <div class="filter-group-title">Product Type</div>
                    <select name="product_type" class="filter-select">
                        <option value="">All Types</option>
                        <option>Textbook</option>
                        <option>Device</option>
                        <option>Consumable</option>
                        <option>Apparel</option>
                        <option>Software</option>
                    </select>
                </div>

                <button type="submit" class="apply-btn">Apply Filters</button>
            </form>
        </aside>

        <main class="browse-main">
            <div class="verified-banner">
                <div>
                    <h6>Verified Sellers Program</h6>
                    <p>Browse listings from credential-verified healthcare professionals.</p>
                </div>
                <a href="#" class="btn-learn">Learn More</a>
            </div>

            <div class="results-header">
                <div>
                    <h2 class="results-title">Products</h2>
                    <p class="results-count"><?php echo number_format($totalCount); ?> listings</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size:0.85rem;color:#888;">Sort by</span>
                    <select class="sort-select" onchange="const u=new URL(window.location);u.searchParams.set('sort',this.value);window.location=u;">
                        <option value="newest" <?php echo $sort === 'newest'    ? 'selected' : ''; ?>>Most Recent</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="oldest" <?php echo $sort === 'oldest'    ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($activeTags)): ?>
                <div class="active-tags">
                    <?php foreach ($activeTags as $key => $label): ?>
                        <span class="active-tag"><?php echo htmlspecialchars($label); ?><button onclick="removeFilter('<?php echo $key; ?>')">×</button></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    <h5>No products found</h5>
                    <p>Try adjusting your filters or search term.</p>
                    <a href="browse.php" class="btn-sell-nav d-inline-flex mt-3">Clear Filters</a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="browse-card">
                            <div class="browse-card-img">
                                <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>"
                                    onerror="this.src='<?php echo $base; ?>/assets/images/placeholder.jpg'"
                                    alt="<?php echo htmlspecialchars($product['title']); ?>">
                                <button class="wishlist-btn"><i class="bi bi-heart"></i></button>
                            </div>
                            <div class="browse-card-body">
                                <div class="browse-card-title"><?php echo htmlspecialchars($product['title']); ?></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="browse-card-price">R<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="cond-badge"><?php echo htmlspecialchars($product['p_condition']); ?></span>
                                </div>
                                <div class="browse-card-meta">
                                    <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($product['seller_location'] ?? 'South Africa'); ?></span>
                                    <span><?php echo htmlspecialchars($product['seller_name'] ?? ''); ?></span>
                                </div>
                            </div>
                            <div class="browse-card-footer">
                                <a href="<?php echo $base; ?>/products/view-product.php?id=<?php echo $product['id']; ?>" class="btn-view-details">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        const slider = document.getElementById('price-slider');
        const maxInput = document.getElementById('max-price');
        if (slider && maxInput) {
            slider.addEventListener('input', () => {
                maxInput.value = slider.value;
            });
            maxInput.addEventListener('input', () => {
                slider.value = maxInput.value;
            });
        }

        function removeFilter(key) {
            const url = new URL(window.location);
            url.searchParams.delete(key === 'condition' ? 'condition[]' : key);
            window.location = url;
        }
    </script>

</body>

</html>