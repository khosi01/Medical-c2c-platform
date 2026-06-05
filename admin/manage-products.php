<?php
require_once __DIR__ . '/admin-auth-check.php';
require_once __DIR__ . '/../config/db.php';

$adminName   = $_SESSION['user_name'] ?? 'Admin';
$adminRole   = $_SESSION['user_role'] ?? 'admin';
$currentPage = 'products';
$pageTitle   = 'Manage Products';
$adminId     = $_SESSION['user_id'];

function logAction($pdo, $adminId, $action)
{
    try {
        $pdo->prepare("INSERT INTO report_logs (user_id, action, created_at) VALUES (?, ?, NOW())")
            ->execute([$adminId, $action]);
    } catch (Exception $e) {
    }
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'create') {
    if (!canAccess('manage-products')) die('Access denied.');
    $title       = trim($_POST['title']);
    $category    = trim($_POST['category']);
    $condition   = trim($_POST['p_condition']);
    $price       = (float)$_POST['price'];
    $description = trim($_POST['description']);
    $status      = $_POST['status'];
    $sellerId    = (int)$_POST['seller_id'];

    $pdo->prepare("
        INSERT INTO products (seller_id, title, category, p_condition, price, description, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ")->execute([$sellerId, $title, $category, $condition, $price, $description, $status]);

    logAction($pdo, $adminId, "Created product: $title");
    $msg = "<div class='alert alert-success'>Product created successfully.</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update') {
    if (!canAccess('manage-products')) die('Access denied.');
    $productId   = (int)$_POST['product_id'];
    $title       = trim($_POST['title']);
    $category    = trim($_POST['category']);
    $condition   = trim($_POST['p_condition']);
    $price       = (float)$_POST['price'];
    $status      = $_POST['status'];

    $pdo->prepare("
        UPDATE products SET title = ?, category = ?, p_condition = ?, price = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ")->execute([$title, $category, $condition, $price, $status, $productId]);

    logAction($pdo, $adminId, "Updated product ID $productId: $title");
    $msg = "<div class='alert alert-success'>Product updated.</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    if (!canAccess('manage-products')) die('Access denied.');
    $productId = (int)$_POST['product_id'];
    $row = $pdo->prepare("SELECT title FROM products WHERE id = ?");
    $row->execute([$productId]);
    $title = $row->fetchColumn();
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
    logAction($pdo, $adminId, "Deleted product ID $productId: $title");
    $msg = "<div class='alert alert-success'>Product deleted.</div>";
}


$search   = trim($_GET['q']        ?? '');
$filter   = trim($_GET['category'] ?? '');
$statFilt = trim($_GET['status']   ?? '');
$where    = ['1=1'];
$params   = [];

if ($search) {
    $where[]  = "(p.title LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter) {
    $where[]  = "p.category = ?";
    $params[] = $filter;
}
if ($statFilt) {
    $where[]  = "p.status = ?";
    $params[] = $statFilt;
}

$stmt = $pdo->prepare("
    SELECT p.*, u.full_name AS seller_name
    FROM products p
    JOIN users u ON u.id = p.seller_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$totalProducts  = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$activeProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$draftProducts  = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'draft'")->fetchColumn();

$sellers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'user' ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Products | MedMarket Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin-mobile.css">
</head>

<body>

    <aside class="sidebar">
        <a href="../index.php" class="sidebar-brand"><img src="../assets/images/Logo.jpg" alt=""><span>Med<em>Market</em></span></a>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="manage-orders.php" class="nav-item <?php echo $currentPage === 'orders' ? 'active' : ''; ?>"><i class="bi bi-bag-check"></i> Orders</a>
            <a href="manage-products.php" class="nav-item <?php echo $currentPage === 'products' ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Products</a>
            <a href="manage-users.php" class="nav-item <?php echo $currentPage === 'users' ? 'active' : ''; ?>"><i class="bi bi-people"></i> Users</a>
            <div class="nav-section-label" style="margin-top:8px;">System</div>
            <a href="report-logs.php" class="nav-item <?php echo $currentPage === 'logs' ? 'active' : ''; ?>"><i class="bi bi-journal-text"></i> Report Logs</a>
            <a href="roles.php" class="nav-item <?php echo $currentPage === 'roles' ? 'active' : ''; ?>"><i class="bi bi-shield-lock"></i> Roles</a>
            <a href="../index.php" class="nav-item"><i class="bi bi-house"></i> Back to Site</a>
        </nav>
        <div class="sidebar-footer"><strong>MedMarket Admin</strong> &copy; <?php echo date('Y'); ?></div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title"><?php echo $pageTitle; ?> <span><?php echo date('l, d F Y'); ?></span></div>
            <div class="topbar-right">
                <button class="notif-btn"><i class="bi bi-bell"></i><span class="notif-badge"></span></button>
                <div class="admin-avatar" onclick="this.querySelector('.dropdown-menu').classList.toggle('show')">
                    <div class="avatar-circle"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
                    <div class="admin-info"><strong><?php echo htmlspecialchars($adminName); ?></strong><small><?php echo htmlspecialchars($adminRole); ?></small></div>
                    <i class="bi bi-chevron-down" style="font-size:.8rem;color:#5a8087;"></i>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="bi bi-person"></i> Profile</a>
                        <a href="../auth/logout.php" class="danger"><i class="bi bi-box-arrow-right"></i> Sign out</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">

            <div class="page-header">
                <div>
                    <h1>Manage Products</h1>
                    <p><?php echo count($products); ?> products found</p>
                </div>
                <?php if (canAccess('manage-products')): ?>
                    <button class="btn-teal" onclick="openModal('createModal')"><i class="bi bi-plus-lg"></i> Add Product</button>
                <?php endif; ?>
            </div>

            <?php if ($msg) echo $msg; ?>

            <!-- Metric cards -->
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px;">
                <?php
                $cards = [
                    ['Total Products', $totalProducts,  'bi-box-seam',    '#036873'],
                    ['Active',         $activeProducts, 'bi-check-circle', '#0f6e56'],
                    ['Drafts',         $draftProducts,  'bi-pencil-square', '#9a6800'],
                ];
                foreach ($cards as [$label, $val, $icon, $color]): ?>
                    <div style="background:white;border:1px solid #ddeaec;border-radius:12px;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <div style="font-size:.75rem;font-weight:600;color:#5a8087;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;"><?php echo $label; ?></div>
                            <div style="font-size:1.6rem;font-weight:700;color:#0d2e32;"><?php echo number_format($val); ?></div>
                        </div>
                        <div style="width:44px;height:44px;border-radius:10px;background:<?php echo $color; ?>18;display:flex;align-items:center;justify-content:center;color:<?php echo $color; ?>;font-size:1.2rem;">
                            <i class="bi <?php echo $icon; ?>"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>All Products</h2>
                </div>
                <div class="table-controls">
                    <form method="GET" style="display:contents;">
                        <div class="search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" name="q" placeholder="Search title or seller…" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select name="category" class="f-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach (['Medical Books', 'Equipment', 'Training Materials', 'Lab Supplies'] as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $filter === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" class="f-select" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $statFilt === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="draft" <?php echo $statFilt === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                        <button type="submit" class="btn-outline"><i class="bi bi-funnel"></i> Filter</button>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Condition</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Listed</th>
                            <?php if (canAccess('manage-products')): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr class="empty-row">
                                <td colspan="9">No products found.</td>
                            </tr>
                            <?php else: foreach ($products as $p):
                                $statusClass = $p['status'] === 'active' ? 'badge-success' : 'badge-warning';
                            ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($p['image_path'])): ?>
                                            <img src="../uploads/products/<?php echo htmlspecialchars($p['image_path']); ?>"
                                                style="width:48px;height:48px;object-fit:cover;border-radius:8px;"
                                                onerror="this.style.display='none'">
                                        <?php else: ?>
                                            <div style="width:48px;height:48px;background:#e8f6f7;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#036873;">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:600;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?php echo htmlspecialchars($p['title']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['seller_name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['category']); ?></td>
                                    <td><?php echo htmlspecialchars($p['p_condition']); ?></td>
                                    <td style="font-weight:700;color:#036873;">R<?php echo number_format($p['price'], 2); ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                                    <td style="color:var(--muted);"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                                    <?php if (canAccess('manage-products')): ?>
                                        <td style="display:flex;gap:6px;align-items:center;">
                                            <button class="btn-outline btn-sm" onclick='openEdit(<?php echo json_encode($p); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn-danger btn-sm" onclick="confirmDelete(<?php echo $p['id']; ?>,'<?php echo htmlspecialchars($p['title']); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CREATE MODAL -->
    <div class="modal-overlay" id="createModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('createModal')"><i class="bi bi-x-lg"></i></button>
            <h3>Add New Product</h3>
            <p>Create a product listing manually.</p>
            <form method="POST">
                <input type="hidden" name="_action" value="create">
                <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Seller</label>
                    <select name="seller_id" class="form-select" required>
                        <option value="">Select seller</option>
                        <?php foreach ($sellers as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <?php foreach (['Medical Books', 'Equipment', 'Training Materials', 'Lab Supplies'] as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Condition</label>
                    <select name="p_condition" class="form-select" required>
                        <?php foreach (['New', 'Like New', 'Good', 'Fair'] as $c): ?>
                            <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Price (ZAR)</label><input type="number" name="price" class="form-control" min="0" step="0.01" required></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                <div class="form-group"><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn-teal">Create Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('editModal')"><i class="bi bi-x-lg"></i></button>
            <h3>Edit Product</h3>
            <p>Update product details.</p>
            <form method="POST">
                <input type="hidden" name="_action" value="update">
                <input type="hidden" name="product_id" id="edit_id">
                <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" id="edit_title" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Category</label>
                    <select name="category" id="edit_category" class="form-select">
                        <?php foreach (['Medical Books', 'Equipment', 'Training Materials', 'Lab Supplies'] as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Condition</label>
                    <select name="p_condition" id="edit_condition" class="form-select">
                        <?php foreach (['New', 'Like New', 'Good', 'Fair'] as $c): ?>
                            <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Price (ZAR)</label><input type="number" name="price" id="edit_price" class="form-control" min="0" step="0.01" required></div>
                <div class="form-group"><label class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-teal">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE FORM -->
    <form method="POST" id="deleteForm" style="display:none;">
        <input type="hidden" name="_action" value="delete">
        <input type="hidden" name="product_id" id="delete_id">
    </form>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function openEdit(p) {
            document.getElementById('edit_id').value = p.id;
            document.getElementById('edit_title').value = p.title;
            document.getElementById('edit_category').value = p.category;
            document.getElementById('edit_condition').value = p.p_condition;
            document.getElementById('edit_price').value = p.price;
            document.getElementById('edit_status').value = p.status;
            openModal('editModal');
        }

        function confirmDelete(id, title) {
            if (confirm('Delete "' + title + '"? This cannot be undone.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        document.addEventListener('click', e => {
            if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('show');
        });
    </script>
</body>

</html>