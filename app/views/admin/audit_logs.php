<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php $current_page = 'audit_logs'; ?>

<?php
$title = "Audit Logs";
$error_msg = '';
$auditLogs = [];

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("
        SELECT
            al.log_id,
            al.admin_id,
            al.action,
            al.created_at,
            a.username,
            a.email,
            a.role
        FROM audit_logs al
        LEFT JOIN admins a
            ON al.admin_id = a.admin_id
        ORDER BY al.log_id DESC
    ");

    $auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <a href="<?= url('admin/reports') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Reports</a>
            <a href="<?= url('admin/rooms') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Rooms</a>
            <a href="<?= url('admin/room-images') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Room Images</a>
            <a href="<?= url('admin/audit-logs') ?>" class="block px-4 py-3 rounded-xl bg-blue-600">Audit Logs</a>
            <a href="<?= url('admin/logout') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 lg:p-8">
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold text-slate-900">Audit Logs</h2>
            <p class="text-slate-500 mt-2">View all recorded admin actions and system activity history.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- Summary Card -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-2xl shadow p-5">
                <p class="text-sm text-slate-500">Total Logs</p>
                <h3 class="text-3xl font-extrabold text-slate-900 mt-2"><?= count($auditLogs) ?></h3>
            </div>

            <div class="bg-white rounded-2xl shadow p-5">
                <p class="text-sm text-slate-500">Latest Activity</p>
                <h3 class="text-lg font-bold text-slate-900 mt-2">
                    <?= !empty($auditLogs) ? htmlspecialchars($auditLogs[0]['created_at']) : 'No activity yet' ?>
                </h3>
            </div>

            <div class="bg-white rounded-2xl shadow p-5">
                <p class="text-sm text-slate-500">Tracked Module</p>
                <h3 class="text-lg font-bold text-slate-900 mt-2">Admin Actions</h3>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200">
                <h3 class="text-xl font-bold text-slate-900">Activity History</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-sm text-slate-600">
                            <th class="px-6 py-4 font-semibold">Log ID</th>
                            <th class="px-6 py-4 font-semibold">Admin</th>
                            <th class="px-6 py-4 font-semibold">Role</th>
                            <th class="px-6 py-4 font-semibold">Action</th>
                            <th class="px-6 py-4 font-semibold">Date & Time</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        <?php if (!empty($auditLogs)): ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-bold text-slate-900">
                                        #<?= htmlspecialchars($log['log_id']) ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">
                                            <?= htmlspecialchars($log['username'] ?: 'Unknown Admin') ?>
                                        </div>
                                        <div class="text-sm text-slate-500">
                                            <?= htmlspecialchars($log['email'] ?: '-') ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        <?= htmlspecialchars($log['role'] ?: '-') ?>
                                    </td>

                                    <td class="px-6 py-4 text-slate-800 max-w-xl">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                        <?= htmlspecialchars($log['created_at']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                    No audit logs found.
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