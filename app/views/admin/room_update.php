<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php include __DIR__ . '/partials/audit_helper.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/rooms'));
    exit;
}

$room_id = (int) ($_POST['room_id'] ?? 0);
$room_number = trim($_POST['room_number'] ?? '');
$type_id = (int) ($_POST['type_id'] ?? 0);
$price_per_night = (float) ($_POST['price_per_night'] ?? 0);
$capacity = (int) ($_POST['capacity'] ?? 0);
$status = trim($_POST['status'] ?? '');
$description = trim($_POST['description'] ?? '');

$allowedStatuses = ['available', 'occupied', 'maintenance', 'cleaning'];

// VALIDATION
if ($room_id <= 0 || $room_number === '' || $type_id <= 0 || $price_per_night < 0 || $capacity <= 0) {
    header('Location: ' . url('admin/rooms?error=Invalid+room+data'));
    exit;
}

if (!in_array($status, $allowedStatuses, true)) {
    header('Location: ' . url('admin/rooms?error=Invalid+room+status'));
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // CHECK IF ROOM EXISTS
    $existingStmt = $pdo->prepare("
        SELECT room_id
        FROM rooms
        WHERE room_id = :room_id
        LIMIT 1
    ");
    $existingStmt->execute([':room_id' => $room_id]);

    if (!$existingStmt->fetch()) {
        header('Location: ' . url('admin/rooms?error=Room+not+found'));
        exit;
    }

    // CHECK DUPLICATE ROOM NUMBER
    $checkStmt = $pdo->prepare("
        SELECT room_id
        FROM rooms
        WHERE room_number = :room_number
          AND room_id != :room_id
        LIMIT 1
    ");
    $checkStmt->execute([
        ':room_number' => $room_number,
        ':room_id' => $room_id,
    ]);

    if ($checkStmt->fetch()) {
        header('Location: ' . url('admin/rooms?error=Room+number+already+exists'));
        exit;
    }

    // CHECK IF ROOM TYPE EXISTS
    $typeStmt = $pdo->prepare("
        SELECT type_id, type_name
        FROM room_types
        WHERE type_id = :type_id
        LIMIT 1
    ");
    $typeStmt->execute([':type_id' => $type_id]);
    $roomType = $typeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$roomType) {
        header('Location: ' . url('admin/rooms?error=Invalid+room+type'));
        exit;
    }

    // UPDATE ROOM
    $stmt = $pdo->prepare("
        UPDATE rooms
        SET room_number = :room_number,
            type_id = :type_id,
            price_per_night = :price_per_night,
            capacity = :capacity,
            status = :status,
            description = :description
        WHERE room_id = :room_id
    ");

    $stmt->execute([
        ':room_number' => $room_number,
        ':type_id' => $type_id,
        ':price_per_night' => $price_per_night,
        ':capacity' => $capacity,
        ':status' => $status,
        ':description' => $description,
        ':room_id' => $room_id,
    ]);

    // AUDIT LOG
    $admin_id = $_SESSION['admin_id'] ?? null;

    write_audit_log(
        $admin_id,
        "Updated room #{$room_id}: Room {$room_number}, Type {$roomType['type_name']}, Price ₱"
        . number_format($price_per_night, 2)
        . ", Capacity {$capacity}, Status {$status}"
    );

    header('Location: ' . url('admin/rooms?success=1'));
    exit;

} catch (PDOException $e) {
    header('Location: ' . url('admin/rooms?error=' . urlencode($e->getMessage())));
    exit;
}