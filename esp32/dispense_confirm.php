<?php
// api/dispense_confirm.php - Konfirmasi dari ESP32 bahwa servo telah selesai berputar mengeluarkan minuman
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$receivedKey = $_SERVER['HTTP_X_MACHINE_KEY'] ?? $_POST['api_key'] ?? $_GET['api_key'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    if (empty($receivedKey) && isset($input['api_key'])) {
        $receivedKey = $input['api_key'];
    }
    $orderId = $input['order_id'] ?? '';
} else {
    $orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
}

if ($receivedKey !== MACHINE_API_KEY) {
    jsonResponse(['error' => 'Akses ditolak: Machine API Key salah'], 401);
}

if (empty($orderId)) {
    jsonResponse(['error' => 'Parameter order_id wajib diisi'], 400);
}

$stmt = $pdo->prepare("UPDATE orders SET dispense_status = 'COMPLETED', dispensed_at = NOW() WHERE order_id = ?");
$stmt->execute([$orderId]);

if ($stmt->rowCount() > 0) {
    jsonResponse([
        'status' => 'success',
        'message' => 'Status dispense berhasil diperbarui menjadi COMPLETED',
        'order_id' => $orderId
    ]);
} else {
    jsonResponse([
        'status' => 'warning',
        'message' => 'Order ID tidak ditemukan atau status sudah selesai sebelumnya',
        'order_id' => $orderId
    ]);
}

