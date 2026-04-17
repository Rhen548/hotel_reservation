<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php
$title = "Reports Module";
$error_msg = '';

$stats = [
    'total_reservations' => 0,
    'total_revenue' => 0,
    'completed_payments' => 0,
    'pending_balance' => 0,
    'cancelled_reservations' => 0,
    'checkins_today' => 0,
    'checkouts_today' => 0,
];

$recentReservations = [];
$recentPayments = [];

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Total reservations
    $stmt = $pdo->query("SELECT COUNT(*) FROM reservations");
    $stats['total_reservations'] = (int) $stmt->fetchColumn();

    // Total revenue from completed payments
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(amount), 0)
        FROM payments
        WHERE payment_status = 'completed'
    ");
    $stats['total_revenue'] = (float) $stmt->fetchColumn();

    // Total completed payments count
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM payments
        WHERE payment_status = 'completed'
    ");
    $stats['completed_payments'] = (int) $stmt->fetchColumn();

    // Total pending balance
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(r.total_amount), 0) -
               COALESCE((
                   SELECT SUM(p.amount)
                   FROM payments p
                   WHERE p.payment_status = 'completed'
               ), 0)
        FROM reservations r
        WHERE r.status IN ('pending', 'confirmed', 'checked_in', 'checked_out')
    ");
    $stats['pending_balance'] = (float) $stmt->fetchColumn();

    // Cancelled reservations
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE status = 'cancelled'
    ");
    $stats['cancelled_reservations'] = (int) $stmt->fetchColumn();

    // Check-ins today
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE status = 'checked_in'
          AND check_in_date = CURDATE()
    ");
    $stats['checkins_today'] = (int) $stmt->fetchColumn();

    // Check-outs today
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE status = 'checked_out'
          AND check_out_date = CURDATE()
    ");
    $stats['checkouts_today'] = (int) $stmt->fetchColumn();

    // Recent reservations
    $recentReservationSql = "
        SELECT
            r.reservation_id,
            r.check_in_date,
            r.check_out_date,
            r.number_of_guests,
            r.total_amount,
            r.status,
            c.first_name,
            c.last_name
        FROM reservations r
        INNER JOIN customers c
            ON r.customer_id = c.customer_id
        ORDER BY r.reservation_id DESC
        LIMIT 8
    ";
    $stmt = $pdo->query($recentReservationSql);
    $recentReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent payments
    $recentPaymentSql = "
        SELECT
            p.payment_id,
            p.reservation_id,
            p.payment_date,
            p.amount,
            p.payment_method,
            p.payment_status,
            c.first_name,
            c.last_name
        FROM payments p
        INNER JOIN reservations r
            ON p.reservation_id = r.reservation_id
        INNER JOIN customers c
            ON r.customer_id = c.customer_id
        ORDER BY p.payment_id DESC
        LIMIT 8
    ";
    $stmt = $pdo->query($recentPaymentSql);
    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
}

function reportBadgeClass($status) {
    return match ($status) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'checked_in' => 'bg-green-100 text-green-800',
        'checked_out' => 'bg-slate-200 text-slate-800',
        'completed' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
        'refunded' => 'bg-slate-200 text-slate-800',
        default => 'bg-gray-100 text-gray-800',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "#2563eb",
            dark: "#0f172a"
          }
        }
      }
    }
    </script>
</head>
<body class="bg-slate-100 min-h-screen">

    <div class="flex min-h-screen">

        <!-- Sidebar -->
       <aside class="w-72 bg-slate-900 text-white p-6 hidden lg:block">
            <h1 class="text-2xl font-extrabold mb-8">Hotel Admin</h1>

            <nav class="space-y-3">
                <a href="<?= url('admin') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Dashboard</a>
                <a href="<?= url('admin/reservations') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Reservations</a>
                <a href="<?= url('admin/payments') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Payments</a>
                <a href="<?= url('admin/reports') ?>"class="block px-4 py-3 rounded-xl bg-blue-600">Reports</a>
                <a href="<?= url('admin/rooms') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Rooms</a>
                <a href="<?= url('admin/room-images') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Room Images</a>
                <a href="<?= url('admin/audit-logs') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Audit Logs</a>
                <a href="<?= url('admin/logout') ?>"  class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Logout</a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <h2 class="text-3xl font-extrabold text-slate-900">Reports Module</h2>
                <p class="text-slate-500 mt-2">Monitor reservations, revenue, and payment performance.</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Total Reservations</p>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2"><?= $stats['total_reservations'] ?></h3>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Total Revenue</p>
                    <h3 class="text-3xl font-extrabold text-green-600 mt-2">₱<?= number_format($stats['total_revenue'], 2) ?></h3>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Completed Payments</p>
                    <h3 class="text-3xl font-extrabold text-blue-600 mt-2"><?= $stats['completed_payments'] ?></h3>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Pending Balance</p>
                    <h3 class="text-3xl font-extrabold text-red-600 mt-2">₱<?= number_format($stats['pending_balance'], 2) ?></h3>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Cancelled Reservations</p>
                    <h3 class="text-3xl font-extrabold text-red-500 mt-2"><?= $stats['cancelled_reservations'] ?></h3>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Check-ins Today</p>
                    <h3 class="text-3xl font-extrabold text-green-600 mt-2"><?= $stats['checkins_today'] ?></h3>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Check-outs Today</p>
                    <h3 class="text-3xl font-extrabold text-slate-800 mt-2"><?= $stats['checkouts_today'] ?></h3>
                </div>
            </div>

            <!-- Recent Reservations -->
            <div class="bg-white rounded-3xl shadow-lg overflow-hidden mb-8">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900">Recent Reservations</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-sm text-slate-600">
                                <th class="px-6 py-4 font-semibold">Reservation</th>
                                <th class="px-6 py-4 font-semibold">Guest</th>
                                <th class="px-6 py-4 font-semibold">Stay Dates</th>
                                <th class="px-6 py-4 font-semibold">Guests</th>
                                <th class="px-6 py-4 font-semibold">Total</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (!empty($recentReservations)): ?>
                                <?php foreach ($recentReservations as $row): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 font-bold text-slate-900">
                                            #<?= htmlspecialchars($row['reservation_id']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <?= htmlspecialchars($row['check_in_date']) ?> → <?= htmlspecialchars($row['check_out_date']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <?= htmlspecialchars($row['number_of_guests']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-slate-900">
                                            ₱<?= number_format((float)$row['total_amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= reportBadgeClass($row['status']) ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                        No reservations found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900">Recent Payments</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-sm text-slate-600">
                                <th class="px-6 py-4 font-semibold">Payment ID</th>
                                <th class="px-6 py-4 font-semibold">Reservation</th>
                                <th class="px-6 py-4 font-semibold">Guest</th>
                                <th class="px-6 py-4 font-semibold">Date</th>
                                <th class="px-6 py-4 font-semibold">Amount</th>
                                <th class="px-6 py-4 font-semibold">Method</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (!empty($recentPayments)): ?>
                                <?php foreach ($recentPayments as $payment): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 font-bold text-slate-900">
                                            #<?= htmlspecialchars($payment['payment_id']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-900">
                                            #<?= htmlspecialchars($payment['reservation_id']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-900">
                                            <?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <?= htmlspecialchars($payment['payment_date']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-slate-900">
                                            ₱<?= number_format((float)$payment['amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <?= htmlspecialchars($payment['payment_method']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= reportBadgeClass($payment['payment_status']) ?>">
                                                <?= htmlspecialchars($payment['payment_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                        No payments found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

</body>
</html>