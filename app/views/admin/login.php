<?php
$title = "Admin Login";
$error_msg = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/partials/audit_helper.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . url('admin'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error_msg = "Please enter both username and password.";
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
                "root",
                ""
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("
                SELECT admin_id, username, password, email, role
                FROM admins
                WHERE username = :username
                LIMIT 1
            ");
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];

                write_audit_log($admin['admin_id'], "Logged in to admin panel");

                header('Location: ' . url('admin'));
                exit;
            } else {
                $error_msg = "Invalid admin credentials.";
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
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-slate-900">Admin Login</h1>
            <p class="text-slate-500 mt-2">Access the hotel management panel.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                <input type="text" name="username"
                       class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                <input type="password" name="password"
                       class="w-full border border-slate-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
            </div>

            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition">
                Login
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="<?= url('/') ?>" class="text-slate-600 hover:text-blue-600">Back to Home</a>
        </div>
    </div>

</body>
</html>