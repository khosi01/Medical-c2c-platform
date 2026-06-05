<?php
require_once __DIR__ . '/admin-auth-check.php';
require_once __DIR__ . '/../config/db.php';

$adminName   = $_SESSION['user_name'] ?? 'Admin';
$adminRole   = $_SESSION['user_role'] ?? 'admin';
$currentPage = 'users';
$pageTitle   = 'Manage Users';
$adminId     = $_SESSION['user_id'];

function logAction($pdo, $adminId, $action)
{
    $pdo->prepare("INSERT INTO report_logs (user_id, action, created_at) VALUES (?, ?, NOW())")
        ->execute([$adminId, $action]);
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'create') {
    if (!canAccess('manage-users')) die('Access denied.');
    $name  = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $msg = "<div class='alert alert-danger'>Email already registered.</div>";
    } else {
        $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?,?,?,?)")
            ->execute([$name, $email, $pass, $role]);
        logAction($pdo, $adminId, "Created user: $email (role: $role)");
        $msg = "<div class='alert alert-success'>User created successfully.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update') {
    if (!canAccess('manage-users')) die('Access denied.');
    $uid  = (int)$_POST['user_id'];
    $name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $pdo->prepare("UPDATE users SET full_name = ?, role = ? WHERE id = ?")
        ->execute([$name, $role, $uid]);
    logAction($pdo, $adminId, "Updated user ID $uid — name: $name, role: $role");
    $msg = "<div class='alert alert-success'>User updated.</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    if (!canAccess('manage-users')) die('Access denied.');
    $uid = (int)$_POST['user_id'];
    $row = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $row->execute([$uid]);
    $deleted = $row->fetchColumn();
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
    logAction($pdo, $adminId, "Deleted user ID $uid ($deleted)");
    $msg = "<div class='alert alert-success'>User deleted.</div>";
}

$search = trim($_GET['q'] ?? '');
$filter = $_GET['role'] ?? '';
$where  = ['1=1'];
$params = [];
if ($search) {
    $where[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter) {
    $where[] = "role = ?";
    $params[] = $filter;
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY id DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Users | MedMarket Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin-mobile.css">
</head>
<?php
?>

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
        <div class="sidebar-footer"><strong>MedMarket Admin</strong>&copy; <?php echo date('Y'); ?></div>
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
                    <h1>Manage Users</h1>
                    <p><?php echo count($users); ?> total users</p>
                </div>
                <?php if (canAccess('manage-users')): ?>
                    <button class="btn-teal" onclick="openModal('createModal')"><i class="bi bi-plus-lg"></i> Add User</button>
                <?php endif; ?>
            </div>

            <?php if ($msg) echo $msg; ?>

            <div class="card">
                <div class="table-controls">
                    <form method="GET" style="display:contents;">
                        <div class="search-wrap"><i class="bi bi-search"></i>
                            <input type="text" name="q" placeholder="Search name or email…" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select name="role" class="f-select" onchange="this.form.submit()">
                            <option value="">All roles</option>
                            <option value="user" <?php echo $filter === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="it" <?php echo $filter === 'it' ? 'selected' : ''; ?>>IT</option>
                            <option value="marketing" <?php echo $filter === 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="support" <?php echo $filter === 'support' ? 'selected' : ''; ?>>Support</option>
                        </select>
                        <button type="submit" class="btn-outline"><i class="bi bi-funnel"></i> Filter</button>
                    </form>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Profession</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr class="empty-row">
                                <td colspan="7">No users found.</td>
                            </tr>
                            <?php else: foreach ($users as $u):
                                $roleClass = match ($u['role']) {
                                    'admin' => 'badge-danger',
                                    'it' => 'badge-info',
                                    'marketing' => 'badge-warning',
                                    'support' => 'badge-secondary',
                                    default => 'badge-success'
                                }; ?>
                                <tr>
                                    <td style="color:var(--muted);"><?php echo $u['id']; ?></td>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="badge <?php echo $roleClass; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                    <td><?php echo htmlspecialchars($u['profession'] ?? '—'); ?></td>
                                    <td style="color:var(--muted);"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <?php if (canAccess('manage-users')): ?>
                                            <button class="btn-outline btn-sm" onclick='openEdit(<?php echo json_encode($u); ?>)'><i class="bi bi-pencil"></i></button>
                                            <button class="btn-danger btn-sm" onclick="confirmDelete(<?php echo $u['id']; ?>,'<?php echo htmlspecialchars($u['full_name']); ?>')"><i class="bi bi-trash"></i></button>
                                        <?php endif; ?>
                                    </td>
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
            <h3>Add New User</h3>
            <p>Create a new user or staff account.</p>
            <form method="POST">
                <input type="hidden" name="_action" value="create">
                <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
                <div class="form-group"><label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="it">IT</option>
                        <option value="marketing">Marketing</option>
                        <option value="support">Support</option>
                    </select>
                </div>
                <div class="modal-footer"><button type="button" class="btn-outline" onclick="closeModal('createModal')">Cancel</button><button type="submit" class="btn-teal">Create User</button></div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('editModal')"><i class="bi bi-x-lg"></i></button>
            <h3>Edit User</h3>
            <p>Update name or role.</p>
            <form method="POST">
                <input type="hidden" name="_action" value="update">
                <input type="hidden" name="user_id" id="edit_id">
                <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" id="edit_name" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Role</label>
                    <select name="role" id="edit_role" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="it">IT</option>
                        <option value="marketing">Marketing</option>
                        <option value="support">Support</option>
                    </select>
                </div>
                <div class="modal-footer"><button type="button" class="btn-outline" onclick="closeModal('editModal')">Cancel</button><button type="submit" class="btn-teal">Save Changes</button></div>
            </form>
        </div>
    </div>

    <!-- DELETE FORM -->
    <form method="POST" id="deleteForm" style="display:none;">
        <input type="hidden" name="_action" value="delete">
        <input type="hidden" name="user_id" id="delete_id">
    </form>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function openEdit(u) {
            document.getElementById('edit_id').value = u.id;
            document.getElementById('edit_name').value = u.full_name;
            document.getElementById('edit_role').value = u.role;
            openModal('editModal');
        }

        function confirmDelete(id, name) {
            if (confirm('Delete user "' + name + '"? This cannot be undone.')) {
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