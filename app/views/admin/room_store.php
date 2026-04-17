<?php include __DIR__ . '/partials/auth_guard.php'; ?>
<?php include __DIR__ . '/partials/audit_helper.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/rooms'));
    exit;
}

$room_number = trim($_POST['room_number'] ?? '');
$type_id = (int) ($_POST['type_id'] ?? 0);
$price_per_night = (float) ($_POST['price_per_night'] ?? 0);
$capacity = (int) ($_POST['capacity'] ?? 0);
$status = trim($_POST['status'] ?? '');
$description = trim($_POST['description'] ?? '');

$allowedStatuses = ['available', 'occupied', 'maintenance', 'cleaning'];

// BASIC VALIDATION
if ($room_number === '' || $type_id <= 0 || $price_per_night < 0 || $capacity <= 0) {
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

    // CHECK DUPLICATE ROOM NUMBER
    $checkStmt = $pdo->prepare("
        SELECT room_id
        FROM rooms
        WHERE room_number = :room_number
        LIMIT 1
    ");
    $checkStmt->execute([':room_number' => $room_number]);

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

    // INSERT ROOM
    $stmt = $pdo->prepare("
        INSERT INTO rooms (
            room_number,
            type_id,
            price_per_night,
            capacity,
            status,
            description
        ) VALUES (
            :room_number,
            :type_id,
            :price_per_night,
            :capacity,
            :status,
            :description
        )
    ");

    $stmt->execute([
        ':room_number' => $room_number,
        ':type_id' => $type_id,
        ':price_per_night' => $price_per_night,
        ':capacity' => $capacity,
        ':status' => $status,
        ':description' => $description,
    ]);

    // GET LAST INSERTED ROOM ID
    $new_room_id = $pdo->lastInsertId();

    // AUDIT LOG
    $admin_id = $_SESSION['admin_id'] ?? null;

    write_audit_log(
        $admin_id,
        "Added room #{$new_room_id}: Room {$room_number}, Type {$roomType['type_name']}, Price ₱"
        . number_format($price_per_night, 2)
        . ", Capacity {$capacity}, Status {$status}"
    );

    header('Location: ' . url('admin/rooms?success=1'));
    exit;

} catch (PDOException $e) {
    header('Location: ' . url('admin/rooms?error=' . urlencode($e->getMessage())));
    exit;
}