<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php include __DIR__ . '/partials/audit_helper.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/room-images'));
    exit;
}

$room_id = (int) ($_POST['room_id'] ?? 0);
$image_path = trim($_POST['image_path'] ?? '');

if ($room_id <= 0 || $image_path === '') {
    header('Location: ' . url('admin/room-images?error=Invalid+image+data'));
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ Check if room exists and get room info for audit log
    $checkStmt = $pdo->prepare("
        SELECT room_id, room_number, room_type
        FROM rooms
        WHERE room_id = :room_id
        LIMIT 1
    ");
    $checkStmt->execute([':room_id' => $room_id]);
    $room = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        header('Location: ' . url('admin/room-images?error=Room+not+found'));
        exit;
    }

    // ✅ Insert room image
    $stmt = $pdo->prepare("
        INSERT INTO room_images (
            room_id,
            image_path
        ) VALUES (
            :room_id,
            :image_path
        )
    ");

    $stmt->execute([
        ':room_id' => $room_id,
        ':image_path' => $image_path,
    ]);

    // ✅ Get inserted image id
    $image_id = $pdo->lastInsertId();

    // ✅ Audit log
    $admin_id = $_SESSION['admin_id'] ?? null;

    write_audit_log(
        $admin_id,
        "Added room image #{$image_id} for room #{$room_id}: {$room['room_number']} ({$room['room_type']}) - {$image_path}"
    );

    header('Location: ' . url('admin/room-images?success=1'));
    exit;

} catch (PDOException $e) {
    header('Location: ' . url('admin/room-images?error=' . urlencode($e->getMessage())));
    exit;
}