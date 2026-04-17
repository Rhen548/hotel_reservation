<?php
$title = "Track Reservation";
$error_msg = '';
$reservation = null;
$payments = [];
$total_paid = 0;
$remaining_balance = 0;

$reservation_id = trim($_POST['reservation_id'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($email === '' && !empty($_SESSION['customer_email'])) {
    $email = $_SESSION['customer_email'];
}

function reservationStatusClass($status) {
    return match ($status) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'checked_in' => 'bg-green-100 text-green-800',
        'checked_out' => 'bg-slate-200 text-slate-800',
        default => 'bg-gray-100 text-gray-800',
    };
}

function paymentStatusClass($status) {
    return match ($status) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'completed' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
        'refunded' => 'bg-slate-200 text-slate-800',
        default => 'bg-gray-100 text-gray-800',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($reservation_id === '' && $email === '') {
        $error_msg = "Please enter either a reservation ID or an email address.";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
                "root",
                ""
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($reservation_id !== '') {
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
                        c.first_name,
                        c.last_name,
                        c.email,
                        c.phone,
                        c.address,
                        rm.room_number,
                        rt.type_name,
                        (
                            SELECT ri.image_path
                            FROM room_images ri
                            WHERE ri.room_id = rm.room_id
                            ORDER BY ri.image_id ASC
                            LIMIT 1
                        ) AS primary_image
                    FROM reservations r
                    INNER JOIN customers c
                        ON r.customer_id = c.customer_id
                    LEFT JOIN reservation_rooms rr
                        ON r.reservation_id = rr.reservation_id
                    LEFT JOIN rooms rm
                        ON rr.room_id = rm.room_id
                    LEFT JOIN room_types rt
                        ON rm.type_id = rt.type_id
                    WHERE r.reservation_id = :reservation_id
                    LIMIT 1
                ");
                $stmt->execute([
                    ':reservation_id' => $reservation_id
                ]);
            } else {
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
                        c.first_name,
                        c.last_name,
                        c.email,
                        c.phone,
                        c.address,
                        rm.room_number,
                        rt.type_name,
                        (
                            SELECT ri.image_path
                            FROM room_images ri
                            WHERE ri.room_id = rm.room_id
                            ORDER BY ri.image_id ASC
                            LIMIT 1
                        ) AS primary_image
                    FROM reservations r
                    INNER JOIN customers c
                        ON r.customer_id = c.customer_id
                    LEFT JOIN reservation_rooms rr
                        ON r.reservation_id = rr.reservation_id
                    LEFT JOIN rooms rm
                        ON rr.room_id = rm.room_id
                    LEFT JOIN room_types rt
                        ON rm.type_id = rt.type_id
                    WHERE c.email = :email
                    ORDER BY r.reservation_id DESC
                    LIMIT 1
                ");
                $stmt->execute([
                    ':email' => $email
                ]);
            }

            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                $error_msg = "No reservation found with the provided information.";
            } else {
                $paymentStmt = $pdo->prepare("
                    SELECT
                        payment_id,
                        payment_date,
                        amount,
                        payment_method,
                        payment_status,
                        transaction_reference
                    FROM payments
                    WHERE reservation_id = :reservation_id
                    ORDER BY payment_id DESC
                ");
                $paymentStmt->execute([
                    ':reservation_id' => $reservation['reservation_id']
                ]);
                $payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);

                $paidStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) AS total_paid
                    FROM payments
                    WHERE reservation_id = :reservation_id
                      AND payment_status = 'completed'
                ");
                $paidStmt->execute([
                    ':reservation_id' => $reservation['reservation_id']
                ]);
                $paidData = $paidStmt->fetch(PDO::FETCH_ASSOC);

                $total_paid = (float) ($paidData['total_paid'] ?? 0);
                $remaining_balance = (float) $reservation['total_amount'] - $total_paid;
            }

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

    <div class="max-w-7xl mx-auto px-6 lg:px-10 py-12 pt-32">

        <div class="mb-10 text-center">
            <h1 class="text-4xl font-extrabold text-slate-900">Track Your Reservation</h1>
            <p class="text-slate-500 mt-3">
                Enter your reservation ID or email address to view your booking details.
            </p>
        </div>

        <div class="max-w-3xl mx-auto bg-white rounded-3xl shadow-lg p-8 mb-10">
            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('track-reservation') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Reservation ID</label>
                    <input
                        type="text"
                        name="reservation_id"
                        value="<?= htmlspecialchars($reservation_id) ?>"
                        placeholder="Enter reservation ID"
                        class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        placeholder="Enter your email"
                        class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                </div>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="w-full bg-primary hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition"
                    >
                        Track Reservation
                    </button>
                </div>
            </form>
        </div>

        <?php if ($reservation): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-2 space-y-8">

                    <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
                        <div class="grid md:grid-cols-2 gap-0">
                            <div class="h-80 bg-slate-200 overflow-hidden">
                                <?php if (!empty($reservation['primary_image'])): ?>
                                    <img
                                        src="<?= htmlspecialchars($reservation['primary_image']) ?>"
                                        alt="Room Image"
                                        class="w-full h-full object-cover"
                                        onerror="this.src='https://via.placeholder.com/800x500?text=No+Image';"
                                    >
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-500 text-xl font-semibold">
                                        No Image
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="p-8">
                                <div class="mb-6">
                                    <p class="text-sm text-slate-500">Reservation ID</p>
                                    <h2 class="text-3xl font-extrabold text-slate-900">
                                        #<?= htmlspecialchars($reservation['reservation_id']) ?>
                                    </h2>
                                </div>

                                <div class="space-y-4 text-slate-700">
                                    <div>
                                        <p class="text-sm text-slate-500">Guest Name</p>
                                        <p class="font-semibold">
                                            <?= htmlspecialchars(trim(($reservation['first_name'] ?? '') . ' ' . ($reservation['last_name'] ?? ''))) ?>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-slate-500">Email</p>
                                        <p class="font-medium"><?= htmlspecialchars($reservation['email'] ?? '-') ?></p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-slate-500">Phone</p>
                                        <p class="font-medium"><?= htmlspecialchars(!empty($reservation['phone']) ? $reservation['phone'] : '-') ?></p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-slate-500">Room</p>
                                        <p class="font-medium">
                                            Room <?= htmlspecialchars($reservation['room_number'] ?? '-') ?> - <?= htmlspecialchars($reservation['type_name'] ?? 'N/A') ?>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-slate-500">Status</p>
                                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= reservationStatusClass($reservation['status']) ?>">
                                            <?= htmlspecialchars($reservation['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl shadow-lg p-8">
                        <h3 class="text-2xl font-bold text-slate-900 mb-6">Stay Details</h3>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-slate-500">Reservation Date</p>
                                <p class="font-medium"><?= htmlspecialchars($reservation['reservation_date'] ?? '-') ?></p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Number of Guests</p>
                                <p class="font-medium"><?= htmlspecialchars($reservation['number_of_guests'] ?? '-') ?></p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Check-in Date</p>
                                <p class="font-medium"><?= htmlspecialchars($reservation['check_in_date'] ?? '-') ?></p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Check-out Date</p>
                                <p class="font-medium"><?= htmlspecialchars($reservation['check_out_date'] ?? '-') ?></p>
                            </div>

                            <div class="md:col-span-2">
                                <p class="text-sm text-slate-500">Special Requests</p>
                                <p class="font-medium">
                                    <?= htmlspecialchars(!empty($reservation['special_requests']) ? $reservation['special_requests'] : 'No special requests.') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl shadow-lg p-8">
                        <h3 class="text-2xl font-bold text-slate-900 mb-6">Payment History</h3>

                        <?php if (!empty($payments)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-slate-50">
                                        <tr class="text-left text-sm text-slate-600">
                                            <th class="px-4 py-3 font-semibold">Payment ID</th>
                                            <th class="px-4 py-3 font-semibold">Date</th>
                                            <th class="px-4 py-3 font-semibold">Amount</th>
                                            <th class="px-4 py-3 font-semibold">Method</th>
                                            <th class="px-4 py-3 font-semibold">Status</th>
                                            <th class="px-4 py-3 font-semibold">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">#<?= htmlspecialchars($payment['payment_id']) ?></td>
                                                <td class="px-4 py-3"><?= htmlspecialchars($payment['payment_date']) ?></td>
                                                <td class="px-4 py-3">₱<?= number_format((float)$payment['amount'], 2) ?></td>
                                                <td class="px-4 py-3"><?= htmlspecialchars($payment['payment_method']) ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= paymentStatusClass($payment['payment_status']) ?>">
                                                        <?= htmlspecialchars($payment['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3"><?= htmlspecialchars(!empty($payment['transaction_reference']) ? $payment['transaction_reference'] : '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-slate-500">No payment records found for this reservation.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="bg-white rounded-3xl shadow-lg p-8 sticky top-28">
                        <h3 class="text-2xl font-bold text-slate-900 mb-6">Billing Summary</h3>

                        <div class="space-y-5 text-slate-700">
                            <div>
                                <p class="text-sm text-slate-500">Total Amount</p>
                                <p class="text-3xl font-extrabold text-primary">
                                    ₱<?= number_format((float)$reservation['total_amount'], 2) ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Total Paid</p>
                                <p class="text-2xl font-bold text-green-600">
                                    ₱<?= number_format($total_paid, 2) ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-500">Remaining Balance</p>
                                <p class="text-2xl font-bold <?= $remaining_balance > 0 ? 'text-red-600' : 'text-green-600' ?>">
                                    ₱<?= number_format($remaining_balance, 2) ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 space-y-3">
                            <a
                                href="<?= url('search-rooms') ?>"
                                class="block w-full text-center bg-primary hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition"
                            >
                                Book Another Room
                            </a>

                            <a
                                href="<?= url('homepage') ?>"
                                class="block w-full text-center bg-slate-200 hover:bg-slate-300 text-slate-800 py-3 rounded-xl font-semibold transition"
                            >
                                Back to Home
                            </a>
                        </div>

                        <div class="mt-6 pt-6 border-t border-slate-200 text-sm text-slate-500">
                            Keep your reservation ID safe for faster tracking next time.
                        </div>
                    </div>
                </div>

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