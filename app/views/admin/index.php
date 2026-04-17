<?php
include __DIR__ . '/partials/auth_guard.php';

$title = "Admin Dashboard";
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">

    <div class="flex min-h-screen">

        <aside class="w-72 bg-slate-900 text-white p-6 hidden lg:block">
            <h1 class="text-2xl font-extrabold mb-8">Hotel Admin</h1>

            <nav class="space-y-3">
                <a href="<?= url('admin') ?>" class="block px-4 py-3 rounded-xl bg-blue-600">Dashboard</a>
                <a href="<?= url('admin/reservations') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Reservations</a>
                <a href="<?= url('admin/payments') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Payments</a>
                <a href="<?= url('admin/reports') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Reports</a>
                <a href="<?= url('admin/rooms') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Rooms</a>
                <a href="<?= url('admin/room-images') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Room Images</a>
                <a href="<?= url('admin/audit-logs') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Audit Logs</a>
                <a href="<?= url('admin/logout') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Logout</a>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            <div class="bg-white rounded-3xl shadow-lg p-8">
                <h2 class="text-3xl font-extrabold text-slate-900">
                    Welcome, <?= htmlspecialchars($admin_username) ?>
                </h2>
                <p class="text-slate-500 mt-2">
                    Role: <span class="font-semibold"><?= htmlspecialchars($admin_role) ?></span>
                </p>

                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6 mt-10">
                    <a href="<?= url('admin/reservations') ?>" class="bg-slate-50 rounded-2xl p-6 border hover:shadow-md transition">
                        <h3 class="text-xl font-bold text-slate-900">Reservations</h3>
                        <p class="text-slate-500 mt-2">Manage bookings and reservation statuses.</p>
                    </a>

                    <a href="<?= url('admin/payments') ?>" class="bg-slate-50 rounded-2xl p-6 border hover:shadow-md transition">
                        <h3 class="text-xl font-bold text-slate-900">Payments</h3>
                        <p class="text-slate-500 mt-2">Track completed, pending, and failed payments.</p>
                    </a>

                    <a href="<?= url('admin/reports') ?>" class="bg-slate-50 rounded-2xl p-6 border hover:shadow-md transition">
                        <h3 class="text-xl font-bold text-slate-900">Reports</h3>
                        <p class="text-slate-500 mt-2">View reservation and revenue summaries.</p>
                    </a>

                    <a href="<?= url('admin/rooms') ?>" class="bg-slate-50 rounded-2xl p-6 border hover:shadow-md transition">
                        <h3 class="text-xl font-bold text-slate-900">Rooms</h3>
                        <p class="text-slate-500 mt-2">Manage room availability and room details.</p>
                    </a>

                    <a href="<?= url('admin/room-images') ?>" class="bg-slate-50 rounded-2xl p-6 border hover:shadow-md transition">
                        <h3 class="text-xl font-bold text-slate-900">Room Images</h3>
                        <p class="text-slate-500 mt-2">Manage room images for landing and booking pages.</p>
                    </a>
                </div>
            </div>
        </main>
    </div>

</body>
</html>