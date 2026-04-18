<?php include __DIR__ . '/partials/auth_guard.php'; ?>

<?php
$title = "Room Management";
$error_msg = '';
$rooms = [];

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ FIXED QUERY (JOIN room_types)
    $stmt = $pdo->query("
        SELECT r.*, rt.type_name
        FROM rooms r
        LEFT JOIN room_types rt ON r.type_id = rt.type_id
        ORDER BY r.room_id DESC
    ");

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
}

// STATUS COLORS
function roomStatusClass($status) {
    switch ($status) {
        case 'available':
            return 'bg-green-100 text-green-800';
        case 'occupied':
            return 'bg-red-100 text-red-800';
        case 'maintenance':
            return 'bg-yellow-100 text-yellow-800';
        case 'cleaning':
            return 'bg-blue-100 text-blue-800';
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

    <!-- SIDEBAR -->
    <aside class="w-72 bg-slate-900 text-white p-6 hidden lg:block">
        <h1 class="text-2xl font-extrabold mb-8">Hotel Admin</h1>

        <nav class="space-y-3">
            <a href="<?= url('admin') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Dashboard</a>
            <a href="<?= url('admin/reservations') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Reservations</a>
            <a href="<?= url('admin/payments') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Payments</a>
            <a href="<?= url('admin/reports') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Reports</a>
            <a href="<?= url('admin/rooms') ?>" class="block px-4 py-3 rounded-xl bg-blue-600">Rooms</a>
            <a href="<?= url('admin/room-images') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Room Images</a>
            <a href="<?= url('admin/audit-logs') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Audit Logs</a>
            <a href="<?= url('admin/logout') ?>"  class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Logout</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 p-6 lg:p-8">

        <!-- HEADER -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900">Room Management</h2>
                <p class="text-slate-500 mt-2">Add, edit, delete, and monitor room availability.</p>
            </div>

            <a href="<?= url('admin/rooms/create') ?>"
               class="inline-flex items-center bg-primary hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold transition">
                Add New Room
            </a>
        </div>

        <!-- ALERTS -->
        <?php if (!empty($_GET['success'])): ?>
            <div class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                Operation completed successfully.
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

        <!-- TABLE -->
        <div class="bg-white rounded-3xl shadow-lg overflow-hidden">

            <div class="px-6 py-5 border-b border-slate-200">
                <h3 class="text-xl font-bold text-slate-900">Room List</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">

                    <thead class="bg-slate-50">
                        <tr class="text-left text-sm text-slate-600">
                            <th class="px-6 py-4 font-semibold">Room Number</th>
                            <th class="px-6 py-4 font-semibold">Room Type</th>
                            <th class="px-6 py-4 font-semibold">Price</th>
                            <th class="px-6 py-4 font-semibold">Capacity</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Description</th>
                            <th class="px-6 py-4 font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        <?php if (!empty($rooms)): ?>
                            <?php foreach ($rooms as $room): ?>
                                <tr class="hover:bg-slate-50">

                                    <!-- ROOM NUMBER -->
                                    <td class="px-6 py-4 font-bold text-slate-900">
                                        <?= htmlspecialchars($room['room_number']) ?>
                                    </td>

                                    <!-- ✅ FIXED ROOM TYPE -->
                                    <td class="px-6 py-4 text-slate-700">
                                        <?= htmlspecialchars($room['type_name'] ?? 'N/A') ?>
                                    </td>

                                    <!-- PRICE -->
                                    <td class="px-6 py-4 font-semibold text-slate-900">
                                        ₱<?= number_format((float)$room['price_per_night'], 2) ?>
                                    </td>

                                    <!-- CAPACITY -->
                                    <td class="px-6 py-4 text-slate-700">
                                        <?= htmlspecialchars($room['capacity']) ?>
                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= roomStatusClass($room['status']) ?>">
                                            <?= htmlspecialchars($room['status']) ?>
                                        </span>
                                    </td>

                                    <!-- DESCRIPTION -->
                                    <td class="px-6 py-4 text-slate-600 max-w-xs">
                                        <?= htmlspecialchars($room['description'] ?: '-') ?>
                                    </td>

                                    <!-- ACTIONS -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">

                                            <a href="<?= url('admin/rooms/edit?id=' . $room['room_id']) ?>"
                                               class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                                                Edit
                                            </a>

                                            <form method="POST" action="<?= url('admin/rooms/delete') ?>" onsubmit="return confirm('Delete this room?')">
                                                <input type="hidden" name="room_id" value="<?= htmlspecialchars($room['room_id']) ?>">
                                                <button class="bg-red-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-red-700 transition">
                                                    Delete
                                                </button>
                                            </form>

                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                    No rooms found.
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