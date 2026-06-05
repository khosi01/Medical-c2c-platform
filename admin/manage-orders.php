<?php
require_once __DIR__ . '/admin-auth-check.php';
require_once __DIR__ . '/../config/db.php';

$adminName   = $_SESSION['user_name'] ?? 'Admin';
$adminRole   = $_SESSION['user_role'] ?? 'admin';
$currentPage = 'orders';
$pageTitle   = 'Manage Orders';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update') {
    if (!canAccess('manage-orders')) die('Access denied.');
    $orderId = (int)$_POST['order_id'];
    $status  = $_POST['status'];
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $orderId]);
    logAction($pdo, $adminId, "Updated order #$orderId status to: $status");
    $msg = "<div class='alert alert-success'>Order status updated.</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    if (!canAccess('manage-orders')) die('Access denied.');
    $orderId = (int)$_POST['order_id'];
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
    logAction($pdo, $adminId, "Deleted order #$orderId");
    $msg = "<div class='alert alert-success'>Order deleted.</div>";
}

$search    = trim($_GET['q']      ?? '');
$filter    = trim($_GET['status'] ?? '');
$where     = ['1=1'];
$params    = [];

if ($search) {
    $where[]  = "(u.full_name LIKE ? OR p.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter) {
    $where[]  = "o.status = ?";
    $params[] = $filter;
}

$stmt = $pdo->prepare("
    SELECT o.id, o.status, o.order_date,
           u.full_name AS buyer_name, u.email AS buyer_email,
           p.title AS product_title, p.price AS product_price
    FROM orders o
    JOIN users    u ON u.id = o.buyer_id
    JOIN products p ON p.id = o.product_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY o.order_date DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$totalOrders    = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$deliveredOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
$cancelledOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Orders | MedMarket Admin</title>
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
                    <h1>Manage Orders</h1>
                    <p><?php echo count($orders); ?> orders found</p>
                </div>
            </div>

            <?php if ($msg) echo $msg; ?>

            <!-- Metric mini cards -->
            <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px;">
                <?php
                $cards = [
                    ['Total',     $totalOrders,     'bi-bag-check',    '#036873'],
                    ['Pending',   $pendingOrders,   'bi-clock',        '#9a6800'],
                    ['Delivered', $deliveredOrders, 'bi-check-circle', '#0f6e56'],
                    ['Cancelled', $cancelledOrders, 'bi-x-circle',     '#c0392b'],
                ];
                foreach ($cards as [$label, $val, $icon, $color]): ?>
                    <div style="background:white; border:1px solid #ddeaec; border-radius:12px; padding:18px 20px; display:flex; align-items:center; justify-content:space-between;">
                        <div>
                            <div style="font-size:.75rem; font-weight:600; color:#5a8087; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px;"><?php echo $label; ?></div>
                            <div style="font-size:1.6rem; font-weight:700; color:#0d2e32;"><?php echo number_format($val); ?></div>
                        </div>
                        <div style="width:44px;height:44px;border-radius:10px;background:<?php echo $color; ?>18;display:flex;align-items:center;justify-content:center;color:<?php echo $color; ?>;font-size:1.2rem;">
                            <i class="bi <?php echo $icon; ?>"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>All Orders</h2>
                </div>
                <div class="table-controls">
                    <form method="GET" style="display:contents;">
                        <div class="search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" name="q" placeholder="Search buyer or product…" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select name="status" class="f-select" onchange="this.form.submit()">
                            <option value="">All status</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="delivered" <?php echo $filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn-outline"><i class="bi bi-funnel"></i> Filter</button>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Buyer</th>
                            <th>Price</th>
                            <th>Date</th>
                            <th>Status</th>
                            <?php if (canAccess('manage-orders')): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr class="empty-row">
                                <td colspan="7">No orders found.</td>
                            </tr>
                            <?php else: foreach ($orders as $o):
                                $status = strtolower($o['status'] ?? 'pending');
                                $badgeClass = match ($status) {
                                    'delivered' => 'badge-success',
                                    'cancelled' => 'badge-danger',
                                    default     => 'badge-warning',
                                }; ?>
                                <tr>
                                    <td style="color:var(--muted);font-weight:600;">#<?php echo str_pad($o['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($o['product_title']); ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($o['buyer_name']); ?></div>
                                        <div style="font-size:.75rem;color:var(--muted);"><?php echo htmlspecialchars($o['buyer_email']); ?></div>
                                    </td>
                                    <td style="font-weight:700;color:#036873;">R<?php echo number_format($o['product_price'], 2); ?></td>
                                    <td style="color:var(--muted);"><?php echo date('d M Y', strtotime($o['order_date'])); ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span></td>
                                    <?php if (canAccess('manage-orders')): ?>
                                        <td style="display:flex;gap:6px;align-items:center;">
                                            <button class="btn-outline btn-sm" onclick="openEdit(<?php echo $o['id']; ?>,'<?php echo $status; ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn-danger btn-sm" onclick="confirmDelete(<?php echo $o['id']; ?>)">
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

    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('editModal')"><i class="bi bi-x-lg"></i></button>
            <h3>Update Order Status</h3>
            <p>Change the status of this order.</p>
            <form method="POST">
                <input type="hidden" name="_action" value="update">
                <input type="hidden" name="order_id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
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
        <input type="hidden" name="order_id" id="delete_id">
    </form>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function openEdit(id, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_status').value = status;
            openModal('editModal');
        }

        function confirmDelete(id) {
            if (confirm('Delete order #' + id + '? This cannot be undone.')) {
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