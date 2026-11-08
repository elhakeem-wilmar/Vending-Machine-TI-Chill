<?php
// config.php - Konfigurasi Utama Sistem Vending Machine

// 1. Pengaturan Database MySQL (Sesuaikan dengan data cPanel AnymHost Anda)
define('DB_HOST', 'localhost');
define('DB_NAME', 'bsgxywct_vending');
define('DB_USER', 'bsgxywct_ti-chill');
define('DB_PASS', '0dV0lC2.H]D8ho');

// 2. Pengaturan Midtrans Sandbox
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-V0po8.uebCIpuXYRLt~PzaQO');
define('MIDTRANS_CLIENT_KEY', 'Mid-client-Z7gAlMf4Xx1JbaCi');
define('MIDTRANS_IS_PRODUCTION', false); // true jika sudah live

// 3. API Key Rahasia untuk ESP32 (Harus sama persis dengan yang ada di sketch Arduino)
define('MACHINE_API_KEY', 'VM_SECRET_TOKEN_2026_XYZ');

// 4. URL Base Website Anda (contoh: https://vending.domainanda.com)
define('BASE_URL', 'http://ti-chill.web.id/vending');

// Inisialisasi Koneksi Database PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]));
}

// Helper: Response JSON
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Helper: Request Snap Token Midtrans via cURL (Tanpa butuh Composer SDK)
function requestMidtransSnapToken($orderId, $grossAmount, $itemDetails = []) {
    $apiUrl = MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    $payload = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => (int) $grossAmount,
        ],
        'item_details' => $itemDetails,
        'enabled_payments' => [
            'gopay',
            'shopeepay',
            'other_qris',
            'dana',
            'bca_va',
            'bni_va',
            'bri_va',
            'echannel',
            'permata_va',
            'other_va',
        ],
        'callbacks' => [
            'finish' => rtrim(BASE_URL, '/') . '/index.php?order_id=' . urlencode($orderId),
        ],
    ];

    $authHeader = base64_encode(MIDTRANS_SERVER_KEY . ':');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . $authHeader,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'CURL Error: ' . $curlError];
    }

    $result = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($result['token'])) {
        return [
            'token' => $result['token'],
            'redirect_url' => $result['redirect_url'] ?? ''
        ];
    }

    $errorMessage = $result['error_messages'][0] 
        ?? $result['status_message'] 
        ?? $result['message'] 
        ?? (!empty($response) ? $response : 'Gagal membuat transaksi Midtrans (HTTP ' . $httpCode . ')');

    return ['error' => $errorMessage];
}

// Helper: Cek status transaksi langsung ke Midtrans API (Failsafe otomatis jika webhook terlambat/terblokir)
function checkMidtransTransactionStatus($orderId) {
    $apiUrl = MIDTRANS_IS_PRODUCTION
        ? "https://api.midtrans.com/v2/{$orderId}/status"
        : "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

    $authHeader = base64_encode(MIDTRANS_SERVER_KEY . ':');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Basic ' . $authHeader,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
