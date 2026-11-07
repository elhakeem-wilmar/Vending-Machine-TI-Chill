<?php
// api/dispense_queue.php - Polling oleh ESP32 untuk mengambil antrean minuman yang harus dikeluarkan
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Autentikasi ESP32 via Header atau Query Param
$receivedKey = $_SERVER['HTTP_X_MACHINE_KEY'] ?? $_GET['api_key'] ?? '';

if ($receivedKey !== MACHINE_API_KEY) {
    jsonResponse(['error' => 'Akses ditolak: Machine API Key salah'], 401);
}

// Mulai transaksi database untuk mencegah race condition
$pdo->beginTransaction();

try {
    // Ambil order terlama yang sudah dibayar dan siap dikeluarkan
    $stmt = $pdo->prepare("
        SELECT id, order_id, slot_number 
        FROM orders 
        WHERE payment_status = 'PAID' AND dispense_status = 'READY_TO_DISPENSE'
        ORDER BY id ASC 
        LIMIT 1 
        FOR UPDATE
    ");
    $stmt->execute();
    $job = $stmt->fetch();

    if ($job) {
        // Tandai status sedang diproses pengeluaran
        $updateStmt = $pdo->prepare("UPDATE orders SET dispense_status = 'DISPENSING' WHERE id = ?");
        $updateStmt->execute([$job['id']]);
        $pdo->commit();

        jsonResponse([
            'status' => 'success',
            'has_job' => true,
            'order_id' => $job['order_id'],
            'slot_number' => (int)$job['slot_number']
        ]);
    } else {
        $pdo->commit();
        jsonResponse([
            'status' => 'success',
            'has_job' => false
        ]);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}

