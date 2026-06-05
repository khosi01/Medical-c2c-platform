<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    $currentUrl = '/medical-c2c-platform' . $_SERVER['REQUEST_URI'];
    header("Location: /medical-c2c-platform/auth/login.php?redirect=" . urlencode($currentUrl));
    exit();
}
