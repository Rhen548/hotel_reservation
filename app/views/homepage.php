<?php
$title = "Hotel Reservation System";
$error_msg = '';
$featuredRooms = [];

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        ORDER BY r.room_id DESC
        LIMIT 6
    ";

    $stmt = $pdo->query($sql);
    $featuredRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        accent: "#22c553",
        dark: "#0f172a",
        soft: "#f8fafc",
      }
    }
  }
}
</script>
</head>

<body class="bg-slate-50 text-slate-800">

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
            <a href="#rooms" class="hover:text-primary transition">Rooms</a>
            <a href="#features" class="hover:text-primary transition">Features</a>
            <a href="#about" class="hover:text-primary transition">About</a>
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

<!-- HERO -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden">
    <div
        class="absolute inset-0 bg-cover bg-center"
        style="background-image: url('https://images.unsplash.com/photo-1566073771259-6a8506099945');">
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/75 via-slate-900/55 to-slate-900/35"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-10 w-full pt-28">
        <div class="grid lg:grid-cols-2 gap-10 items-center">

            <div class="text-white">
                <span class="inline-flex items-center rounded-full bg-white/15 px-4 py-2 text-sm font-medium backdrop-blur-md border border-white/20 mb-6">
                    Trusted Hotel Booking Experience
                </span>

                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                    Book Your Perfect
                    <span class="text-blue-300">Luxury Stay</span>
                    With Ease
                </h1>

                <p class="text-lg md:text-xl text-slate-200 max-w-xl mb-8 leading-relaxed">
                    Discover elegant rooms, real-time availability, seamless reservations, and a booking experience designed for comfort and convenience.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a
                        href="#search"
                        class="bg-primary hover:bg-blue-700 text-white px-7 py-4 rounded-2xl text-lg font-semibold shadow-xl transition text-center"
                    >
                        Book Now
                    </a>
                    <a
                        href="#rooms"
                        class="bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md text-white px-7 py-4 rounded-2xl text-lg font-semibold transition text-center"
                    >
                        Explore Rooms
                    </a>
                </div>
            </div>

            <!-- SEARCH CARD -->
            <div id="search" class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/40 p-6 md:p-8">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900">Find Your Stay</h2>
                    <p class="text-slate-500 mt-1">Search room availability in seconds.</p>
                </div>

                <form method="POST" action="<?= url('search-rooms') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Check-in</label>
                        <input
                            type="date"
                            name="check_in"
                            class="w-full border border-slate-300 bg-white p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Check-out</label>
                        <input
                            type="date"
                            name="check_out"
                            class="w-full border border-slate-300 bg-white p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Guests</label>
                        <input
                            type="number"
                            name="guests"
                            min="1"
                            placeholder="Number of guests"
                            class="w-full border border-slate-300 bg-white p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="w-full bg-primary text-white rounded-xl py-3.5 font-semibold hover:bg-blue-700 transition shadow-lg"
                        >
                            Search Rooms
                        </button>
                    </div>
                </form>

                <div class="grid grid-cols-3 gap-3 mt-6 text-center text-sm">
                    <div class="rounded-2xl bg-slate-100 py-3">
                        <p class="font-bold text-slate-900">24/7</p>
                        <p class="text-slate-500">Support</p>
                    </div>
                    <div class="rounded-2xl bg-slate-100 py-3">
                        <p class="font-bold text-slate-900">Safe</p>
                        <p class="text-slate-500">Booking</p>
                    </div>
                    <div class="rounded-2xl bg-slate-100 py-3">
                        <p class="font-bold text-slate-900">Fast</p>
                        <p class="text-slate-500">Check-in</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- STATS -->
