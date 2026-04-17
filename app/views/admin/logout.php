<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/partials/audit_helper.php';

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_username = $_SESSION['admin_username'] ?? 'Unknown Admin';

if ($admin_id) {
    write_audit_log($admin_id, "Logged out from admin panel");
}

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);

header('Location: ' . url('admin/login'));
exit;