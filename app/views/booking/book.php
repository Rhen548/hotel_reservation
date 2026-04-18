<?php
$title = "Book Room";
$error_msg = '';

$room = null;
$room_id = (int) ($_GET['room_id'] ?? 0);
$check_in = trim($_GET['check_in'] ?? '');
$check_out = trim($_GET['check_out'] ?? '');
$guests = (int) ($_GET['guests'] ?? 1);

if ($room_id <= 0 || empty($check_in) || empty($check_out)) {
    die("Invalid booking request.");
}

$first_name = '';
$last_name = '';
$email = '';
$phone = '';
$address = '';
$special_requests = '';

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // If signed in, preload customer data
    $logged_in_customer = null;
    if (!empty($_SESSION['customer_id'])) {
        $customerStmt = $pdo->prepare("
            SELECT customer_id, first_name, last_name, email, phone, address, profile_picture
            FROM customers
            WHERE customer_id = :customer_id
            LIMIT 1
        ");
        $customerStmt->execute([
            ':customer_id' => $_SESSION['customer_id']
        ]);
        $logged_in_customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

        if ($logged_in_customer) {
            $first_name = $logged_in_customer['first_name'] ?? '';
            $last_name = $logged_in_customer['last_name'] ?? '';
            $email = $logged_in_customer['email'] ?? '';
            $phone = $logged_in_customer['phone'] ?? '';
            $address = $logged_in_customer['address'] ?? '';
        }
    }

    // Get room details + primary image + room type name
    $stmt = $pdo->prepare("
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
          AND r.status = 'available'
    ");
    $stmt->execute([':room_id' => $room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        die("Room not found or not available.");
    }

    if ($check_out <= $check_in) {
        die("Invalid date range.");
    }

    $availabilitySql = "
        SELECT COUNT(*)
        FROM reservation_rooms rr
        INNER JOIN reservations res ON rr.reservation_id = res.reservation_id
        WHERE rr.room_id = :room_id
          AND res.status IN ('pending', 'confirmed', 'checked_in')
          AND (
              :check_in < res.check_out_date
              AND :check_out > res.check_in_date
          )
    ";

    $availabilityStmt = $pdo->prepare($availabilitySql);
    $availabilityStmt->execute([
        ':room_id' => $room_id,
        ':check_in' => $check_in,
        ':check_out' => $check_out,
    ]);

    $existingBookings = (int) $availabilityStmt->fetchColumn();

    if ($existingBookings > 0) {
        die("This room is no longer available for the selected dates.");
    }

    $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    $total_amount = $nights * (float) $room['price_per_night'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $special_requests = trim($_POST['special_requests'] ?? '');

        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_msg = "First name, last name, and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please enter a valid email address.";
        } elseif ($guests < 1) {
            $error_msg = "Number of guests must be at least 1.";
        } elseif ($guests > (int) $room['capacity']) {
            $error_msg = "The selected room cannot accommodate that number of guests.";
        } else {
            $pdo->beginTransaction();

            try {
                // Re-check availability before final insert
                $availabilityStmt = $pdo->prepare($availabilitySql);
                $availabilityStmt->execute([
                    ':room_id' => $room_id,
                    ':check_in' => $check_in,
                    ':check_out' => $check_out,
                ]);

                $existingBookings = (int) $availabilityStmt->fetchColumn();

                if ($existingBookings > 0) {
                    throw new Exception("This room is no longer available for the selected dates.");
                }

                if (!empty($_SESSION['customer_id'])) {
                    // Use signed-in customer account
                    $customer_id = (int) $_SESSION['customer_id'];

                    $updateCustomerStmt = $pdo->prepare("
                        UPDATE customers
                        SET first_name = :first_name,
                            last_name = :last_name,
                            email = :email,
                            phone = :phone,
                            address = :address,
                            updated_at = NOW()
                        WHERE customer_id = :customer_id
                    ");

                    $updateCustomerStmt->execute([
                        ':first_name' => $first_name,
                        ':last_name' => $last_name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':address' => $address,
                        ':customer_id' => $customer_id,
                    ]);

                    $_SESSION['customer_name'] = trim($first_name . ' ' . $last_name);
                    $_SESSION['customer_email'] = $email;
                } else {
                    $customerCheckStmt = $pdo->prepare("
                        SELECT customer_id
                        FROM customers
                        WHERE email = :email
                        LIMIT 1
                    ");
                    $customerCheckStmt->execute([':email' => $email]);
                    $existingCustomer = $customerCheckStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingCustomer) {
                        $customer_id = (int) $existingCustomer['customer_id'];

                        $updateCustomerStmt = $pdo->prepare("
                            UPDATE customers
                            SET first_name = :first_name,
                                last_name = :last_name,
                                phone = :phone,
                                address = :address,
                                updated_at = NOW()
                            WHERE customer_id = :customer_id
                        ");

                        $updateCustomerStmt->execute([
                            ':first_name' => $first_name,
                            ':last_name' => $last_name,
                            ':phone' => $phone,
                            ':address' => $address,
                            ':customer_id' => $customer_id,
                        ]);
                    } else {
                        $insertCustomerStmt = $pdo->prepare("
                            INSERT INTO customers (
                                first_name,
                                last_name,
                                email,
                                phone,
                                address,
                                created_at,
                                updated_at
                            )
                            VALUES (
                                :first_name,
                                :last_name,
                                :email,
                                :phone,
                                :address,
                                NOW(),
                                NOW()
                            )
                        ");

                        $insertCustomerStmt->execute([
                            ':first_name' => $first_name,
                            ':last_name' => $last_name,
                            ':email' => $email,
                            ':phone' => $phone,
                            ':address' => $address,
                        ]);

                        $customer_id = (int) $pdo->lastInsertId();
                    }
                }

                $insertReservationStmt = $pdo->prepare("
                    INSERT INTO reservations (
                        customer_id,
                        check_in_date,
                        check_out_date,
                        number_of_guests,
                        total_amount,
                        status,
                        special_requests
                    ) VALUES (
                        :customer_id,
                        :check_in_date,
                        :check_out_date,
                        :number_of_guests,
                        :total_amount,
                        'pending',
                        :special_requests
                    )
                ");

                $insertReservationStmt->execute([
                    ':customer_id' => $customer_id,
                    ':check_in_date' => $check_in,
                    ':check_out_date' => $check_out,
                    ':number_of_guests' => $guests,
                    ':total_amount' => $total_amount,
                    ':special_requests' => $special_requests,
                ]);

                $reservation_id = (int) $pdo->lastInsertId();

                $insertReservationRoomStmt = $pdo->prepare("
                    INSERT INTO reservation_rooms (
                        reservation_id,
                        room_id,
                        price_per_night_at_booking
                    ) VALUES (
                        :reservation_id,
                        :room_id,
                        :price_per_night
                    )
                ");

                $insertReservationRoomStmt->execute([
                    ':reservation_id' => $reservation_id,
                    ':room_id' => $room_id,
                    ':price_per_night' => $room['price_per_night'],
                ]);

                $pdo->commit();

                header("Location: " . url("booking-confirmation?reservation_id=" . $reservation_id));
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error_msg = "Booking failed: " . $e->getMessage();
            }
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
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
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 bg-white rounded-3xl shadow-lg p-8">
                <h1 class="text-3xl font-extrabold text-slate-900 mb-2">Complete Your Booking</h1>
                <p class="text-slate-500 mb-8">Fill in your details to reserve this room.</p>

                <?php if (!empty($error_msg)): ?>
                    <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                        <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('book-room?room_id=' . urlencode($room_id) . '&check_in=' . urlencode($check_in) . '&check_out=' . urlencode($check_out) . '&guests=' . urlencode($guests)) ?>" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">First Name</label>
                            <input
                                type="text"
                                name="first_name"
                                value="<?= htmlspecialchars($first_name) ?>"
                                class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Last Name</label>
                            <input
                                type="text"
                                name="last_name"
                                value="<?= htmlspecialchars($last_name) ?>"
                                class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                                required
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                            <input
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($email) ?>"
                                class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Phone</label>
                            <input
                                type="text"
                                name="phone"
                                value="<?= htmlspecialchars($phone) ?>"
                                class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Address</label>
                        <textarea
                            name="address"
                            rows="3"
                            class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                        ><?= htmlspecialchars($address) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Special Requests</label>
                        <textarea
                            name="special_requests"
                            rows="4"
                            class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Optional requests, arrival notes, bed preference, etc."
                        ><?= htmlspecialchars($special_requests) ?></textarea>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-primary hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition"
                    >
                        Confirm Booking
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-3xl shadow-lg p-8 h-fit">
                <h2 class="text-2xl font-bold text-slate-900 mb-6">Booking Summary</h2>

                <div class="h-48 bg-slate-200 rounded-2xl mb-6 overflow-hidden">
                    <?php if (!empty($room['primary_image'])): ?>
                        <img
                            src="<?= htmlspecialchars($room['primary_image']) ?>"
                            alt="Room Image"
                            class="w-full h-full object-cover"
                            onerror="this.src='https://via.placeholder.com/600x400?text=No+Image';"
                        >
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-slate-500 font-semibold">
                            No Image
                        </div>
                    <?php endif; ?>
                </div>

                <div class="space-y-4 text-slate-700">
                    <div>
                        <p class="text-sm text-slate-500">Room</p>
                        <p class="font-bold text-lg">Room <?= htmlspecialchars($room['room_number']) ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Room Type</p>
                        <p class="font-medium"><?= htmlspecialchars($room['type_name'] ?? 'N/A') ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Guests</p>
                        <p class="font-medium"><?= htmlspecialchars($guests) ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Check-in</p>
                        <p class="font-medium"><?= htmlspecialchars($check_in) ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Check-out</p>
                        <p class="font-medium"><?= htmlspecialchars($check_out) ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Nights</p>
                        <p class="font-medium"><?= htmlspecialchars($nights) ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Price per Night</p>
                        <p class="font-medium">₱<?= number_format((float)$room['price_per_night'], 2) ?></p>
                    </div>

                    <div class="pt-4 border-t">
                        <p class="text-sm text-slate-500">Total Amount</p>
                        <p class="text-3xl font-extrabold text-primary">
                            ₱<?= number_format($total_amount, 2) ?>
                        </p>
                    </div>
                </div>
            </div>

        </div>
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