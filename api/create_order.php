<?php
// api/create_order.php - Buat order dan dapatkan Snap Token Midtrans
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Metode request harus POST'], 405);
}

// Baca input (baik x-www-form-urlencoded maupun raw json)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
if ($productId <= 0) {
    jsonResponse(['error' => 'Product ID tidak valid'], 400);
}

// Cek produk dan stok
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    jsonResponse(['error' => 'Produk minuman tidak ditemukan'], 404);
}

if ($product['stock'] <= 0) {
    jsonResponse(['error' => 'Maaf, stok minuman ini sedang habis'], 400);
}

// Generate unique order ID
$orderId = 'VM-' . date('YmdHis') . '-' . rand(100, 999);
$grossAmount = (int) $product['price'];

$itemDetails = [
    [
        'id' => (string) $product['slot_number'],
        'price' => $grossAmount,
        'quantity' => 1,
        'name' => mb_substr($product['name'], 0, 50),
    ]
];

// Request token ke Midtrans
$snapData = requestMidtransSnapToken($orderId, $grossAmount, $itemDetails);

if (isset($snapData['error'])) {
    jsonResponse(['error' => $snapData['error']], 500);
}

$snapToken = $snapData['token'];

// Simpan ke tabel orders
$insertStmt = $pdo->prepare("
    INSERT INTO orders (order_id, product_id, slot_number, amount, payment_status, dispense_status, snap_token)
    VALUES (?, ?, ?, ?, 'PENDING', 'WAITING_PAYMENT', ?)
");
$insertStmt->execute([
    $orderId,
    $product['id'],
    $product['slot_number'],
    $grossAmount,
    $snapToken
]);

jsonResponse([
    'success' => true,
    'order_id' => $orderId,
    'snap_token' => $snapToken,
    'slot_number' => $product['slot_number'],
    'product_name' => $product['name'],
    'price' => $product['price']
]);

