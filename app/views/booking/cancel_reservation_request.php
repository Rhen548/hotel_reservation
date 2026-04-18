<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: " . url('customer-signin'));
    exit;
}

$reservation_id = (int)($_POST['reservation_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($reservation_id <= 0 || empty($reason)) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: " . url('my-reservations'));
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=hotel_reservation;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // check reservation
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        $_SESSION['error'] = "Reservation not found.";
        header("Location: " . url('my-reservations'));
        exit;
    }

    if ($reservation['cancel_requested'] == 1) {
        $_SESSION['error'] = "Already requested.";
        header("Location: " . url('my-reservations'));
        exit;
    }

    // update
    $stmt = $pdo->prepare("
        UPDATE reservations
        SET cancel_requested = 1,
            cancellation_reason = ?
        WHERE reservation_id = ?
    ");
    $stmt->execute([$reason, $reservation_id]);

    $_SESSION['success'] = "Cancellation request sent.";

} catch (PDOException $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: " . url('my-reservations'));
exit;