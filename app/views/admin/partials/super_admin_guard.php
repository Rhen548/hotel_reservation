<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . url('admin/login'));
    exit;
}

if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'super_admin') {
    die('Access denied. Super Admin only.');
}