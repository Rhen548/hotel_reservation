<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php include __DIR__ . '/partials/audit_helper.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/room-images'));
    exit;
}

$image_id = (int) ($_POST['image_id'] ?? 0);

if ($image_id <= 0) {
    header('Location: ' . url('admin/room-images?error=Invalid+image+ID'));
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ GET IMAGE INFO FIRST (IMPORTANT)
    $getStmt = $pdo->prepare("
        SELECT ri.image_id, ri.image_path, ri.room_id,
               r.room_number, r.room_type
        FROM room_images ri
        LEFT JOIN rooms r ON ri.room_id = r.room_id
        WHERE ri.image_id = :image_id
        LIMIT 1
    ");
    $getStmt->execute([':image_id' => $image_id]);
    $image = $getStmt->fetch(PDO::FETCH_ASSOC);

    if (!$image) {
        header('Location: ' . url('admin/room-images?error=Image+not+found'));
        exit;
    }

    // ✅ DELETE IMAGE
    $stmt = $pdo->prepare("
        DELETE FROM room_images
        WHERE image_id = :image_id
    ");
    $stmt->execute([':image_id' => $image_id]);

    // ✅ AUDIT LOG
    $admin_id = $_SESSION['admin_id'] ?? null;

    $room_label = $image['room_number']
        ? $image['room_number'] . " (" . $image['room_type'] . ")"
        : "Room #" . $image['room_id'];

    write_audit_log(
        $admin_id,
        "Deleted room image #{$image_id} from {$room_label}: {$image['image_path']}"
    );

    header('Location: ' . url('admin/room-images?success=1'));
    exit;

} catch (PDOException $e) {
    header('Location: ' . url('admin/room-images?error=' . urlencode($e->getMessage())));
    exit;
}