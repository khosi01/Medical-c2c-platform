<?php
require_once __DIR__ . '/admin-auth-check.php';
require_once __DIR__ . '/../config/db.php';

$adminName   = $_SESSION['user_name'] ?? 'Admin';
$adminRole   = $_SESSION['user_role'] ?? 'admin';
$currentPage = 'logs';
$pageTitle   = 'Report Logs';
$adminId     = $_SESSION['user_id'];

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'create') {
    if (!canAccess('report-logs')) die('Access denied.');
    $action = trim($_POST['action']);
    if (!empty($action)) {
        $pdo->prepare("INSERT INTO report_logs (user_id, action, created_at) VALUES (?, ?, NOW())")
            ->execute([$adminId, $action]);
        $msg = "<div class='alert alert-success'>Log entry added.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    if (!canAccess('report-logs')) die('Access denied.');
    $logId = (int)$_POST['log_id'];
    $pdo->prepare("DELETE FROM report_logs WHERE id = ?")->execute([$logId]);
    $msg = "<div class='alert alert-success'>Log entry deleted.</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'clear') {
    if ($_SESSION['user_role'] !== 'admin') die('Access denied.');
    $pdo->query("TRUNCATE TABLE report_logs");
    $msg = "<div class='alert alert-success'>All logs cleared.</div>";
}

$search    = trim($_GET['q']    ?? '');
$dateFrom  = trim($_GET['from'] ?? '');
$dateTo    = trim($_GET['to']   ?? '');
$where     = ['1=1'];
$params    = [];

if ($search) {
    $where[]  = "(rl.action LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($dateFrom) {
    $where[]  = "rl.created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo) {
    $where[]  = "rl.created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$stmt = $pdo->prepare("
    SELECT rl.id, rl.action, rl.created_at,
           u.full_name AS admin_name, u.role AS admin_role
    FROM report_logs rl
    LEFT JOIN users u ON u.id = rl.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY rl.created_at DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$totalLogs  = $pdo->query("SELECT COUNT(*) FROM report_logs")->fetchColumn();
$todayLogs  = $pdo->query("SELECT COUNT(*) FROM report_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$weekLogs   = $pdo->query("SELECT COUNT(*) FROM report_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Report Logs | MedMarket Admin</title>
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
                    <h1>Report Logs</h1>
                    <p>Track all admin actions on MedMarket.</p>
                </div>
                <div style="display:flex;gap:8px;">
                    <?php if (canAccess('report-logs')): ?>
                        <button class="btn-teal" onclick="openModal('createModal')">
                            <i class="bi bi-plus-lg"></i> Add Log Entry
                        </button>
                    <?php endif; ?>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <button class="btn-danger" onclick="confirmClear()">
                            <i class="bi bi-trash"></i> Clear All
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($msg) echo $msg; ?>

            <!-- Metric cards -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;">
                <?php
                $cards = [
                    ['Total Logs',    $totalLogs, 'bi-journal-text', '#036873'],
                    ['Today',         $todayLogs, 'bi-calendar-day', '#0f6e56'],
                    ['Last 7 Days',   $weekLogs,  'bi-calendar-week', '#9a6800'],
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
                    <h2>All Log Entries</h2>
                </div>
                <div class="table-controls">
                    <form method="GET" style="display:contents;">
                        <div class="search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" name="q" placeholder="Search action or admin…" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <input type="date" name="from" class="f-select" value="<?php echo htmlspecialchars($dateFrom); ?>" title="From date">
                        <input type="date" name="to" class="f-select" value="<?php echo htmlspecialchars($dateTo); ?>" title="To date">
                        <button type="submit" class="btn-outline"><i class="bi bi-funnel"></i> Filter</button>
                        <?php if ($search || $dateFrom || $dateTo): ?>
                            <a href="report-logs.php" class="btn-outline"><i class="bi bi-x"></i> Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Action</th>
                            <th>Performed By</th>
                            <th>Role</th>
                            <th>Date & Time</th>
                            <?php if (canAccess('report-logs')): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr class="empty-row">
                                <td colspan="6">No log entries found.</td>
                            </tr>
                            <?php else: foreach ($logs as $log): ?>
                                <tr>
                                    <td style="color:var(--muted);"><?php echo $log['id']; ?></td>
                                    <td style="max-width:320px;">
                                        <div style="display:flex;align-items:flex-start;gap:8px;">
                                            <div style="width:28px;height:28px;border-radius:6px;background:#e8f6f7;display:flex;align-items:center;justify-content:center;color:#036873;font-size:.85rem;flex-shrink:0;">
                                                <i class="bi bi-activity"></i>
                                            </div>
                                            <span style="font-size:.85rem;line-height:1.4;"><?php echo htmlspecialchars($log['action']); ?></span>
                                        </div>
                                    </td>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($log['admin_name'] ?? 'System'); ?></td>
                                    <td>
                                        <?php if ($log['admin_role']): ?>
                                            <span class="badge badge-info" style="text-transform:capitalize;"><?php echo htmlspecialchars($log['admin_role']); ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--muted);white-space:nowrap;">
                                        <?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?>
                                    </td>
                                    <?php if (canAccess('report-logs')): ?>
                                        <td>
                                            <button class="btn-danger btn-sm" onclick="confirmDeleteLog(<?php echo $log['id']; ?>)">
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

    <!-- CREATE LOG MODAL -->
    <div class="modal-overlay" id="createModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('createModal')"><i class="bi bi-x-lg"></i></button>
            <h3>Add Log Entry</h3>
            <p>Manually record an admin action.</p>
            <form method="POST">
                <input type="hidden" name="_action" value="create">
                <div class="form-group">
                    <label class="form-label">Action Description</label>
                    <textarea name="action" class="form-control" rows="3" placeholder="Describe the action taken…" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn-teal">Add Entry</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE FORM -->
    <form method="POST" id="deleteForm" style="display:none;">
        <input type="hidden" name="_action" value="delete">
        <input type="hidden" name="log_id" id="delete_id">
    </form>

    <!-- CLEAR ALL FORM -->
    <form method="POST" id="clearForm" style="display:none;">
        <input type="hidden" name="_action" value="clear">
    </form>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function confirmDeleteLog(id) {
            if (confirm('Delete this log entry?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function confirmClear() {
            if (confirm('Clear ALL log entries? This cannot be undone.')) {
                document.getElementById('clearForm').submit();
            }
        }
        document.addEventListener('click', e => {
            if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('show');
        });
    </script>
</body>

</html>