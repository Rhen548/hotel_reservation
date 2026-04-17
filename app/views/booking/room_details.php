<?php
$title = "Room Details";
$error_msg = '';
$room = null;
$images = [];

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

    // Get room details + primary image + room type name
    $roomStmt = $pdo->prepare("
        SELECT
            r.*,
            rt.type_name,
            (
                SELECT ri.image_path
                FROM room_images ri
                WHERE ri.room_id = r.room_id
                ORDER BY ri.image_id ASC
                LIMIT 1
            ) AS primary_image
        FROM rooms r
        LEFT JOIN room_types rt ON r.type_id = rt.type_id
        WHERE r.room_id = :room_id
        LIMIT 1
    ");
    $roomStmt->execute([':room_id' => $room_id]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        die("Room not found.");
    }

    // Get all room images
    $imageStmt = $pdo->prepare("
        SELECT image_id, image_path
        FROM room_images
        WHERE room_id = :room_id
        ORDER BY image_id ASC
    ");
    $imageStmt->execute([':room_id' => $room_id]);
    $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($room['type_name'] ?? $title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "#2563eb",
            accent: "#22c55e",
            dark: "#0f172a"
          }
        }
      }
    }
    </script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <!-- NAVBAR -->
    <nav class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 py-4 flex items-center justify-between">
            <a href="<?= url('/') ?>" class="text-2xl font-extrabold text-primary">HotelSys</a>

            <div class="space-x-4">
                <a href="<?= url('/') ?>" class="text-slate-600 hover:text-primary">Home</a>
                <a href="<?= url('search-rooms') ?>" class="text-slate-600 hover:text-primary">Search Rooms</a>
                <a href="<?= url('admin/login') ?>" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Admin Login
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 lg:px-10 py-10">

        <?php if (!empty($error_msg)): ?>
            <div class="mb-8 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($room): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

                <!-- Left: Main Content -->
                <div class="lg:col-span-2">
                    <div class="mb-6">
                        <p class="text-sm font-semibold uppercase tracking-widest text-primary">Room Details</p>
                        <h1 class="text-4xl font-extrabold text-slate-900 mt-2">
                            Room <?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['type_name'] ?? 'N/A') ?>
                        </h1>
                        <p class="text-slate-500 mt-3">
                            A premium accommodation designed for comfort, convenience, and a memorable guest experience.
                        </p>
                    </div>

                    <!-- Main Image -->
                    <div class="bg-white rounded-3xl shadow-lg overflow-hidden mb-8">
                        <div class="w-full h-[420px] bg-slate-200 overflow-hidden">
                            <?php if (!empty($room['primary_image'])): ?>
                                <img
                                    src="<?= htmlspecialchars($room['primary_image']) ?>"
                                    alt="Room Image"
                                    class="w-full h-full object-cover"
                                    onerror="this.src='https://via.placeholder.com/900x600?text=No+Image';"
                                >
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-500 text-2xl font-semibold">
                                    No Image Available
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Gallery -->
                    <div class="bg-white rounded-3xl shadow-lg p-6 mb-8">
                        <h2 class="text-2xl font-bold text-slate-900 mb-5">Room Gallery</h2>

                        <?php if (!empty($images)): ?>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($images as $image): ?>
                                    <div class="h-40 rounded-2xl overflow-hidden bg-slate-200">
                                        <img
                                            src="<?= htmlspecialchars($image['image_path']) ?>"
                                            alt="Room Gallery Image"
                                            class="w-full h-full object-cover"
                                            onerror="this.src='https://via.placeholder.com/400x300?text=No+Image';"
                                        >
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-slate-500">No additional room images available.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="bg-white rounded-3xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-slate-900 mb-4">About This Room</h2>
                        <p class="text-slate-600 leading-relaxed">
                            <?= nl2br(htmlspecialchars($room['description'] ?: 'Comfortable and elegant accommodation prepared for your hotel stay.')) ?>
                        </p>
                    </div>
                </div>

                <!-- Right: Summary Card -->
                <div>
                    <div class="bg-white rounded-3xl shadow-lg p-8 sticky top-6">
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-slate-900">Booking Summary</h2>
                            <p class="text-slate-500 mt-1">Review room details before booking.</p>
                        </div>

                        <div class="space-y-5 text-slate-700">
                            <div>
                                <p class="text-sm text-slate-500">Room Number</p>
                                <p class="font-bold text-lg"><?= htmlspecialchars($room['room_number']) ?></p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Room Type</p>
                                <p class="font-medium"><?= htmlspecialchars($room['type_name'] ?? 'N/A') ?></p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Capacity</p>
                                <p class="font-medium"><?= htmlspecialchars($room['capacity']) ?> Guest(s)</p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Status</p>
                                <p class="font-medium capitalize"><?= htmlspecialchars($room['status']) ?></p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Price Per Night</p>
                                <p class="text-3xl font-extrabold text-primary">
                                    ₱<?= number_format((float)$room['price_per_night'], 2) ?>
                                    <span class="text-sm font-medium text-slate-500">/night</span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 space-y-3">
                            <a href="<?= url('search-rooms') ?>"
                               class="block w-full text-center bg-primary hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition">
                                Search & Book
                            </a>

                            <a href="<?= url('/') ?>"
                               class="block w-full text-center bg-slate-200 hover:bg-slate-300 text-slate-800 py-3 rounded-xl font-semibold transition">
                                Back to Home
                            </a>
                        </div>

                        <div class="mt-6 pt-6 border-t border-slate-200 text-sm text-slate-500">
                            You can continue to the search page to choose your dates and complete your reservation.
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </div>

</body>
</html>