<?php
// api/midtrans_webhook.php - Menerima notifikasi status pembayaran dari Midtrans
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    jsonResponse(['error' => 'Data payload kosong'], 400);
}

$orderId = $data['order_id'] ?? '';
$statusCode = $data['status_code'] ?? '';
$grossAmount = $data['gross_amount'] ?? '';
$signatureKey = $data['signature_key'] ?? '';
$transactionStatus = $data['transaction_status'] ?? '';
$fraudStatus = $data['fraud_status'] ?? '';

// Verifikasi Signature Key Midtrans: SHA512(order_id + status_code + gross_amount + ServerKey)
$expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . MIDTRANS_SERVER_KEY);

if ($signatureKey !== $expectedSignature) {
    jsonResponse(['error' => 'Signature key tidak valid'], 403);
}

// Cek order di database
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(['error' => 'Order tidak ditemukan'], 404);
}

// Tentukan status pembayaran
$newPaymentStatus = $order['payment_status'];
$newDispenseStatus = $order['dispense_status'];

if ($transactionStatus == 'capture') {
    if ($fraudStatus == 'accept') {
        $newPaymentStatus = 'PAID';
        $newDispenseStatus = 'READY_TO_DISPENSE';
    }
} elseif ($transactionStatus == 'settlement') {
    $newPaymentStatus = 'PAID';
    $newDispenseStatus = 'READY_TO_DISPENSE';
} elseif ($transactionStatus == 'cancel' || $transactionStatus == 'expire') {
    $newPaymentStatus = 'EXPIRED';
    $newDispenseStatus = 'FAILED';
} elseif ($transactionStatus == 'deny') {
    $newPaymentStatus = 'FAILED';
    $newDispenseStatus = 'FAILED';
}

// Jika pembayaran baru saja sukses dan sebelumnya belum PAID, kurangi stok produk
if ($newPaymentStatus === 'PAID' && $order['payment_status'] !== 'PAID') {
    $updateStmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = 'PAID', 
            dispense_status = 'READY_TO_DISPENSE', 
            paid_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$order['id']]);

    // Kurangi stok di produk
    $stockStmt = $pdo->prepare("UPDATE products SET stock = GREATEST(stock - 1, 0) WHERE id = ?");
    $stockStmt->execute([$order['product_id']]);
} else {
    // Update status non-PAID jika diperlukan
    $updateStmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = ?, 
            dispense_status = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([$newPaymentStatus, $newDispenseStatus, $order['id']]);
}

jsonResponse(['status' => 'OK', 'message' => 'Notifikasi berhasil diproses']);

