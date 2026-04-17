<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php include __DIR__ . '/partials/audit_helper.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/payments'));
    exit;
}

$reservation_id = (int) ($_POST['reservation_id'] ?? 0);
$amount = (float) ($_POST['amount'] ?? 0);
$payment_method = trim($_POST['payment_method'] ?? '');
$payment_status = trim($_POST['payment_status'] ?? '');
$transaction_reference = trim($_POST['transaction_reference'] ?? '');

$allowedMethods = ['cash', 'gcash', 'credit_card', 'bank_transfer'];
$allowedStatuses = ['pending', 'completed', 'failed', 'refunded'];

if ($reservation_id <= 0) {
    header('Location: ' . url('admin/payments?error=Invalid+reservation'));
    exit;
}

if ($amount <= 0) {
    header('Location: ' . url('admin/payments?error=Amount+must+be+greater+than+zero'));
    exit;
}

if (!in_array($payment_method, $allowedMethods, true)) {
    header('Location: ' . url('admin/payments?error=Invalid+payment+method'));
    exit;
}

if (!in_array($payment_status, $allowedStatuses, true)) {
    header('Location: ' . url('admin/payments?error=Invalid+payment+status'));
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check reservation exists
    $reservationStmt = $pdo->prepare("
        SELECT reservation_id, total_amount
        FROM reservations
        WHERE reservation_id = :reservation_id
        LIMIT 1
    ");
    $reservationStmt->execute([':reservation_id' => $reservation_id]);
    $reservation = $reservationStmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        header('Location: ' . url('admin/payments?error=Reservation+not+found'));
        exit;
    }

    // Get total paid
    $paidStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total_paid
        FROM payments
        WHERE reservation_id = :reservation_id
          AND payment_status = 'completed'
    ");
    $paidStmt->execute([':reservation_id' => $reservation_id]);
    $paidData = $paidStmt->fetch(PDO::FETCH_ASSOC);

    $currentPaid = (float) $paidData['total_paid'];
    $totalAmount = (float) $reservation['total_amount'];

    // Prevent overpayment
    if ($payment_status === 'completed' && ($currentPaid + $amount) > $totalAmount) {
        header('Location: ' . url('admin/payments?error=Payment+exceeds+remaining+balance'));
        exit;
    }

    // INSERT PAYMENT
    $insertStmt = $pdo->prepare("
        INSERT INTO payments (
            reservation_id,
            amount,
            payment_method,
            payment_status,
            transaction_reference
        ) VALUES (
            :reservation_id,
            :amount,
            :payment_method,
            :payment_status,
            :transaction_reference
        )
    ");

    $insertStmt->execute([
        ':reservation_id' => $reservation_id,
        ':amount' => $amount,
        ':payment_method' => $payment_method,
        ':payment_status' => $payment_status,
        ':transaction_reference' => $transaction_reference,
    ]);

    // ✅ AUDIT LOG (TAMANG LUGAR)
    $admin_id = $_SESSION['admin_id'] ?? null;

    write_audit_log(
        $admin_id,
        "Recorded payment for reservation #{$reservation_id}: amount ₱" 
        . number_format($amount, 2) . 
        ", method {$payment_method}, status {$payment_status}"
    );

    // REDIRECT
    header('Location: ' . url('admin/payments?success=1'));
    exit;

} catch (PDOException $e) {
    header('Location: ' . url('admin/payments?error=' . urlencode($e->getMessage())));
    exit;
}