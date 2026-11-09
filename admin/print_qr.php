<?php
// admin/print_qr.php - Cetak Lembar Poster / Stiker QR Code untuk Fisik Mesin
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$machineUrl = BASE_URL;
$qrCodeApiUrl = "https://quickchart.io/qr?text=" . urlencode($machineUrl) . "&size=400&margin=2";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Mesin - Vending Machine TI-Chill</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0a1680; --sky:#93b2f8; --gold:#f1b94c; --butter:#fbedb0; --canvas:#fcfdff; --well:#f2f6ff; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--canvas); color: var(--navy); }
        button, a, [role="button"] { cursor: pointer; }
        :focus-visible { outline: 2px solid var(--navy); outline-offset: 2px; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-border { border: 2px solid #0a1680 !important; }
        }
    </style>
</head>
<body class="min-h-screen p-6 flex flex-col items-center justify-center">

    <div class="no-print mb-6 flex space-x-3">
        <button type="button" onclick="window.print()" class="px-4 py-2 text-white font-bold text-xs rounded-lg transition inline-flex items-center gap-1.5" style="background:var(--navy);border-bottom:2px solid #050b4c">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
            Cetak Lembar QR
        </button>
        <a href="index.php" class="px-4 py-2 text-xs font-semibold rounded-lg transition" style="border:1px solid #c7d7fe;color:var(--navy);background:var(--well)">
            &larr; Kembali ke Dashboard
        </a>
    </div>

    <!-- Lembar Fisik Stiker Mesin -->
    <main class="bg-white rounded-2xl p-8 max-w-sm w-full text-center print-border" style="border:1px solid #dbe4f8;box-shadow:0 4px 14px rgba(10,22,128,.05)">
        <img src="../assets/logo.png" alt="Logo Kelas TI-Chill" width="56" height="56" fetchpriority="high" class="h-14 w-14 mx-auto object-contain mb-3">
        <h1 class="text-xl font-extrabold tracking-tight" style="color:var(--navy)">Vending Machine TI-Chill</h1>
        <p class="text-xs font-semibold mt-0.5" style="color:#475569">Pemesanan Mandiri via QRIS &amp; E-Wallet</p>

        <div class="my-6 p-4 rounded-xl flex justify-center" style="background:var(--well);border:1px solid #dbe4f8">
            <img src="<?= $qrCodeApiUrl ?>" alt="QR Code Halaman Pemesanan" width="240" height="240" class="w-60 h-60 object-contain bg-white p-2 rounded-lg">
        </div>

        <div class="rounded-lg py-3 px-3 mb-4" style="background:var(--butter);color:var(--navy);border:1px solid var(--gold)">
            <p class="text-xs font-extrabold">Scan QR dengan Kamera HP</p>
        </div>

        <ol class="text-left space-y-2 text-xs pt-4" style="color:#334155;border-top:1px solid #dbe4f8">
            <li class="flex items-start space-x-2">
                <span class="font-bold" style="color:var(--navy)">1.</span>
                <span>Arahkan kamera HP ke QR Code di atas untuk membuka halaman menu.</span>
            </li>
            <li class="flex items-start space-x-2">
                <span class="font-bold" style="color:var(--navy)">2.</span>
                <span>Pilih slot minuman yang tersedia dan selesaikan pembayaran.</span>
            </li>
            <li class="flex items-start space-x-2">
                <span class="font-bold" style="color:var(--navy)">3.</span>
                <span>Tunggu minuman diturunkan dan ambil di wadah bawah mesin.</span>
            </li>
        </ol>

        <p class="text-[11px] mt-6 font-mono truncate" style="color:#475569"><?= htmlspecialchars($machineUrl) ?></p>
    </main>

</body>
</html>
