<?php
$title = "Customer Sign In";
$error_msg = '';
$full_name = '';
$email = '';

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($full_name === '' || $email === '' || $password === '') {
            $error_msg = "Full name, email, and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please enter a valid email address.";
        } else {
            $name_parts = preg_split('/\s+/', $full_name, -1, PREG_SPLIT_NO_EMPTY);
            $first_name = $name_parts[0] ?? '';
            $last_name = count($name_parts) > 1 ? implode(' ', array_slice($name_parts, 1)) : '';

            if ($first_name === '') {
                $error_msg = "Please enter your full name.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($customer) {
                    if (!empty($customer['password'])) {
                        if (password_verify($password, $customer['password'])) {
                            $_SESSION['customer_id'] = $customer['customer_id'];
                            $_SESSION['customer_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                            $_SESSION['customer_email'] = $customer['email'];
                            $_SESSION['customer_profile_picture'] = $customer['profile_picture'] ?? null;

                            header("Location: " . url('homepage'));
                            exit;
                        } else {
                            $error_msg = "Incorrect email or password.";
                        }
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        $updateStmt = $pdo->prepare("
                            UPDATE customers
                            SET first_name = :first_name,
                                last_name = :last_name,
                                password = :password,
                                is_verified = 1,
                                updated_at = NOW()
                            WHERE customer_id = :customer_id
                        ");

                        $updateStmt->execute([
                            ':first_name' => $first_name,
                            ':last_name' => $last_name,
                            ':password' => $hashed_password,
                            ':customer_id' => $customer['customer_id'],
                        ]);

                        $_SESSION['customer_id'] = $customer['customer_id'];
                        $_SESSION['customer_name'] = trim($first_name . ' ' . $last_name);
                        $_SESSION['customer_email'] = $email;
                        $_SESSION['customer_profile_picture'] = $customer['profile_picture'] ?? null;

                        header("Location: " . url('homepage'));
                        exit;
                    }
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $insertStmt = $pdo->prepare("
                        INSERT INTO customers (
                            first_name,
                            last_name,
                            email,
                            password,
                            is_verified,
                            created_at,
                            updated_at
                        ) VALUES (
                            :first_name,
                            :last_name,
                            :email,
                            :password,
                            1,
                            NOW(),
                            NOW()
                        )
                    ");

                    $insertStmt->execute([
                        ':first_name' => $first_name,
                        ':last_name' => $last_name,
                        ':email' => $email,
                        ':password' => $hashed_password,
                    ]);

                    $customer_id = $pdo->lastInsertId();

                    $_SESSION['customer_id'] = $customer_id;
                    $_SESSION['customer_name'] = trim($first_name . ' ' . $last_name);
                    $_SESSION['customer_email'] = $email;
                    $_SESSION['customer_profile_picture'] = null;

                    header("Location: " . url('homepage'));
                    exit;
                }
            }
        }
    }
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
                    dark: "#0f172a"
                }
            }
        }
    }
    </script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900">Customer Sign In</h1>
            <p class="text-slate-500 mt-2">Continue to your HotelSys account</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-5 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('customer-signin') ?>" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                <input
                    type="text"
                    name="full_name"
                    value="<?= htmlspecialchars($full_name) ?>"
                    placeholder="Enter your full name"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="Enter your email"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                >
            </div>

            <button
                type="submit"
                class="w-full bg-primary hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition"
            >
                Continue
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="<?= url('homepage') ?>" class="text-primary font-medium hover:underline">
                Back to Homepage
            </a>
        </div>
    </div>

</body>
</html>