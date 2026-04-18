<?php
session_start();

$reservation_id = (int)($_POST['reservation_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($reservation_id <= 0) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: " . url('admin/reservations'));
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=hotel_reservation;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'approve') {

        $stmt = $pdo->prepare("
            UPDATE reservations
            SET status = 'cancelled',
                cancel_requested = 0,
                cancelled_at = NOW()
            WHERE reservation_id = ?
        ");
        $stmt->execute([$reservation_id]);

        $_SESSION['success'] = "Cancelled successfully.";

    } elseif ($action === 'reject') {

        $stmt = $pdo->prepare("
            UPDATE reservations
            SET cancel_requested = 0
            WHERE reservation_id = ?
        ");
        $stmt->execute([$reservation_id]);

        $_SESSION['success'] = "Request rejected.";
    }

} catch (PDOException $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: " . url('admin/reservations'));
exit;