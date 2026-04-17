<?php
$reservation_id = (int) ($_GET['reservation_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Success</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-3xl shadow-xl max-w-lg w-full text-center">
        <div class="text-5xl mb-4">🎉</div>
        <h1 class="text-3xl font-bold text-green-600 mb-4">Booking Successful</h1>
        <p class="text-slate-600 mb-4">Your reservation has been successfully created.</p>
        <p class="text-lg font-semibold text-slate-800 mb-8">
            Reservation ID: <?= htmlspecialchars($reservation_id) ?>
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= url('/') ?>" class="bg-blue-600 text-white px-5 py-3 rounded-xl hover:bg-blue-700 transition">
                Back to Home
            </a>
            <a href="<?= url('search-rooms') ?>" class="bg-slate-200 text-slate-800 px-5 py-3 rounded-xl hover:bg-slate-300 transition">
                Book Another Room
            </a>
        </div>
    </div>
</body>
</html>