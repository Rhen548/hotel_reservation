<?php

if (!isset($_SESSION['customer_id'])) {
    header("Location: " . url('customer-signin'));
    exit;
}

$title = "My Profile";
$error_msg = '';
$success_msg = '';
$customer = null;

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = :customer_id LIMIT 1");
    $stmt->execute([':customer_id' => $_SESSION['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $phone = trim($_POST['phone'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        $profile_picture_path = $customer['profile_picture'] ?? null;

        if (!empty($_FILES['profile_picture']['name'])) {
            $upload_dir = dirname(__DIR__, 2) . '/public/uploads/profile_pictures/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (!is_writable($upload_dir)) {
                $error_msg = "Upload folder is not writable.";
            } else {
                $file_tmp = $_FILES['profile_picture']['tmp_name'];
                $original_name = $_FILES['profile_picture']['name'];
                $file_size = $_FILES['profile_picture']['size'];
                $file_error = $_FILES['profile_picture']['error'];

                $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file_tmp);
                finfo_close($finfo);

                $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

                if ($file_error !== UPLOAD_ERR_OK) {
                    $error_msg = "There was an error uploading the file.";
                } elseif ($file_size > 5 * 1024 * 1024) {
                    $error_msg = "Profile picture must not exceed 5MB.";
                } elseif (!in_array($extension, $allowed_extensions, true)) {
                    $error_msg = "Only JPG, JPEG, PNG, and WEBP files are allowed.";
                } elseif (!in_array($mime_type, $allowed_mimes, true)) {
                    $error_msg = "Invalid image file.";
                } else {
                    $safe_name = preg_replace('/[^A-Za-z0-9\-_\.]/', '', $original_name);
                    $new_file_name = 'customer_' . $_SESSION['customer_id'] . '_' . time() . '_' . $safe_name;
                    $target_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp, $target_path)) {
                        $profile_picture_path = 'public/uploads/profile_pictures/' . $new_file_name;
                    } else {
                        $error_msg = "Failed to upload profile picture.";
                    }
                }
            }
        }

        if ($error_msg === '') {
            if ($new_password !== '' || $confirm_password !== '') {
                if ($new_password === '' || $confirm_password === '') {
                    $error_msg = "Please fill in both password fields.";
                } elseif (strlen($new_password) < 6) {
                    $error_msg = "Password must be at least 6 characters.";
                } elseif ($new_password !== $confirm_password) {
                    $error_msg = "New password and confirm password do not match.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    $updateStmt = $pdo->prepare("
                        UPDATE customers
                        SET phone = :phone,
                            profile_picture = :profile_picture,
                            password = :password,
                            updated_at = NOW()
                        WHERE customer_id = :customer_id
                    ");

                    $updateStmt->execute([
                        ':phone' => $phone,
                        ':profile_picture' => $profile_picture_path,
                        ':password' => $hashed_password,
                        ':customer_id' => $_SESSION['customer_id'],
                    ]);
                }
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE customers
                    SET phone = :phone,
                        profile_picture = :profile_picture,
                        updated_at = NOW()
                    WHERE customer_id = :customer_id
                ");

                $updateStmt->execute([
                    ':phone' => $phone,
                    ':profile_picture' => $profile_picture_path,
                    ':customer_id' => $_SESSION['customer_id'],
                ]);
            }

            if ($error_msg === '') {
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = :customer_id LIMIT 1");
                $stmt->execute([':customer_id' => $_SESSION['customer_id']]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['customer_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                $_SESSION['customer_email'] = $customer['email'] ?? '';
                $_SESSION['customer_profile_picture'] = $customer['profile_picture'] ?? null;

                $success_msg = "Profile updated successfully.";
            }
        }
    }
} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
}

$display_name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$display_initial = !empty($customer['first_name'])
    ? strtoupper(substr($customer['first_name'], 0, 1))
    : 'U';
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
            primary: "#2563eb"
          }
        }
      }
    }
    </script>
</head>
<body class="bg-slate-100 min-h-screen">

    <div class="max-w-4xl mx-auto px-6 py-10">
        <div class="bg-white rounded-3xl shadow-lg p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900">My Profile</h1>
                    <p class="text-slate-500 mt-1">Manage your account details and profile settings.</p>
                </div>

                <a href="<?= url('homepage') ?>" class="text-blue-600 hover:underline font-medium">
                    Back to Homepage
                </a>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="mb-4 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="mb-4 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <div class="w-full md:w-52">
                        <div class="flex flex-col items-start">
                            <?php if (!empty($customer['profile_picture'])): ?>
                                <img
                                    src="<?= htmlspecialchars(url($customer['profile_picture'])) ?>"
                                    alt="Profile Picture"
                                    class="w-36 h-36 rounded-full object-cover border-4 border-slate-200 shadow-sm"
                                >
                            <?php else: ?>
                                <div class="w-36 h-36 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-3xl border-4 border-slate-200">
                                    <?= htmlspecialchars($display_initial) ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-4 w-full">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Profile Picture</label>
                                <input
                                    type="file"
                                    name="profile_picture"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100"
                                >
                                <p class="text-xs text-slate-400 mt-2">Allowed: JPG, JPEG, PNG, WEBP. Max 5MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                            <input
                                type="text"
                                value="<?= htmlspecialchars($display_name) ?>"
                                class="w-full border border-slate-300 rounded-xl px-4 py-3 bg-slate-50"
                                readonly
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="text"
                                    value="<?= htmlspecialchars($customer['email'] ?? '') ?>"
                                    class="w-full border border-slate-300 rounded-xl px-4 py-3 bg-slate-50"
                                    readonly
                                >
                                <span class="inline-flex items-center px-3 py-2 rounded-full text-xs font-bold bg-green-100 text-green-700 whitespace-nowrap">
                                    Verified
                                </span>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Phone Number</label>
                            <input
                                type="text"
                                name="phone"
                                value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                                placeholder="Enter phone number"
                                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>
                </div>

                <div class="border-t pt-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Change Password</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">New Password</label>
                            <input
                                type="password"
                                name="new_password"
                                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter new password"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                            <input
                                type="password"
                                name="confirm_password"
                                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Confirm new password"
                            >
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-xl transition"
                    >
                        Save Changes
                    </button>

                    <a
                        href="<?= url('homepage') ?>"
                        class="inline-flex items-center justify-center border border-slate-300 text-slate-700 font-semibold px-8 py-3 rounded-xl hover:bg-slate-50 transition"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>