<?php
function write_audit_log($admin_id, $action) {
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=hotel_reservation;charset=utf8mb4",
            "root",
            ""
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (admin_id, action)
            VALUES (:admin_id, :action)
        ");

        $stmt->execute([
            ':admin_id' => $admin_id ?: null,
            ':action' => $action
        ]);
    } catch (PDOException $e) {
        // Silent fail para hindi masira ang main system flow
    }
}