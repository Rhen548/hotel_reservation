<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php
$title = "Payments Module";
$error_msg = '';
$payments = [];
$reservations = [];

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get reservations with customer info and total paid
    $reservationSql = "
        SELECT
            r.reservation_id,
            r.total_amount,
            r.status,
            r.check_in_date,
            r.check_out_date,
            c.first_name,
            c.last_name,
            COALESCE(SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END), 0) AS total_paid
        FROM reservations r
        INNER JOIN customers c
            ON r.customer_id = c.customer_id
        LEFT JOIN payments p
            ON r.reservation_id = p.reservation_id
        GROUP BY
            r.reservation_id,
            r.total_amount,
            r.status,
            r.check_in_date,
            r.check_out_date,
            c.first_name,
            c.last_name
        ORDER BY r.reservation_id DESC
    ";
    $reservationStmt = $pdo->query($reservationSql);
    $reservations = $reservationStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all payment records
    $paymentSql = "
        SELECT
            p.payment_id,
            p.reservation_id,
            p.payment_date,
            p.amount,
            p.payment_method,
            p.payment_status,
            p.transaction_reference,
            c.first_name,
            c.last_name
        FROM payments p
        INNER JOIN reservations r
            ON p.reservation_id = r.reservation_id
        INNER JOIN customers c
            ON r.customer_id = c.customer_id
        ORDER BY p.payment_id DESC
    ";
    $paymentStmt = $pdo->query($paymentSql);
    $payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
}

function paymentStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        case 'refunded':
            return 'bg-slate-200 text-slate-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
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
                <a href="<?= url('admin/payments') ?>" class="block px-4 py-3 rounded-xl bg-blue-600">Payments</a>
                <a href="<?= url('admin/reports') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Reports</a>
                <a href="<?= url('admin/rooms') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Rooms</a>
                <a href="<?= url('admin/room-images') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Room Images</a>
                <a href="<?= url('admin/audit-logs') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Audit Logs</a>
                <a href="<?= url('admin/logout') ?>"  class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Logout</a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <h2 class="text-3xl font-extrabold text-slate-900">Payments Module</h2>
                <p class="text-slate-500 mt-2">Track payments, balances, and reservation billing.</p>
            </div>

            <?php if (!empty($_GET['success'])): ?>
                <div class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                    Payment recorded successfully.
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['error'])): ?>
                <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <!-- Record Payment Form -->
            <div class="bg-white rounded-3xl shadow-lg p-8 mb-8">
                <h3 class="text-2xl font-bold text-slate-900 mb-6">Record Payment</h3>

                <form method="POST" action="<?= url('admin/payments/store') ?>" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="md:col-span-2 xl:col-span-1">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Reservation</label>
                        <select name="reservation_id" class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary" required>
                            <option value="">Select reservation</option>
                            <?php foreach ($reservations as $reservation): ?>
                                <?php
                                    $balance = (float)$reservation['total_amount'] - (float)$reservation['total_paid'];
                                ?>
                                <option value="<?= htmlspecialchars($reservation['reservation_id']) ?>">
                                    #<?= htmlspecialchars($reservation['reservation_id']) ?>
                                    - <?= htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']) ?>
                                    - Balance: ₱<?= number_format($balance, 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Amount</label>
                        <input type="number" step="0.01" min="0" name="amount"
                               class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Payment Method</label>
                        <select name="payment_method"
                                class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                                required>
                            <option value="">Select method</option>
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Payment Status</label>
                        <select name="payment_status"
                                class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                                required>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 xl:col-span-1">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Transaction Reference</label>
                        <input type="text" name="transaction_reference"
                               class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                               placeholder="Optional reference number">
                    </div>

                    <div class="md:col-span-2 xl:col-span-3">
                        <button class="bg-primary hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition">
                            Save Payment
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reservation Balances -->
            <div class="bg-white rounded-3xl shadow-lg overflow-hidden mb-8">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900">Reservation Balances</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-sm text-slate-600">
                                <th class="px-6 py-4 font-semibold">Reservation</th>
                                <th class="px-6 py-4 font-semibold">Guest</th>
                                <th class="px-6 py-4 font-semibold">Stay Dates</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Total Amount</th>
                                <th class="px-6 py-4 font-semibold">Total Paid</th>
                                <th class="px-6 py-4 font-semibold">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (!empty($reservations)): ?>
                                <?php foreach ($reservations as $row): ?>
                                    <?php $balance = (float)$row['total_amount'] - (float)$row['total_paid']; ?>
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
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-slate-900">
                                            ₱<?= number_format((float)$row['total_amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-green-700">
                                            ₱<?= number_format((float)$row['total_paid'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 font-bold <?= $balance > 0 ? 'text-red-600' : 'text-green-600' ?>">
                                            ₱<?= number_format($balance, 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                        No reservation balances found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900">Payment History</h3>
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
                                <th class="px-6 py-4 font-semibold">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (!empty($payments)): ?>
                                <?php foreach ($payments as $payment): ?>
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
                                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= paymentStatusClass($payment['payment_status']) ?>">
                                                <?= htmlspecialchars($payment['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <?= htmlspecialchars($payment['transaction_reference'] ?: '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-slate-500">
                                        No payment records found.
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