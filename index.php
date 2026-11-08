<?php
// index.php - Halaman Pemesanan Minuman untuk Pembeli (Mobile First)
require_once __DIR__ . '/config.php';

// Ambil 4 slot produk
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY slot_number ASC LIMIT 4");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("<h3>Koneksi database sukses, tetapi tabel belum dibuat.</h3><p>Error: " . htmlspecialchars($e->getMessage()) . "</p><p><b>Solusi:</b> Buka phpMyAdmin di cPanel ➔ pilih database Anda ➔ klik tab <b>Import</b> ➔ pilih file <b>database.sql</b>.</p>");
}

$snapJsUrl = MIDTRANS_IS_PRODUCTION
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vending Machine TI-Chill</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= $snapJsUrl ?>" data-client-key="<?= htmlspecialchars(MIDTRANS_CLIENT_KEY) ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0a1680;
            --sky: #93b2f8;
            --gold: #f1b94c;
            --butter: #fbedb0;
            --canvas: #fcfdff;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--canvas); color: var(--navy); }
        .btn-primary {
            background: var(--navy);
            color: #fff;
            border-radius: 15px;
            transition: background .15s, transform .12s, box-shadow .12s;
            box-shadow: 0 4px 14px rgba(10,22,128,.2);
        }
        .btn-primary:hover { background: #0d1fa0; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(10,22,128,.28); }
        .btn-primary:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(10,22,128,.18); }
        .card { background: #fff; border: 1px solid rgba(147,178,248,.35); border-radius: 0.8rem; box-shadow: 0 2px 8px rgba(10,22,128,.06); transition: box-shadow .15s, transform .12s; }
        .card:hover { box-shadow: 0 8px 24px rgba(10,22,128,.1); transform: translateY(-2px); }
        .badge-slot { background: rgba(147,178,248,.2); color: var(--navy); border: 1px solid rgba(147,178,248,.5); border-radius: 9px; }
        .tag-stock-low { color: #c05200; background: rgba(241,185,76,.15); border-radius: 10px; padding: 2px 8px; }
        .tag-stock-ok  { color: #555; }
        .loading-bar-fill { background: var(--gold); }
    </style>
</head>
<body class="min-h-screen pb-12" style="background:var(--canvas)">
    <!-- Header -->
    <header class="sticky top-0 z-40 px-4 py-3" style="background:rgba(252,253,255,.88);backdrop-filter:blur(12px);border-bottom:1px solid rgba(147,178,248,.3);box-shadow:0 2px 12px rgba(10,22,128,.06)">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <img src="./assets/logo.png" alt="TI Chill Logo" class="h-10 w-10 object-contain">
                <div>
                    <h1 class="text-base font-extrabold tracking-tight" style="color:var(--navy)">Vending Machine TI-Chill</h1>
                </div>
            </div>
            <a href="admin/index.php" class="text-xs font-semibold px-4 py-1.5 rounded-full transition" style="border:1.5px solid var(--sky);color:var(--navy);background:rgba(147,178,248,.12)">
                Admin
            </a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 mt-7">
        <!-- Hero banner -->
        <div class="rounded-2xl p-5 mb-7 flex items-center justify-between gap-4" style="background:linear-gradient(135deg,var(--navy) 0%,#1a2fa8 100%)">
            <div>
                <h2 class="text-lg font-extrabold text-white leading-tight">Pilih Minuman Anda </h2>
                <p class="text-xs mt-1" style="color:rgba(255,255,255,.75)">Bayar menggunakan QRIS / e-wallet</p>
            </div>
            <span class="text-4xl select-none shrink-0">🥤</span>
        </div>

        <!-- Grid 4 Slot Produk: 4 col desktop, 2 col mobile -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($products as $p): ?>
                <?php $isOutOfStock = ($p['stock'] <= 0); ?>
                <div class="card overflow-hidden flex flex-col <?= $isOutOfStock ? 'opacity-70' : '' ?>">
                    <div class="relative">
                        <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://placehold.co/300x180/fbedb0/0a1680?text=Minuman') ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             class="w-full h-36 object-cover" style="background:var(--butter)">
                        <span class="badge-slot absolute top-2 left-2 text-[10px] font-bold px-2 py-0.5">
                            Slot <?= $p['slot_number'] ?>
                        </span>
                        <?php if ($isOutOfStock): ?>
                            <div class="absolute inset-0 flex items-center justify-center" style="background:rgba(252,253,255,.82);backdrop-filter:blur(2px)">
                                <span class="text-xs font-extrabold px-3 py-1 rounded-full" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5">HABIS</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-3.5 flex flex-col flex-grow justify-between">
                        <div>
                            <h3 class="font-bold text-sm line-clamp-1" style="color:var(--navy)"><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="text-base font-extrabold mt-0.5" style="color:var(--gold)">
                                Rp <?= number_format($p['price'], 0, ',', '.') ?>
                            </p>
                        </div>

                        <div class="mt-3 pt-2.5 flex items-center justify-between" style="border-top:1px solid rgba(147,178,248,.25)">
                            <span class="text-[11px] font-semibold <?= $p['stock'] > 3 ? 'tag-stock-ok' : 'tag-stock-low' ?>">
                                Sisa: <?= $p['stock'] ?>
                            </span>
                            <button
                                onclick="orderProduct(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')"
                                <?= $isOutOfStock ? 'disabled' : '' ?>
                                class="btn-primary text-xs font-bold px-4 py-1.5 <?= $isOutOfStock ? 'opacity-40 cursor-not-allowed' : '' ?>"
                                <?= $isOutOfStock ? 'style="pointer-events:none"' : '' ?>>
                                Beli
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal Status Pengeluaran Minuman -->
    <div id="dispenseModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(10,22,128,.35);backdrop-filter:blur(6px)">
        <div class="rounded-3xl p-6 w-full max-w-sm text-center relative" style="background:#fff;border:1px solid rgba(147,178,248,.4);box-shadow:0 16px 48px rgba(10,22,128,.15)">
            <div id="modalIcon" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl" style="background:rgba(147,178,248,.15)">
                ⏳
            </div>
            <h3 id="modalTitle" class="text-lg font-bold mb-1" style="color:var(--navy)">Memproses Order...</h3>
            <p id="modalDesc" class="text-xs leading-relaxed mb-6" style="color:#555">
                Mohon tunggu sebentar, sistem sedang menghubungi gerbang pembayaran.
            </p>

            <div id="loadingBar" class="w-full h-2 rounded-full overflow-hidden mb-4" style="background:rgba(147,178,248,.2)">
                <div class="loading-bar-fill h-full animate-pulse rounded-full w-full"></div>
            </div>

            <button id="modalCloseBtn" onclick="closeModal()" class="btn-primary w-full py-2.5 text-xs font-bold hidden">
                Tutup
            </button>
        </div>
    </div>

    <script>
        let currentOrderId = null;
        let pollInterval = null;

        async function orderProduct(productId, productName) {
            showModal('⏳', 'Membuat Pesanan...', 'Menghubungi server untuk memproses pesanan ' + productName + '...');

            try {
                const response = await fetch('api/create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });

                const data = await response.json();

                if (!data.success) {
                    showModal('⚠️', 'Gagal', data.error || 'Terjadi kesalahan sistem', true);
                    return;
                }

                currentOrderId = data.order_id;

                // Tutup modal loading sejenak dan buka Snap Popup
                hideModal();

                window.snap.pay(data.snap_token, {
                    onSuccess: function(result) {
                        handlePaymentSuccess();
                    },
                    onPending: function(result) {
                        showModal('⏳', 'Menunggu Pembayaran', 'Selesaikan pembayaran Anda di aplikasi e-wallet / m-banking.', false);
                        startPollingStatus(currentOrderId);
                    },
                    onError: function(result) {
                        showModal('❌', 'Pembayaran Gagal', 'Transaksi dibatalkan atau waktu pembayaran habis.', true);
                    },
                    onClose: function() {
                        // Jika ditutup, cek apakah sudah bayar
                        checkImmediateStatus(currentOrderId);
                    }
                });

            } catch (err) {
                showModal('❌', 'Koneksi Error', err.message, true);
            }
        }

        function handlePaymentSuccess() {
            showModal('🎉', 'Pembayaran Berhasil!', 'Instruksi dikirim ke mesin. ESP32 sedang memutar servo untuk mengeluarkan minuman Anda...', false);
            startPollingStatus(currentOrderId);
        }

        async function checkImmediateStatus(orderId) {
            try {
                const res = await fetch('api/check_status.php?order_id=' + encodeURIComponent(orderId));
                const data = await res.json();
                if (data.payment_status === 'PAID') {
                    handlePaymentSuccess();
                }
            } catch (e) {}
        }

        function startPollingStatus(orderId) {
            if (pollInterval) clearInterval(pollInterval);

            pollInterval = setInterval(async () => {
                try {
                    const res = await fetch('api/check_status.php?order_id=' + encodeURIComponent(orderId));
                    const data = await res.json();

                    if (!data.success) return;

                    if (data.dispense_status === 'DISPENSING') {
                        showModal('⚙️', 'Sedang Dikeluarkan...', 'Motor servo di Slot ' + data.slot_number + ' sedang berputar. Minuman Anda akan segera jatuh ke wadah!', false);
                    } else if (data.dispense_status === 'COMPLETED') {
                        clearInterval(pollInterval);
                        showModal('✅', 'Selesai!', 'Minuman Anda telah dikeluarkan di Slot ' + data.slot_number + '. Silakan ambil dan nikmati!', true);
                    }
                } catch (e) {
                    console.error('Polling error:', e);
                }
            }, 2000);
        }

        function showModal(icon, title, desc, showClose = false) {
            document.getElementById('modalIcon').innerText = icon;
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalDesc').innerText = desc;
            
            const closeBtn = document.getElementById('modalCloseBtn');
            const loadingBar = document.getElementById('loadingBar');

            if (showClose) {
                closeBtn.classList.remove('hidden');
                loadingBar.classList.add('hidden');
            } else {
                closeBtn.classList.add('hidden');
                loadingBar.classList.remove('hidden');
            }

            document.getElementById('dispenseModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('dispenseModal').classList.add('hidden');
        }

        function closeModal() {
            hideModal();
            window.location.reload();
        }
    </script>
</body>
</html>
