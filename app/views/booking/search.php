<?php
$title = "Search Available Rooms";
$rooms = [];
$error_msg = '';
$check_in = '';
$check_out = '';
$guests = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = trim($_POST['check_in'] ?? '');
    $check_out = trim($_POST['check_out'] ?? '');
    $guests = (int) ($_POST['guests'] ?? 1);

    if (empty($check_in) || empty($check_out)) {
        $error_msg = "Please select both check-in and check-out dates.";
    } elseif ($check_out <= $check_in) {
        $error_msg = "Check-out date must be later than check-in date.";
    } elseif ($guests < 1) {
        $error_msg = "Number of guests must be at least 1.";
    } else {
        $sql = "
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
            WHERE r.status = 'available'
              AND r.capacity >= :guests
              AND r.room_id NOT IN (
                  SELECT rr.room_id
                  FROM reservation_rooms rr
                  INNER JOIN reservations res
                      ON rr.reservation_id = res.reservation_id
                  WHERE res.status IN ('pending', 'confirmed', 'checked_in')
                    AND (
                        :check_in < res.check_out_date
                        AND :check_out > res.check_in_date
                    )
              )
            ORDER BY r.price_per_night ASC
        ";

        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
                "root",
                ""
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':guests' => $guests,
                ':check_in' => $check_in,
                ':check_out' => $check_out,
            ]);

            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
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
<body class="bg-slate-50 min-h-screen text-slate-800">

    <!-- NAVBAR -->
    <nav class="fixed top-0 left-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 py-4 flex items-center justify-between">
            <div>
                <a href="<?= url('homepage') ?>" class="block">
                    <h1 class="text-2xl font-extrabold tracking-tight text-primary">HotelSys</h1>
                    <p class="text-xs text-slate-500 -mt-1">Luxury Hotel Reservation</p>
                </a>
            </div>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium">
                <a href="<?= url('homepage') ?>" class="hover:text-primary transition">Home</a>
                <a href="<?= url('search-rooms') ?>" class="hover:text-primary transition">Search Rooms</a>
                <a href="<?= url('track-reservation') ?>" class="hover:text-primary transition">Track Reservation</a>

                <?php if (isset($_SESSION['customer_id'])): ?>
                    <div class="relative">
                        <button
                            id="customerMenuButton"
                            type="button"
                            class="flex items-center gap-3 bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 transition"
                        >
                            <?php if (!empty($_SESSION['customer_profile_picture'])): ?>
                                <img
                                    src="<?= htmlspecialchars(url($_SESSION['customer_profile_picture'])) ?>"
                                    alt="Profile"
                                    class="w-8 h-8 rounded-full object-cover border border-white"
                                >
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-white text-blue-600 flex items-center justify-center font-bold">
                                    <?= strtoupper(substr($_SESSION['customer_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>

                            <span><?= htmlspecialchars($_SESSION['customer_name']) ?></span>
                        </button>

                        <div
                            id="customerDropdown"
                            class="hidden absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden z-50"
                        >
                            <div class="px-4 py-3 border-b bg-slate-50">
                                <p class="text-sm text-slate-500">My Account</p>
                                <p class="font-semibold text-slate-800 truncate">
                                    <?= htmlspecialchars($_SESSION['customer_email'] ?? '') ?>
                                </p>
                            </div>

                            <a
                            href="<?= url('my-reservations') ?>"
                            class="block px-4 py-3 text-slate-700 hover:bg-slate-100 font-medium"
                        >
                             My Reservations
                        </a>

                            <a
                                href="<?= url('customer-profile') ?>"
                                class="block px-4 py-3 text-slate-700 hover:bg-slate-100 font-medium"
                            >
                                Profile
                            </a>

                            <a
                                href="<?= url('customer-signout') ?>"
                                class="block px-4 py-3 text-red-600 hover:bg-red-50 font-semibold"
                            >
                                SIGN OUT
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a
                        href="<?= url('customer-signin') ?>"
                        class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 transition"
                    >
                        Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-10 pt-32">
        <div class="mb-8">
            <h1 class="text-4xl font-extrabold text-slate-900">Search Available Rooms</h1>
            <p class="text-slate-500 mt-2">Find the perfect room for your stay.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-10 border border-slate-100">
            <form method="POST" action="<?= url('search-rooms') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Check-in</label>
                    <input
                        type="date"
                        name="check_in"
                        value="<?= htmlspecialchars($check_in) ?>"
                        class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Check-out</label>
                    <input
                        type="date"
                        name="check_out"
                        value="<?= htmlspecialchars($check_out) ?>"
                        class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Guests</label>
                    <input
                        type="number"
                        name="guests"
                        min="1"
                        value="<?= htmlspecialchars($guests ?: '1') ?>"
                        class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                        required
                    >
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full bg-primary hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition"
                    >
                        Search Rooms
                    </button>
                </div>
            </form>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_msg)): ?>
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-slate-900">Available Rooms</h2>
                <p class="text-slate-500"><?= count($rooms) ?> room(s) found</p>
            </div>

            <?php if (!empty($rooms)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($rooms as $room): ?>
                        <div class="bg-white rounded-3xl shadow-lg overflow-hidden border border-slate-100">

                            <div class="h-52 bg-slate-200 overflow-hidden">
                                <?php if (!empty($room['primary_image'])): ?>
                                    <img
                                        src="<?= htmlspecialchars($room['primary_image']) ?>"
                                        alt="Room Image"
                                        class="w-full h-full object-cover"
                                        onerror="this.src='https://via.placeholder.com/600x400?text=No+Image';"
                                    >
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-500 text-lg font-semibold">
                                        No Image
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="p-6">
                                <div class="flex items-start justify-between mb-3 gap-3">
                                    <div>
                                        <h3 class="text-xl font-bold text-slate-900">
                                            Room <?= htmlspecialchars($room['room_number']) ?>
                                        </h3>
                                        <p class="text-slate-500"><?= htmlspecialchars($room['type_name'] ?? 'N/A') ?></p>
                                    </div>

                                    <span class="bg-blue-50 text-primary px-3 py-1 rounded-full text-sm font-semibold whitespace-nowrap">
                                        <?= htmlspecialchars($room['capacity']) ?> Guest(s)
                                    </span>
                                </div>

                                <p class="text-slate-600 mb-4 min-h-[48px]">
                                    <?= htmlspecialchars($room['description'] ?: 'Comfortable and well-prepared accommodation for your stay.') ?>
                                </p>

                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-2xl font-extrabold text-primary">
                                        ₱<?= number_format((float)$room['price_per_night'], 2) ?>
                                        <span class="text-sm font-medium text-slate-500">/night</span>
                                    </p>

                                    <div class="flex gap-2">
                                        <a
                                            href="<?= url('room?id=' . $room['room_id']) ?>"
                                            class="bg-slate-200 hover:bg-slate-300 text-slate-800 px-4 py-2 rounded-xl font-semibold transition"
                                        >
                                            Details
                                        </a>

                                        <a
                                            href="<?= url('book-room?room_id=' . $room['room_id'] . '&check_in=' . urlencode($check_in) . '&check_out=' . urlencode($check_out) . '&guests=' . urlencode($guests)) ?>"
                                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl font-semibold transition"
                                        >
                                            Book
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-6 py-5 rounded-2xl">
                    No rooms are available for the selected dates and guest count.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuButton = document.getElementById('customerMenuButton');
        const dropdown = document.getElementById('customerDropdown');

        if (menuButton && dropdown) {
            menuButton.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', function () {
                dropdown.classList.add('hidden');
            });

            dropdown.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }
    });
    </script>

</body>
</html>