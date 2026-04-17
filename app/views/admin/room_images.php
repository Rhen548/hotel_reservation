<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php
$title = "Room Images Module";
$error_msg = '';
$rooms = [];
$roomImages = [];

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all rooms
    $roomStmt = $pdo->query("
        SELECT room_id, room_number, room_type
        FROM rooms
        ORDER BY room_number ASC
    ");
    $rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all room images with room info
    $imageStmt = $pdo->query("
        SELECT
            ri.image_id,
            ri.room_id,
            ri.image_path,
            r.room_number,
            r.room_type
        FROM room_images ri
        INNER JOIN rooms r
            ON ri.room_id = r.room_id
        ORDER BY ri.image_id DESC
    ");
    $roomImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

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
                <a href="<?= url('admin/room-images') ?>" class="block px-4 py-3 rounded-xl bg-blue-600">Room Images</a>
                <a href="<?= url('admin/audit-logs') ?>" class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Audit Logs</a>
                <a href="<?= url('admin/logout') ?>"  class="block px-4 py-3 rounded-xl hover:bg-slate-800 transition">Logout</a>
            </nav>
        </aside>

    <main class="flex-1 p-6 lg:p-8">
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold text-slate-900">Room Images Module</h2>
            <p class="text-slate-500 mt-2">Manage room image paths for room display and booking pages.</p>
        </div>

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

        <!-- Add Image Form -->
        <div class="bg-white rounded-3xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-bold text-slate-900 mb-6">Add Room Image</h3>

            <form method="POST" action="<?= url('admin/room-images/store') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Select Room</label>
                    <select name="room_id" class="w-full border border-slate-300 p-3 rounded-xl" required>
                        <option value="">Choose room</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= htmlspecialchars($room['room_id']) ?>">
                                Room <?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['room_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Image Path / URL</label>
                    <input type="text" name="image_path"
                           class="w-full border border-slate-300 p-3 rounded-xl"
                           placeholder="e.g. /uploads/room1.jpg or https://..."
                           required>
                </div>

                <div class="md:col-span-2">
                    <button class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
                        Save Room Image
                    </button>
                </div>
            </form>
        </div>

        <!-- Image List -->
        <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200">
                <h3 class="text-xl font-bold text-slate-900">Room Image List</h3>
            </div>

            <div class="p-6">
                <?php if (!empty($roomImages)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach ($roomImages as $image): ?>
                            <div class="border border-slate-200 rounded-3xl overflow-hidden bg-white shadow-sm">
                                <div class="h-56 bg-slate-100 flex items-center justify-center overflow-hidden">
                                    <img src="<?= htmlspecialchars($image['image_path']) ?>"
                                         alt="Room Image"
                                         class="w-full h-full object-cover">
                                </div>

                                <div class="p-5">
                                    <h4 class="text-lg font-bold text-slate-900 mb-1">
                                        Room <?= htmlspecialchars($image['room_number']) ?>
                                    </h4>
                                    <p class="text-slate-500 mb-3"><?= htmlspecialchars($image['room_type']) ?></p>

                                    <p class="text-xs text-slate-500 break-all mb-4">
                                        <?= htmlspecialchars($image['image_path']) ?>
                                    </p>

                                    <form method="POST" action="<?= url('admin/room-images/delete') ?>" onsubmit="return confirm('Delete this room image?')">
                                        <input type="hidden" name="image_id" value="<?= htmlspecialchars($image['image_id']) ?>">
                                        <button class="bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-red-700 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-slate-500 py-10">
                        No room images found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>