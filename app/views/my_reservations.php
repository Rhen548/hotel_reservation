<?php
$title = "My Reservations";
$error_msg = '';
$reservations = [];

if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id'])) {
    header("Location: " . url('customer-signin'));
    exit;
}

function reservationStatusClass($status) {
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

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $customerStmt = $pdo->prepare("
        SELECT customer_id, first_name, last_name, email, profile_picture
        FROM customers
        WHERE customer_id = :customer_id
        LIMIT 1
    ");
    $customerStmt->execute([
        ':customer_id' => $_SESSION['customer_id']
    ]);
    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Location: " . url('customer-signin'));
        exit;
    }

    $_SESSION['customer_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
    $_SESSION['customer_email'] = $customer['email'] ?? '';
    $_SESSION['customer_profile_picture'] = $customer['profile_picture'] ?? null;

    $stmt = $pdo->prepare("
        SELECT
            r.reservation_id,
            r.reservation_date,
            r.check_in_date,
            r.check_out_date,
            r.number_of_guests,
            r.total_amount,
            r.status,
            r.special_requests,
            rm.room_id,
            rm.room_number,
            rm.price_per_night,
            rm.capacity,
            rt.type_name,
            (
                SELECT ri.image_path
                FROM room_images ri
                WHERE ri.room_id = rm.room_id
                ORDER BY ri.image_id ASC
                LIMIT 1
            ) AS primary_image,
            (
                SELECT COALESCE(SUM(p.amount), 0)
                FROM payments p
                WHERE p.reservation_id = r.reservation_id
                  AND p.payment_status = 'completed'
            ) AS total_paid
        FROM reservations r
        LEFT JOIN reservation_rooms rr
            ON r.reservation_id = rr.reservation_id
        LEFT JOIN rooms rm
            ON rr.room_id = rm.room_id
        LEFT JOIN room_types rt
            ON rm.type_id = rt.type_id
        WHERE r.customer_id = :customer_id
        ORDER BY r.reservation_id DESC
    ");
    $stmt->execute([
        ':customer_id' => $_SESSION['customer_id']
    ]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <a href="<?= url('my-reservations') ?>" class="text-primary font-semibold">My Reservations</a>

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
            </div>
        </div>
    </nav>

    <!-- PAGE -->
    <div class="max-w-7xl mx-auto px-6 lg:px-10 py-10 pt-32">
        <div class="mb-8">
            <h1 class="text-4xl font-extrabold text-slate-900">My Reservations</h1>
            <p class="text-slate-500 mt-2">View and manage all your hotel bookings in one place.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reservations)): ?>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <?php foreach ($reservations as $reservation): ?>
                    <?php
                        $total_amount = (float) ($reservation['total_amount'] ?? 0);
                        $total_paid = (float) ($reservation['total_paid'] ?? 0);
                        $remaining_balance = $total_amount - $total_paid;
                    ?>
                    <div class="bg-white rounded-3xl shadow-lg overflow-hidden border border-slate-100">
                        <div class="grid md:grid-cols-5 gap-0">
                            <div class="md:col-span-2 h-64 bg-slate-200 overflow-hidden">
                                <?php if (!empty($reservation['primary_image'])): ?>
                                    <img
                                        src="<?= htmlspecialchars($reservation['primary_image']) ?>"
                                        alt="Room Image"
                                        class="w-full h-full object-cover"
                                        onerror="this.src='https://via.placeholder.com/700x500?text=No+Image';"
                                    >
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-500 text-lg font-semibold">
                                        No Image
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="md:col-span-3 p-6">
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-slate-500">Reservation ID</p>
                                        <h2 class="text-2xl font-extrabold text-slate-900">
                                            #<?= htmlspecialchars($reservation['reservation_id']) ?>
                                        </h2>
                                    </div>

                                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= reservationStatusClass($reservation['status']) ?>">
                                        <?= htmlspecialchars($reservation['status']) ?>
                                    </span>
                                </div>

                                <div class="space-y-3 text-slate-700">
                                    <div>
                                        <p class="text-sm text-slate-500">Room</p>
                                        <p class="font-semibold">
                                            Room <?= htmlspecialchars($reservation['room_number'] ?? '-') ?> - <?= htmlspecialchars($reservation['type_name'] ?? 'N/A') ?>
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-slate-500">Check-in</p>
                                            <p class="font-medium"><?= htmlspecialchars($reservation['check_in_date'] ?? '-') ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-slate-500">Check-out</p>
                                            <p class="font-medium"><?= htmlspecialchars($reservation['check_out_date'] ?? '-') ?></p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-slate-500">Guests</p>
                                            <p class="font-medium"><?= htmlspecialchars($reservation['number_of_guests'] ?? '-') ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-slate-500">Reservation Date</p>
                                            <p class="font-medium"><?= htmlspecialchars($reservation['reservation_date'] ?? '-') ?></p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-4 pt-2">
                                        <div>
                                            <p class="text-sm text-slate-500">Total</p>
                                            <p class="font-bold text-primary">₱<?= number_format($total_amount, 2) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-slate-500">Paid</p>
                                            <p class="font-bold text-green-600">₱<?= number_format($total_paid, 2) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-slate-500">Balance</p>
                                            <p class="font-bold <?= $remaining_balance > 0 ? 'text-red-600' : 'text-green-600' ?>">
                                                ₱<?= number_format($remaining_balance, 2) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3 mt-6">
                                    <a
                                        href="<?= url('booking-confirmation?reservation_id=' . urlencode($reservation['reservation_id'])) ?>"
                                        class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-semibold transition"
                                    >
                                        View Confirmation
                                    </a>

                                    <a
                                        href="<?= url('track-reservation') ?>"
                                        class="bg-slate-200 hover:bg-slate-300 text-slate-800 px-4 py-2 rounded-xl font-semibold transition"
                                    >
                                        Track Reservation
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl shadow-lg border border-slate-100 p-10 text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-4xl mb-5">
                    🏨
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-3">No Reservations Yet</h2>
                <p class="text-slate-500 max-w-xl mx-auto mb-6">
                    You do not have any reservations yet. Start exploring rooms and make your first booking.
                </p>
                <a
                    href="<?= url('search-rooms') ?>"
                    class="inline-block bg-primary hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-semibold transition"
                >
                    Search Rooms
                </a>
            </div>
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