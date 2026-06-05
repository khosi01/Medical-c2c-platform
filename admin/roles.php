<?php
require_once __DIR__ . '/admin-auth-check.php';
require_once __DIR__ . '/../config/db.php';

$adminName   = $_SESSION['user_name'] ?? 'Admin';
$adminRole   = $_SESSION['user_role'] ?? 'admin';
$currentPage = 'roles';
$pageTitle   = 'Roles & Permissions';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update_role') {
    if (!canAccess('roles')) die('Access denied.');
    $userId  = (int)$_POST['user_id'];
    $newRole = $_POST['role'];

    if ($userId === (int)$adminId) {
        $msg = "<div class='alert alert-danger'>You cannot change your own role.</div>";
    } else {
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $userId]);
        $nameStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $nameStmt->execute([$userId]);
        $name = $nameStmt->fetchColumn();
        logAction($pdo, $adminId, "Changed role of $name (ID $userId) to: $newRole");
        $msg = "<div class='alert alert-success'>Role updated successfully.</div>";
    }
}

$search = trim($_GET['q']    ?? '');
$filter = trim($_GET['role'] ?? '');
$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter) {
    $where[]  = "role = ?";
    $params[] = $filter;
}

$stmt = $pdo->prepare("
    SELECT id, full_name, email, role, profession, created_at
    FROM users
    WHERE " . implode(' AND ', $where) . "
    ORDER BY FIELD(role,'admin','it','marketing','support','user'), full_name ASC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Role counts
$roleCounts = [];
$rc = $pdo->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
foreach ($rc->fetchAll() as $row) $roleCounts[$row['role']] = $row['cnt'];

// Permissions
$permissions = [
    'admin'     => ['dashboard', 'manage-users', 'manage-products', 'manage-orders', 'report-logs', 'roles'],
    'it'        => ['dashboard', 'report-logs', 'roles'],
    'marketing' => ['dashboard', 'manage-products'],
    'support'   => ['dashboard', 'manage-orders', 'manage-users'],
    'user'      => [],
];
$allPages = ['dashboard', 'manage-users', 'manage-products', 'manage-orders', 'report-logs', 'roles'];
$pageLabels = [
    'dashboard'        => 'Dashboard',
    'manage-users'     => 'Manage Users',
    'manage-products'  => 'Manage Products',
    'manage-orders'    => 'Manage Orders',
    'report-logs'      => 'Report Logs',
    'roles'            => 'Roles',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Roles | MedMarket Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin-mobile.css">
    <style>
        .perm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .75rem;
        }

        .perm-table th {
            padding: 8px 10px;
            text-align: left;
            font-size: .68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--muted);
            background: var(--off-white);
            border-bottom: 1px solid var(--border);
        }

        .perm-table td {
            padding: 6.4px 6.4px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .perm-table tr:last-child td {
            border-bottom: none;
        }

        .check-yes {
            color: #0f6e56;
            font-size: 1.1rem;
        }

        .check-no {
            color: #ddd;
            font-size: 1.1rem;
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 700;
        }

        .role-admin {
            background: #fdf0f0;
            color: #c0392b;
        }

        .role-it {
            background: var(--teal-light);
            color: var(--teal);
        }

        .role-marketing {
            background: #fff8e6;
            color: #9a6800;
        }

        .role-support {
            background: #f0f0f0;
            color: #555;
        }

        .role-user {
            background: #eaf6f2;
            color: #0f6e56;
        }

        @media (max-width: 1100px) {
            .perm-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
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
                    <h1>Roles & Permissions</h1>
                    <p>Manage who has access to what on MedMarket admin.</p>
                </div>
            </div>

            <?php if ($msg) echo $msg; ?>

            <!-- Role overview cards -->
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:28px;">
                <?php
                $roleCards = [
                    ['admin',     'Admin',     'bi-shield-fill',  '#c0392b'],
                    ['it',        'IT',        'bi-pc-display',   '#036873'],
                    ['marketing', 'Marketing', 'bi-megaphone',    '#9a6800'],
                    ['support',   'Support',   'bi-headset',      '#555'],
                    ['user',      'Users',     'bi-people',       '#0f6e56'],
                ];
                foreach ($roleCards as [$key, $label, $icon, $color]): ?>
                    <div style="background:white;border:1px solid #ddeaec;border-radius:12px;padding:16px;text-align:center;cursor:pointer;transition:box-shadow .2s;"
                        onclick="filterByRole('<?php echo $key; ?>')"
                        onmouseover="this.style.boxShadow='0 4px 16px rgba(3,104,115,.1)'"
                        onmouseout="this.style.boxShadow='none'">
                        <div style="width:40px;height:40px;border-radius:10px;background:<?php echo $color; ?>18;display:flex;align-items:center;justify-content:center;color:<?php echo $color; ?>;font-size:1.1rem;margin:0 auto 8px;">
                            <i class="bi <?php echo $icon; ?>"></i>
                        </div>
                        <div style="font-size:1.3rem;font-weight:800;color:#0d2e32;"><?php echo $roleCounts[$key] ?? 0; ?></div>
                        <div style="font-size:.75rem;color:#5a8087;font-weight:500;"><?php echo $label; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="perm-grid" style="display:grid;grid-template-columns:1fr 340px;gap:20px;flex-wrap:wrap;">

                <!-- Users table -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Users & Roles</h2>
                    </div>
                    <div class="table-controls">
                        <form method="GET" style="display:contents;" id="filterForm">
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="text" name="q" placeholder="Search name or email…" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <select name="role" id="roleFilter" class="f-select" onchange="this.form.submit()">
                                <option value="">All roles</option>
                                <?php foreach (['admin', 'it', 'marketing', 'support', 'user'] as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo $filter === $r ? 'selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-outline"><i class="bi bi-funnel"></i> Filter</button>
                        </form>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Current Role</th>
                                <?php if (canAccess('roles') && $adminRole === 'admin'): ?><th>Change Role</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr class="empty-row">
                                    <td colspan="4">No users found.</td>
                                </tr>
                                <?php else: foreach ($users as $u):
                                    $rc = 'role-' . $u['role'];
                                    $isSelf = $u['id'] == $adminId;
                                ?>
                                    <tr <?php echo $isSelf ? "style='background:#fafefe;'" : ''; ?>>
                                        <td>
                                            <div style="font-weight:600;"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                            <?php if ($isSelf): ?>
                                                <div style="font-size:.72rem;color:#036873;font-weight:600;">You</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color:var(--muted);font-size:.83rem;"><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span class="role-pill <?php echo $rc; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                        <?php if (canAccess('roles') && $adminRole === 'admin'): ?>
                                            <td>
                                                <?php if (!$isSelf): ?>
                                                    <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                                        <input type="hidden" name="_action" value="update_role">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                        <select name="role" class="f-select" style="padding:5px 8px;font-size:.8rem;">
                                                            <?php foreach (['admin', 'it', 'marketing', 'support', 'user'] as $r): ?>
                                                                <option value="<?php echo $r; ?>" <?php echo $u['role'] === $r ? 'selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="btn-teal btn-sm">Save</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="font-size:.78rem;color:#bbb;">Cannot change own role</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Permission matrix -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h2>Permission Matrix</h2>
                        </div>
                        <div style="padding:16px 20px;">
                            <table class="perm-table">
                                <thead>
                                    <tr>
                                        <th>Page</th>
                                        <?php foreach (['admin', 'it', 'marketing', 'support'] as $r): ?>
                                            <th style="text-align:center;"><?php echo ucfirst($r); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allPages as $page): ?>
                                        <tr>
                                            <td style="font-weight:500;"><?php echo $pageLabels[$page]; ?></td>
                                            <?php foreach (['admin', 'it', 'marketing', 'support'] as $r): ?>
                                                <td style="text-align:center;">
                                                    <?php if (in_array($page, $permissions[$r])): ?>
                                                        <i class="bi bi-check-circle-fill check-yes"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-x-circle check-no"></i>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Role descriptions -->
                    <div class="card" style="margin-top:0;">
                        <div class="card-header">
                            <h2>Role Descriptions</h2>
                        </div>
                        <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                            <?php
                            $descriptions = [
                                ['admin',     '#c0392b', 'bi-shield-fill',  'Full access to all pages and actions.'],
                                ['it',        '#036873', 'bi-pc-display',   'Access to logs and roles only.'],
                                ['marketing', '#9a6800', 'bi-megaphone',    'Can manage product listings.'],
                                ['support',   '#555',    'bi-headset',      'Can manage orders and users.'],
                            ];
                            foreach ($descriptions as [$r, $color, $icon, $desc]): ?>
                                <div style="display:flex;align-items:flex-start;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:<?php echo $color; ?>18;display:flex;align-items:center;justify-content:center;color:<?php echo $color; ?>;font-size:.9rem;flex-shrink:0;">
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:.85rem;text-transform:capitalize;"><?php echo $r; ?></div>
                                        <div style="font-size:.78rem;color:#5a8087;"><?php echo $desc; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function filterByRole(role) {
            document.getElementById('roleFilter').value = role;
            document.getElementById('filterForm').submit();
        }
        document.addEventListener('click', e => {
            if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('show');
        });
    </script>
</body>

</html>