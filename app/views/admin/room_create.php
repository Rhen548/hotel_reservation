<?php include __DIR__ . '/partials/auth_guard.php'; ?>

<?php
$title = "Add Room";

// CONNECT DATABASE
$pdo = new PDO("mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// GET ROOM TYPES
$types = $pdo->query("SELECT * FROM room_types")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-3xl bg-white rounded-3xl shadow-lg p-8">

    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Add New Room</h1>
        <p class="text-slate-500 mt-2">Create a new room entry for your hotel.</p>
    </div>

    <form method="POST" action="<?= url('admin/rooms/store') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <!-- ROOM NUMBER -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Room Number</label>
            <input type="text" name="room_number" class="w-full border border-slate-300 p-3 rounded-xl" required>
        </div>

        <!-- ✅ ROOM TYPE DROPDOWN -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Room Type</label>
            <select name="type_id" class="w-full border border-slate-300 p-3 rounded-xl" required>
                <option value="">Select Room Type</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= $type['type_id'] ?>">
                        <?= htmlspecialchars($type['type_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- PRICE -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Price Per Night</label>
            <input type="number" step="0.01" min="0" name="price_per_night" class="w-full border border-slate-300 p-3 rounded-xl" required>
        </div>

        <!-- CAPACITY -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Capacity</label>
            <input type="number" min="1" name="capacity" class="w-full border border-slate-300 p-3 rounded-xl" required>
        </div>

        <!-- STATUS -->
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
            <select name="status" class="w-full border border-slate-300 p-3 rounded-xl" required>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Maintenance</option>
                <option value="cleaning">Cleaning</option>
            </select>
        </div>

        <!-- DESCRIPTION -->
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
            <textarea name="description" rows="4" class="w-full border border-slate-300 p-3 rounded-xl"></textarea>
        </div>

        <!-- BUTTONS -->
        <div class="md:col-span-2 flex gap-3">
            <button class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
                Save Room
            </button>

            <a href="<?= url('admin/rooms') ?>" class="bg-slate-200 text-slate-800 px-6 py-3 rounded-xl font-semibold hover:bg-slate-300 transition">
                Cancel
            </a>
        </div>

    </form>

</div>

</body>
</html>