<section class="relative -mt-10 z-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                <h3 class="text-3xl font-extrabold text-primary"><?= count($featuredRooms) ?>+</h3>
                <p class="text-slate-500 mt-1">Featured Rooms</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                <h3 class="text-3xl font-extrabold text-primary">24/7</h3>
                <p class="text-slate-500 mt-1">Customer Care</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                <h3 class="text-3xl font-extrabold text-primary">Fast</h3>
                <p class="text-slate-500 mt-1">Reservations</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                <h3 class="text-3xl font-extrabold text-primary">Safe</h3>
                <p class="text-slate-500 mt-1">Payments</p>
            </div>
        </div>
    </div>
</section>

<!-- ROOMS -->
<section id="rooms" class="py-24 px-6 lg:px-10">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-14">
            <span class="text-primary font-semibold uppercase tracking-widest text-sm">Featured Rooms</span>
            <h2 class="text-4xl font-extrabold text-slate-900 mt-3">Our Finest Accommodation</h2>
            <p class="text-slate-500 mt-3 max-w-2xl mx-auto">
                Explore real room listings directly from the database with live pricing and room details.
            </p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-8 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($featuredRooms)): ?>
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-8">
                <?php foreach ($featuredRooms as $room): ?>
                    <div class="bg-white rounded-3xl shadow-lg overflow-hidden hover:-translate-y-1 hover:shadow-2xl transition duration-300">

                        <div class="w-full h-60 bg-slate-200 overflow-hidden">
                            <?php if (!empty($room['primary_image'])): ?>
                                <img
                                    src="<?= htmlspecialchars($room['primary_image']) ?>"
                                    alt="Room Image"
                                    class="w-full h-full object-cover"
                                    onerror="this.src='https://via.placeholder.com/800x500?text=No+Image';"
                                >
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-500 text-lg font-semibold">
                                    No Image
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3 gap-3">
                                <h3 class="font-bold text-xl text-slate-900">
                                    Room <?= htmlspecialchars($room['room_number']) ?>
                                </h3>
                                <span class="bg-blue-50 text-primary text-sm font-semibold px-3 py-1 rounded-full">
                                    <?= htmlspecialchars($room['capacity']) ?> Guest(s)
                                </span>
                            </div>

                            <p class="text-slate-500 mb-2 font-medium">
                                <?= htmlspecialchars($room['type_name'] ?? 'N/A') ?>
                            </p>

                            <p class="text-slate-500 mb-4 min-h-[48px]">
                                <?= htmlspecialchars($room['description'] ?: 'Comfortable and elegant accommodation prepared for your stay.') ?>
                            </p>

                            <div class="flex items-center justify-between gap-3">
                                <p class="text-primary font-extrabold text-xl">
                                    ₱<?= number_format((float)$room['price_per_night'], 2) ?>
                                    <span class="text-sm text-slate-500 font-medium">/night</span>
                                </p>

                                <div class="flex gap-2">
                                    <a
                                        href="<?= url('room?id=' . $room['room_id']) ?>"
                                        class="bg-slate-200 text-slate-800 px-4 py-2 rounded-xl hover:bg-slate-300 transition"
                                    >
                                        Details
                                    </a>

                                    <a
                                        href="<?= url('search-rooms') ?>"
                                        class="bg-primary text-white px-4 py-2 rounded-xl hover:bg-blue-700 transition"
                                    >
                                        Reserve
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-6 py-5 rounded-2xl text-center">
                No featured rooms are available right now.
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- FEATURES -->
<section id="features" class="bg-slate-100 py-24 px-6 lg:px-10">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-14">
            <span class="text-primary font-semibold uppercase tracking-widest text-sm">Why Choose Us</span>
            <h2 class="text-4xl font-extrabold text-slate-900 mt-3">Everything You Need For A Great Stay</h2>
        </div>

        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-3xl shadow-md hover:shadow-xl transition">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-primary flex items-center justify-center text-2xl mb-5">⚡</div>
                <h3 class="font-bold text-xl mb-3">Fast Booking</h3>
                <p class="text-slate-500">Book rooms quickly with a streamlined reservation process and real-time availability.</p>
            </div>

            <div class="bg-white p-8 rounded-3xl shadow-md hover:shadow-xl transition">
                <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center text-2xl mb-5">🔒</div>
                <h3 class="font-bold text-xl mb-3">Secure Payments</h3>
                <p class="text-slate-500">Enjoy safer payment handling with trusted methods and organized reservation records.</p>
            </div>

            <div class="bg-white p-8 rounded-3xl shadow-md hover:shadow-xl transition">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl mb-5">🕒</div>
                <h3 class="font-bold text-xl mb-3">24/7 Support</h3>
                <p class="text-slate-500">Our team is always ready to assist with booking concerns, requests, and stay-related questions.</p>
            </div>
        </div>
    </div>
