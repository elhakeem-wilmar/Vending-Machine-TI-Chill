<?php
// admin/print_qr.php - Cetak Lembar Poster / Stiker QR Code untuk Fisik Mesin
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$machineUrl = BASE_URL;
// Menggunakan API QR Code SVG/PNG gratis dan andal (QuickChart / Google Chart format)
$qrCodeApiUrl = "https://quickchart.io/qr?text=" . urlencode($machineUrl) . "&size=400&margin=2";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Mesin - Vending Machine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .print-border { border: 2px solid #0f172a !important; }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-6 flex flex-col items-center justify-center">

    <div class="no-print mb-6 flex space-x-3">
        <button onclick="window.print()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm rounded-xl shadow-lg transition">
            🖨️ Cetak Lembar QR
        </button>
        <a href="index.php" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-sm font-semibold rounded-xl border border-slate-600 transition">
            ← Kembali ke Dashboard
        </a>
    </div>

    <!-- Lembar Fisik Stiker Mesin -->
    <div class="bg-white text-slate-900 rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl print-border border border-slate-200">
        <div class="inline-block bg-blue-50 text-blue-600 p-3 rounded-2xl mb-3 text-3xl">
            🥤
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Vending Machine TI-Chill</h1>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mt-0.5">Drinks Station</p>

        <div class="my-6 p-4 bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl flex justify-center">
            <img src="<?= $qrCodeApiUrl ?>" alt="QR Code Mesin" class="w-64 h-64 object-contain">
        </div>

        <div class="bg-blue-600 text-white rounded-xl py-2 px-3 mb-4">
            <p class="text-xs font-bold uppercase tracking-wider">Scan Menggunakan Kamera HP Anda</p>
        </div>

        <div class="text-left space-y-2 text-xs text-slate-600 border-t border-slate-200 pt-4">
            <div class="flex items-start space-x-2">
                <span class="font-bold text-blue-600">1.</span>
                <span>Scan QR Code untuk membuka menu minuman di HP.</span>
            </div>
            <div class="flex items-start space-x-2">
                <span class="font-bold text-blue-600">2.</span>
                <span>Pilih minuman dingin dan bayar via QRIS / E-Wallet.</span>
            </div>
            <div class="flex items-start space-x-2">
                <span class="font-bold text-blue-600">3.</span>
                <span>Minuman akan otomatis keluar dari mesin!</span>
            </div>
        </div>

        <p class="text-[10px] text-slate-400 mt-6 font-mono truncate"><?= htmlspecialchars($machineUrl) ?></p>
    </div>

</body>
</html>
