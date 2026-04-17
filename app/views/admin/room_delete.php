<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php include __DIR__ . '/partials/audit_helper.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/rooms'));
    exit;
}

$room_id = (int) ($_POST['room_id'] ?? 0);

if ($room_id <= 0) {
    header('Location: ' . url('admin/rooms?error=Invalid+room+ID'));
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ GET ROOM INFO FIRST (IMPORTANT FOR AUDIT)
    $getStmt = $pdo->prepare("
        SELECT room_number, room_type
        FROM rooms
        WHERE room_id = :room_id
        LIMIT 1
    ");
    $getStmt->execute([':room_id' => $room_id]);
    $room = $getStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        header('Location: ' . url('admin/rooms?error=Room+not+found'));
        exit;
    }

    // ✅ DELETE ROOM
    $stmt = $pdo->prepare("
        DELETE FROM rooms
        WHERE room_id = :room_id
    ");
    $stmt->execute([':room_id' => $room_id]);

    // ✅ AUDIT LOG (TAMANG LUGAR)
    $admin_id = $_SESSION['admin_id'] ?? null;

    $room_label = $room['room_number'] . " (" . $room['room_type'] . ")";

    write_audit_log(
        $admin_id,
        "Deleted room #{$room_id}: {$room_label}"
    );

    // REDIRECT
    header('Location: ' . url('admin/rooms?success=1'));
    exit;

} catch (PDOException $e) {
    header('Location: ' . url('admin/rooms?error=' . urlencode($e->getMessage())));
    exit;
}