</section>

<!-- ABOUT -->
<section id="about" class="py-24 px-6 lg:px-10 bg-white">
    <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
        <div>
            <img
                src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267"
                class="rounded-3xl shadow-2xl w-full h-[420px] object-cover"
                alt="Hotel Interior"
            >
        </div>
        <div>
            <span class="text-primary font-semibold uppercase tracking-widest text-sm">About Our Hotel</span>
            <h2 class="text-4xl font-extrabold text-slate-900 mt-3 mb-5">Hospitality Designed Around Comfort</h2>
            <p class="text-slate-600 leading-relaxed mb-5">
                Our hotel reservation system is built to deliver convenience, reliability, and a premium guest experience from the very first click.
            </p>
            <p class="text-slate-600 leading-relaxed mb-8">
                From elegant rooms to responsive service, we combine comfort and technology to make every reservation easy and every stay memorable.
            </p>
            <a
                href="<?= url('search-rooms') ?>"
                class="inline-block bg-primary text-white px-6 py-3 rounded-2xl font-semibold hover:bg-blue-700 transition"
            >
                Start Booking
            </a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-24 px-6 lg:px-10 bg-gradient-to-r from-primary to-blue-700 text-white">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-4xl font-extrabold mb-4">Ready To Reserve Your Next Stay?</h2>
        <p class="text-blue-100 text-lg mb-8">
            Search available rooms now and experience a simpler, smarter hotel booking process.
        </p>
        <a
            href="<?= url('search-rooms') ?>"
            class="inline-block bg-white text-primary px-8 py-4 rounded-2xl font-bold hover:bg-slate-100 transition"
        >
            Book A Room Now
        </a>
    </div>
</section>

<!-- FOOTER -->
<footer class="bg-slate-950 text-slate-300">
    <div class="max-w-7xl mx-auto px-6 lg:px-10 py-14 grid md:grid-cols-3 gap-10">
        <div>
            <h3 class="text-2xl font-extrabold text-white mb-3">HotelSys</h3>
            <p class="text-slate-400 leading-relaxed">
                A professional hotel reservation platform built for comfort, convenience, and modern hospitality.
            </p>
        </div>

        <div>
            <h4 class="text-white font-bold mb-4">Quick Links</h4>
            <ul class="space-y-2">
                <li><a href="<?= url('homepage') ?>" class="hover:text-white transition">Home</a></li>
                <li><a href="#rooms" class="hover:text-white transition">Rooms</a></li>
                <li><a href="#features" class="hover:text-white transition">Features</a></li>
                <li><a href="#about" class="hover:text-white transition">About</a></li>
                <li><a href="<?= url('track-reservation') ?>" class="hover:text-white transition">Track Reservation</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-white font-bold mb-4">Contact</h4>
            <p class="mb-2">Email: support@hotelsys.com</p>
            <p class="mb-2">Phone: +63 900 000 0000</p>
            <p>Open 24/7 for booking assistance</p>
        </div>
    </div>

    <div class="border-t border-slate-800 text-center py-5 text-sm text-slate-500">
        © 2026 HotelSys. All rights reserved.
    </div>
</footer>

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