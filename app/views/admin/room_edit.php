<?php include __DIR__ . '/partials/auth_guard.php'; ?>

<?php
$room_id = (int) ($_GET['id'] ?? 0);
if ($room_id <= 0) {
    die("Invalid room ID.");
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get room info
    $stmt = $pdo->prepare("
        SELECT *
        FROM rooms
        WHERE room_id = :room_id
        LIMIT 1
    ");
    $stmt->execute([':room_id' => $room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        die("Room not found.");
    }

    // Get all room types for dropdown
    $typeStmt = $pdo->query("
        SELECT type_id, type_name
        FROM room_types
        ORDER BY type_name ASC
    ");
    $roomTypes = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-3xl bg-white rounded-3xl shadow-lg p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Edit Room</h1>
        <p class="text-slate-500 mt-2">Update room information and availability status.</p>
    </div>

    <form method="POST" action="<?= url('admin/rooms/update') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <input type="hidden" name="room_id" value="<?= htmlspecialchars($room['room_id']) ?>">

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Room Number</label>
            <input
                type="text"
                name="room_number"
                value="<?= htmlspecialchars($room['room_number']) ?>"
                class="w-full border border-slate-300 p-3 rounded-xl"
                required
            >
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Room Type</label>
            <select
                name="type_id"
                class="w-full border border-slate-300 p-3 rounded-xl"
                required
            >
                <option value="">Select Room Type</option>
                <?php foreach ($roomTypes as $type): ?>
                    <option
                        value="<?= htmlspecialchars($type['type_id']) ?>"
                        <?= (int)$room['type_id'] === (int)$type['type_id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($type['type_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Price Per Night</label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="price_per_night"
                value="<?= htmlspecialchars($room['price_per_night']) ?>"
                class="w-full border border-slate-300 p-3 rounded-xl"
                required
            >
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Capacity</label>
            <input
                type="number"
                min="1"
                name="capacity"
                value="<?= htmlspecialchars($room['capacity']) ?>"
                class="w-full border border-slate-300 p-3 rounded-xl"
                required
            >
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
            <select name="status" class="w-full border border-slate-300 p-3 rounded-xl" required>
                <option value="available" <?= $room['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                <option value="occupied" <?= $room['status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                <option value="maintenance" <?= $room['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                <option value="cleaning" <?= $room['status'] === 'cleaning' ? 'selected' : '' ?>>Cleaning</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
            <textarea
                name="description"
                rows="4"
                class="w-full border border-slate-300 p-3 rounded-xl"
            ><?= htmlspecialchars($room['description']) ?></textarea>
        </div>

        <div class="md:col-span-2 flex gap-3">
            <button class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
                Update Room
            </button>

            <a href="<?= url('admin/rooms') ?>" class="bg-slate-200 text-slate-800 px-6 py-3 rounded-xl font-semibold hover:bg-slate-300 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

</body>
</html>