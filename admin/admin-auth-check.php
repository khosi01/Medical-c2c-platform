<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$adminRoles = ['admin', 'it', 'marketing', 'support'];

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $adminRoles)) {
    header("Location: /medical-c2c-platform/auth/login.php");
    exit();
}

$role = $_SESSION['user_role'];

function canAccess(string $page): bool
{
    global $role;
    $permissions = [
        'admin'     => ['dashboard', 'manage-users', 'manage-products', 'manage-orders', 'report-logs', 'roles'],
        'it'        => ['dashboard', 'report-logs', 'roles'],
        'marketing' => ['dashboard', 'manage-products'],
        'support'   => ['dashboard', 'manage-orders', 'manage-users'],
    ];
    return in_array($page, $permissions[$role] ?? []);
}
