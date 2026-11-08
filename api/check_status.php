<?php
// api/check_status.php - Cek status pembayaran dan pengeluaran produk oleh pembeli
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$orderId = $_GET['order_id'] ?? '';
if (empty($orderId)) {
    jsonResponse(['error' => 'Parameter order_id wajib diisi'], 400);
}

$stmt = $pdo->prepare("
    SELECT o.id, o.order_id, o.product_id, o.payment_status, o.dispense_status, o.slot_number, p.name AS product_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.order_id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(['error' => 'Order tidak ditemukan'], 404);
}

// Jika status di database masih PENDING, cek langsung status terkini ke server Midtrans API (Failsafe)
if ($order['payment_status'] === 'PENDING') {
    $midtransStatus = checkMidtransTransactionStatus($orderId);

    if ($midtransStatus && isset($midtransStatus['transaction_status'])) {
        $trxStatus = $midtransStatus['transaction_status'];
        $fraudStatus = $midtransStatus['fraud_status'] ?? 'accept';

        $isPaid = false;
        if ($trxStatus === 'settlement') {
            $isPaid = true;
        } elseif ($trxStatus === 'capture' && $fraudStatus === 'accept') {
            $isPaid = true;
        }

        if ($isPaid) {
            // Update database ke PAID dan siap dikeluarkan oleh ESP32
            $update = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'PAID', 
                    dispense_status = 'READY_TO_DISPENSE', 
                    paid_at = NOW() 
                WHERE id = ?
            ");
            $update->execute([$order['id']]);

            // Potong stok produk
            $stock = $pdo->prepare("UPDATE products SET stock = GREATEST(stock - 1, 0) WHERE id = ?");
            $stock->execute([$order['product_id']]);

            $order['payment_status'] = 'PAID';
            $order['dispense_status'] = 'READY_TO_DISPENSE';
        } elseif (in_array($trxStatus, ['expire', 'cancel', 'deny'], true)) {
            $newPay = ($trxStatus === 'expire') ? 'EXPIRED' : 'FAILED';
            $update = $pdo->prepare("UPDATE orders SET payment_status = ?, dispense_status = 'FAILED' WHERE id = ?");
            $update->execute([$newPay, $order['id']]);
            $order['payment_status'] = $newPay;
            $order['dispense_status'] = 'FAILED';
        }
    }
}

if (isset($_GET['cancel']) && $_GET['cancel'] === '1' && $order['payment_status'] === 'PENDING') {
    $update = $pdo->prepare("UPDATE orders SET payment_status = 'FAILED', dispense_status = 'FAILED' WHERE id = ?");
    $update->execute([$order['id']]);
    $order['payment_status'] = 'FAILED';
    $order['dispense_status'] = 'FAILED';
}

jsonResponse([
    'success' => true,
    'order_id' => $order['order_id'],
    'product_name' => $order['product_name'],
    'slot_number' => (int)$order['slot_number'],
    'payment_status' => $order['payment_status'],
    'dispense_status' => $order['dispense_status']
]);
