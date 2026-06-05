<?php
require_once    __DIR__ . '/admin-auth-check.php';
require_once __DIR__ . '/../config/db.php';

$base = '/medical-c2c-platform';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminRole = $_SESSION['user_role'] ?? 'admin';
$currentPage = 'dashboard';

$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalLogs     = $pdo->query("SELECT COUNT(*) FROM report_logs")->fetchColumn();

$recentOrders = $pdo->query("
    SELECT o.id, o.order_date, o.status,
           u.full_name AS buyer_name,
           p.title     AS product_title,
           p.price     AS product_price
    FROM orders o
    JOIN users    u ON o.buyer_id   = u.id
    JOIN products p ON o.product_id = p.id
    ORDER BY o.order_date DESC
    LIMIT 10
")->fetchAll();

$catData = $pdo->query("
    SELECT category, COUNT(*) as cnt
    FROM products
    GROUP BY category
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MedMarket Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin-mobile.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --teal: #036873;
            --teal-dark: #024a52;
            --teal-light: #e8f6f7;
            --teal-mid: #c2eaed;
            --sidebar-w: 240px;
            --white: #ffffff;
            --off-white: #f4f7f8;
            --text: #0d2e32;
            --muted: #5a8087;
            --border: #ddeaec;
            --radius: 12px;
            --radius-sm: 8px;
            --font: 'DM Sans', sans-serif;
            --font-d: 'DM Serif Display', serif;
        }

        body {
            font-family: var(--font);
            background: var(--off-white);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-w);
            background: var(--teal);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            text-decoration: none;
        }

        .sidebar-brand img {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            object-fit: cover;
        }

        .sidebar-brand span {
            font-family: var(--font-d);
            font-style: italic;
            font-size: 1.15rem;
            color: white;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.45);
            padding: 12px 10px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 2px;
            transition: background 0.15s, color 0.15s;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.18);
            color: white;
        }

        .nav-item i {
            font-size: 1.05rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.4);
        }

        .sidebar-footer strong {
            display: block;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }

        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
        }

        .topbar-title span {
            color: var(--muted);
            font-weight: 400;
            font-size: 0.85rem;
            margin-left: 8px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .notif-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            font-size: 1.2rem;
            padding: 6px;
        }

        .notif-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 8px;
            height: 8px;
            background: #e74c3c;
            border-radius: 50%;
            border: 2px solid white;
        }

        .admin-avatar {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            position: relative;
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--teal-light);
            color: var(--teal);
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--teal-mid);
        }

        .admin-info {
            line-height: 1.3;
        }

        .admin-info strong {
            font-size: 0.88rem;
            display: block;
        }

        .admin-info small {
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: capitalize;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            min-width: 160px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 200;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            text-decoration: none;
            font-size: 0.88rem;
            color: var(--text);
            transition: background 0.15s;
        }

        .dropdown-menu a:hover {
            background: var(--off-white);
        }

        .dropdown-menu a.danger {
            color: #c0392b;
        }

        .content {
            padding: 32px;
            flex: 1;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-family: var(--font-d);
            font-size: 1.6rem;
            color: var(--text);
        }

        .page-header p {
            color: var(--muted);
            font-size: 0.88rem;
            margin-top: 4px;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .metric-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: box-shadow 0.2s;
        }

        .metric-card:hover {
            box-shadow: 0 4px 16px rgba(3, 104, 115, 0.08);
        }

        .metric-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--teal-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--teal);
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h2 {
            font-size: 0.97rem;
            font-weight: 600;
        }

        .card-header a {
            font-size: 0.82rem;
            color: var(--teal);
            text-decoration: none;
            font-weight: 500;
        }

        .card-header a:hover {
            text-decoration: underline;
        }

        .table-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
        }

        .search-wrap {
            position: relative;
            flex: 1;
        }

        .search-wrap i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 0.95rem;
        }

        .search-wrap input {
            width: 100%;
            padding: 8px 12px 8px 32px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.88rem;
            color: var(--text);
            outline: none;
            background: var(--off-white);
        }

        .search-wrap input:focus {
            border-color: var(--teal);
            background: white;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.85rem;
            color: var(--text);
            background: var(--off-white);
            outline: none;
            cursor: pointer;
        }

        .filter-select:focus {
            border-color: var(--teal);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .data-table th {
            padding: 11px 24px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            background: var(--off-white);
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background: #fafefe;
        }

        /* status badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-delivered {
            background: #eaf6f2;
            color: #0f6e56;
        }

        .badge-pending {
            background: #fff8e6;
            color: #9a6800;
        }

        .badge-cancelled {
            background: #fdf0f0;
            color: #c0392b;
        }

        .badge-default {
            background: var(--teal-light);
            color: var(--teal);
        }

        .chart-wrap {
            padding: 24px;
        }

        canvas {
            max-width: 100%;
        }

        .empty-row td {
            text-align: center;
            padding: 48px 24px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        @media (max-width: 1100px) {
            .metric-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main {
                margin-left: 0;
            }

            .metric-grid {
                grid-template-columns: 1fr 1fr;
            }

            .content {
                padding: 20px 16px;
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <a href="../index.php" class="sidebar-brand">
            <img src="../assets/images/Logo.jpg" alt="MedMarket">
            <span>Med<em>Market</em></span>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>

            <a href="dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a href="manage-orders.php" class="nav-item <?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
                <i class="bi bi-bag-check"></i> Orders
            </a>
            <a href="manage-products.php" class="nav-item <?php echo $currentPage === 'products' ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Products
            </a>
            <a href="manage-users.php" class="nav-item <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Users
            </a>

            <div class="nav-section-label" style="margin-top:8px;">System</div>

            <a href="report-logs.php" class="nav-item <?php echo $currentPage === 'logs' ? 'active' : ''; ?>">
                <i class="bi bi-journal-text"></i> Report Logs
            </a>
            <a href="roles.php" class="nav-item <?php echo $currentPage === 'roles' ? 'active' : ''; ?>">
                <i class="bi bi-shield-lock"></i> Roles
            </a>
            <a href="../index.php" class="nav-item">
                <i class="bi bi-house"></i> Back to Site
            </a>
        </nav>

        <div class="sidebar-footer">
            <strong>MedMarket Admin</strong>
            &copy; <?php echo date('Y'); ?> All rights reserved
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="menu-toggle" id="menu-toggle" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-title">
                Dashboard
                <span><?php echo date('l, d F Y'); ?></span>
            </div>
            <div class="topbar-right">

                <button class="notif-btn" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="notif-badge"></span>
                </button>

                <div class="admin-avatar" onclick="toggleDropdown()">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                    </div>
                    <div class="admin-info">
                        <strong><?php echo htmlspecialchars($adminName); ?></strong>
                        <small><?php echo htmlspecialchars($adminRole); ?></small>
                    </div>
                    <i class="bi bi-chevron-down" style="font-size:0.8rem; color:var(--muted);"></i>

                    <div class="dropdown-menu" id="adminDropdown">
                        <a href="profile.php"><i class="bi bi-person"></i> Profile</a>
                        <a href="../auth/logout.php" class="danger"><i class="bi bi-box-arrow-right"></i> Sign out</a>
                    </div>
                </div>

            </div>
        </header>


        <div class="content">
            <div class="page-header">
                <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $adminName)[0]); ?> 👋</h1>
                <p>Here's what's happening on MedMarket today.</p>
            </div>


            <div class="metric-grid">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Total Orders</div>
                        <div class="metric-value"><?php echo number_format($totalOrders); ?></div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-bag-check"></i></div>
                </div>
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Total Products</div>
                        <div class="metric-value"><?php echo number_format($totalProducts); ?></div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-box-seam"></i></div>
                </div>
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Total Users</div>
                        <div class="metric-value"><?php echo number_format($totalUsers); ?></div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-people"></i></div>
                </div>
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Report Logs</div>
                        <div class="metric-value"><?php echo number_format($totalLogs); ?></div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-journal-text"></i></div>
                </div>
            </div>

            <!-- Bottom grid -->
            <div class="bottom-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <a href="manage-orders.php">View all</a>
                    </div>
                    <div class="table-controls">
                        <div class="search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" id="order-search" placeholder="Search orders…" oninput="filterTable()">
                        </div>
                        <select class="filter-select" id="status-filter" onchange="filterTable()">
                            <option value="">All status</option>
                            <option value="delivered">Delivered</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="table-scroll-wrap">
                        <table class="data-table" id="orders-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Order ID</th>
                                    <th>Buyer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr class="empty-row">
                                        <td colspan="6">No orders found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order):
                                        $status = strtolower($order['status'] ?? 'pending');
                                        $badgeClass = match ($status) {
                                            'delivered' => 'badge-delivered',
                                            'cancelled' => 'badge-cancelled',
                                            default     => 'badge-pending',
                                        };
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['product_title']); ?></td>
                                            <td style="color:var(--muted);">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                            <td style="color:var(--muted);"><?php echo date('M jS, Y', strtotime($order['order_date'])); ?></td>
                                            <td style="font-weight:600;">R<?php echo number_format($order['product_price'], 2); ?></td>
                                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Products by Category</h2>
                        </div>
                        <div class="chart-wrap">
                            <canvas id="catChart" height="220"></canvas>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script>
            function toggleDropdown() {
                document.getElementById('adminDropdown').classList.toggle('show');
            }
            document.addEventListener('click', e => {
                if (!e.target.closest('.admin-avatar')) {
                    document.getElementById('adminDropdown').classList.remove('show');
                }
            });

            function filterTable() {
                const q = document.getElementById('order-search').value.toLowerCase();
                const status = document.getElementById('status-filter').value.toLowerCase();
                document.querySelectorAll('#orders-table tbody tr:not(.empty-row)').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const badge = row.querySelector('.badge')?.textContent.toLowerCase() ?? '';
                    const matchQ = !q || text.includes(q);
                    const matchS = !status || badge.includes(status);
                    row.style.display = matchQ && matchS ? '' : 'none';
                });
            }

            const catData = <?php echo json_encode(array_values($catData)); ?>;
            const catLabels = <?php echo json_encode(array_keys($catData)); ?>;

            new Chart(document.getElementById('catChart'), {
                type: 'doughnut',
                data: {
                    labels: catLabels.length ? catLabels : ['No data'],
                    datasets: [{
                        data: catData.length ? catData : [1],
                        backgroundColor: ['#036873', '#c2eaed', '#024a52', '#7bbfc5', '#a8d8dc'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'DM Sans',
                                    size: 12
                                },
                                padding: 16,
                                boxWidth: 12
                            }
                        }
                    }
                }
            });

            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.querySelector('.sidebar');

            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);

            menuToggle?.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });

            sidebar.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('active');
                    }
                });
            });
        </script>
</body>

</html>