<?php

$title = "My Reservations";
$error_msg = '';
$reservations = [];

if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id'])) {
    header("Location: " . url('customer-signin'));
    exit;
}

function reservationStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'confirmed':
            return 'bg-blue-100 text-blue-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'checked_in':
            return 'bg-green-100 text-green-800';
        case 'checked_out':
            return 'bg-slate-200 text-slate-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT
            r.*,
            rm.room_number,
            rt.type_name,
            (
                SELECT COALESCE(SUM(amount), 0)
                FROM payments
                WHERE reservation_id = r.reservation_id
                  AND payment_status = 'completed'
            ) AS total_paid
        FROM reservations r
        LEFT JOIN reservation_rooms rr
            ON r.reservation_id = rr.reservation_id
        LEFT JOIN rooms rm
            ON rr.room_id = rm.room_id
        LEFT JOIN room_types rt
            ON rm.type_id = rt.type_id
        WHERE r.customer_id = :customer_id
          AND r.status <> 'cancelled'
        ORDER BY r.reservation_id DESC
    ");
    $stmt->execute([
        ':customer_id' => $_SESSION['customer_id']
    ]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">My Reservations</h1>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="bg-green-100 text-green-700 p-3 mb-4 rounded">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reservations)): ?>
            <?php foreach ($reservations as $r): ?>
                <?php
                    $total = (float) ($r['total_amount'] ?? 0);
                    $paid = (float) ($r['total_paid'] ?? 0);
                    $balance = $total - $paid;
                    $cancelRequested = (int) ($r['cancel_requested'] ?? 0);
                    $status = $r['status'] ?? '';
                ?>

                <div class="bg-white p-6 rounded-xl shadow mb-6">
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <h2 class="font-bold text-lg">
                            Reservation #<?= htmlspecialchars($r['reservation_id']) ?>
                        </h2>

                        <span class="px-3 py-1 rounded text-sm font-semibold <?= reservationStatusClass($status) ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </div>

                    <p>
                        <b>Room:</b>
                        <?= htmlspecialchars($r['room_number'] ?? '-') ?>
                        (<?= htmlspecialchars($r['type_name'] ?? 'N/A') ?>)
                    </p>
                    <p><b>Check-in:</b> <?= htmlspecialchars($r['check_in_date'] ?? '-') ?></p>
                    <p><b>Check-out:</b> <?= htmlspecialchars($r['check_out_date'] ?? '-') ?></p>

                    <p class="mt-2"><b>Total:</b> ₱<?= number_format($total, 2) ?></p>
                    <p><b>Paid:</b> ₱<?= number_format($paid, 2) ?></p>
                    <p><b>Balance:</b> ₱<?= number_format($balance, 2) ?></p>

                    <?php if ($cancelRequested === 1): ?>
                        <div class="bg-yellow-100 text-yellow-800 p-3 mt-4 rounded">
                            <b>Cancellation Request Pending</b><br>
                            Reason:
                            <?= htmlspecialchars($r['cancellation_reason'] ?? '') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($cancelRequested === 0 && $status !== 'cancelled' && $status !== 'checked_out'): ?>
                        <form action="<?= url('reservation/cancel-request') ?>" method="POST" class="mt-4">
                            <input
                                type="hidden"
                                name="reservation_id"
                                value="<?= htmlspecialchars($r['reservation_id']) ?>"
                            >

                            <textarea
                                name="reason"
                                required
                                class="w-full border border-slate-300 p-2 rounded mb-2"
                                placeholder="Enter reason for cancellation..."
                            ></textarea>

                            <button
                                type="submit"
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition"
                                onclick="return confirm('Are you sure you want to request cancellation for this reservation?');"
                            >
                                Request Cancellation
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-white p-10 rounded-xl shadow text-center">
                <div class="text-5xl mb-4">🏨</div>
                <h2 class="text-2xl font-bold mb-2">No Active Reservations</h2>
                <p class="text-slate-500 mb-6">
                    You currently have no active reservations to display.
                </p>
                <a
                    href="<?= url('search-rooms') ?>"
                    class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
                >
                    Search Rooms
                </a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>