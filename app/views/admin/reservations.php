<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php
$title = "Admin Reservation Dashboard";
$error_msg = '';

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SELECT
            r.reservation_id,
            r.reservation_date,
            r.check_in_date,
            r.check_out_date,
            r.number_of_guests,
            r.total_amount,
            r.status,
            r.special_requests,

            c.customer_id,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,

            rm.room_id,
            rm.room_number,
            rm.room_type,
            rr.price_per_night_at_booking

        FROM reservations r
        INNER JOIN customers c
            ON r.customer_id = c.customer_id
        LEFT JOIN reservation_rooms rr
            ON r.reservation_id = rr.reservation_id
        LEFT JOIN rooms rm
            ON rr.room_id = rm.room_id
        ORDER BY r.reservation_id DESC
    ";

    $stmt = $pdo->query($sql);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
    $reservations = [];
}

function statusBadgeClass($status) {
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
function canConfirm($status) {
    return $status === 'pending';
}

function canCancel($status) {
    return in_array($status, ['pending', 'confirmed'], true);
}

function canCheckIn($status) {
    return $status === 'confirmed';
}

function canCheckOut($status) {
    return $status === 'checked_in';
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
                <a href="<?= url('admin/reservations') ?>" class="block px-4 py-3 rounded-xl bg-blue-600">Reservations</a>
                <a href="<?= url('admin/payments') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Payments</a>
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
                <h2 class="text-3xl font-extrabold text-slate-900">Reservation Dashboard</h2>
                <p class="text-slate-500 mt-2">Manage bookings, statuses, and guest stays.</p>
            </div>

            <?php if (!empty($_GET['success'])): ?>
                <div class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                    Reservation updated successfully.
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

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Total Reservations</p>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2"><?= count($reservations) ?></h3>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Pending</p>
                    <h3 class="text-3xl font-extrabold text-yellow-600 mt-2">
                        <?= count(array_filter($reservations, fn($r) => $r['status'] === 'pending')) ?>
                    </h3>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Confirmed</p>
                    <h3 class="text-3xl font-extrabold text-blue-600 mt-2">
                        <?= count(array_filter($reservations, fn($r) => $r['status'] === 'confirmed')) ?>
                    </h3>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-slate-500">Checked In</p>
                    <h3 class="text-3xl font-extrabold text-green-600 mt-2">
                        <?= count(array_filter($reservations, fn($r) => $r['status'] === 'checked_in')) ?>
                    </h3>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900">Reservation List</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-sm text-slate-600">
                                <th class="px-6 py-4 font-semibold">Reservation</th>
                                <th class="px-6 py-4 font-semibold">Guest</th>
                                <th class="px-6 py-4 font-semibold">Room</th>
                                <th class="px-6 py-4 font-semibold">Stay Dates</th>
                                <th class="px-6 py-4 font-semibold">Guests</th>
                                <th class="px-6 py-4 font-semibold">Total</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (!empty($reservations)): ?>
                                <?php foreach ($reservations as $row): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900">#<?= htmlspecialchars($row['reservation_id']) ?></div>
                                            <div class="text-sm text-slate-500">
                                                <?= htmlspecialchars($row['reservation_date']) ?>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                            </div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($row['email']) ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($row['phone']) ?></div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <?php if (!empty($row['room_number'])): ?>
                                                <div class="font-semibold text-slate-900">Room <?= htmlspecialchars($row['room_number']) ?></div>
                                                <div class="text-sm text-slate-500"><?= htmlspecialchars($row['room_type']) ?></div>
                                            <?php else: ?>
                                                <span class="text-slate-400">No room assigned</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900">
                                                <span class="font-semibold">In:</span> <?= htmlspecialchars($row['check_in_date']) ?>
                                            </div>
                                            <div class="text-sm text-slate-900">
                                                <span class="font-semibold">Out:</span> <?= htmlspecialchars($row['check_out_date']) ?>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 text-slate-900 font-medium">
                                            <?= htmlspecialchars($row['number_of_guests']) ?>
                                        </td>

                                        <td class="px-6 py-4 text-slate-900 font-bold">
                                            ₱<?= number_format((float)$row['total_amount'], 2) ?>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= statusBadgeClass($row['status']) ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <?php if (canConfirm($row['status'])): ?>
                                                    <form method="POST" action="<?= url('admin/reservations/action') ?>">
                                                        <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['reservation_id']) ?>">
                                                        <input type="hidden" name="action" value="confirm">
                                                        <button class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                                                            Confirm
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (canCancel($row['status'])): ?>
                                                    <form method="POST" action="<?= url('admin/reservations/action') ?>">
                                                        <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['reservation_id']) ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <button class="bg-red-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-red-700 transition">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (canCheckIn($row['status'])): ?>
                                                    <form method="POST" action="<?= url('admin/reservations/action') ?>">
                                                        <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['reservation_id']) ?>">
                                                        <input type="hidden" name="action" value="check_in">
                                                        <button class="bg-green-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-green-700 transition">
                                                            Check In
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (canCheckOut($row['status'])): ?>
                                                    <form method="POST" action="<?= url('admin/reservations/action') ?>">
                                                        <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['reservation_id']) ?>">
                                                        <input type="hidden" name="action" value="check_out">
                                                        <button class="bg-slate-700 text-white px-3 py-2 rounded-lg text-sm hover:bg-slate-800 transition">
                                                            Check Out
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <?php if (!empty($row['special_requests'])): ?>
                                        <tr class="bg-slate-50">
                                            <td colspan="8" class="px-6 py-3 text-sm text-slate-600">
                                                <span class="font-semibold">Special Requests:</span>
                                                <?= htmlspecialchars($row['special_requests']) ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-slate-500">
                                        No reservations found.
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