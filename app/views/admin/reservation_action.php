<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php include __DIR__ . '/partials/audit_helper.php'; ?>
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/reservations'));
    exit;
}

$reservation_id = (int) ($_POST['reservation_id'] ?? 0);
$action = trim($_POST['action'] ?? '');

if ($reservation_id <= 0 || empty($action)) {
    header('Location: ' . url('admin/reservations?error=Invalid+request'));
    exit;
}

$allowedActions = ['confirm', 'cancel', 'check_in', 'check_out'];

if (!in_array($action, $allowedActions, true)) {
    header('Location: ' . url('admin/reservations?error=Invalid+action'));
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT status FROM reservations WHERE reservation_id = :reservation_id LIMIT 1");
    $stmt->execute([':reservation_id' => $reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        header('Location: ' . url('admin/reservations?error=Reservation+not+found'));
        exit;
    }

    $currentStatus = $reservation['status'];
    $newStatus = null;

    switch ($action) {
        case 'confirm':
            if ($currentStatus !== 'pending') {
                header('Location: ' . url('admin/reservations?error=Only+pending+reservations+can+be+confirmed'));
                exit;
            }
            $newStatus = 'confirmed';
            break;

        case 'cancel':
            if (!in_array($currentStatus, ['pending', 'confirmed'], true)) {
                header('Location: ' . url('admin/reservations?error=Only+pending+or+confirmed+reservations+can+be+cancelled'));
                exit;
            }
            $newStatus = 'cancelled';
            break;

        case 'check_in':
            if ($currentStatus !== 'confirmed') {
                header('Location: ' . url('admin/reservations?error=Only+confirmed+reservations+can+be+checked+in'));
                exit;
            }
            $newStatus = 'checked_in';
            break;

        case 'check_out':
            if ($currentStatus !== 'checked_in') {
                header('Location: ' . url('admin/reservations?error=Only+checked-in+reservations+can+be+checked+out'));
                exit;
            }
            $newStatus = 'checked_out';
            break;
    }

    $updateStmt = $pdo->prepare("
        UPDATE reservations
        SET status = :status
        WHERE reservation_id = :reservation_id
    ");

    $updateStmt->execute([
        ':status' => $newStatus,
        ':reservation_id' => $reservation_id,
    ]);

    $admin_id = $_SESSION['admin_id'] ?? null;
    write_audit_log($admin_id, "Updated reservation #{$reservation_id} status from {$currentStatus} to {$newStatus}");

    header('Location: ' . url('admin/reservations?success=1'));
    exit;

} catch (PDOException $e) {
    header('Location: ' . url('admin/reservations?error=' . urlencode($e->getMessage())));
    exit;
}