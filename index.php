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
            border-radius: 0.625rem;
            transition: background .15s;
        }
        .btn-primary:hover { background: #1424a8; }
        .btn-primary:active { background: #070f5c; }
        .card { background: #fff; border: 1.5px solid rgba(147,178,248,.45); border-radius: 0.875rem; }
        .badge-slot { background: var(--butter); color: var(--navy); border: 1px solid var(--gold); border-radius: 0.375rem; }
        .tag-stock-low { color: #9a3412; background: var(--butter); border-radius: 0.375rem; padding: 2px 7px; }
        .tag-stock-ok  { color: #475569; }
        .loading-bar-fill { background: var(--gold); }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between" style="background:var(--canvas)">
    <div>
        <!-- Header -->
        <header class="sticky top-0 z-40 px-4 py-3" style="background:var(--canvas);border-bottom:1.5px solid rgba(147,178,248,.4)">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <img src="./assets/logo.png" alt="TI Chill Logo" class="h-9 w-9 object-contain">
                    <span class="text-base font-extrabold tracking-tight" style="color:var(--navy)">TI-Chill</span>
                </div>
                <span class="text-xs font-semibold" style="color:#475569">Vending Machine Mandiri</span>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 mt-6">
            <!-- Section Header -->
            <div class="mb-5 pb-4 flex flex-col sm:flex-row sm:items-end justify-between gap-2" style="border-bottom:1px solid rgba(147,178,248,.35)">
                <div>
                    <h1 class="text-xl font-extrabold tracking-tight" style="color:var(--navy)">Pilih Minuman</h1>
                    <p class="text-xs mt-0.5" style="color:#475569">Klik Beli, selesaikan pembayaran, dan ambil minuman di wadah bawah mesin.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-md self-start sm:self-auto" style="background:var(--butter);color:var(--navy)">
                    <span class="h-2 w-2 rounded-full" style="background:#16a34a"></span>
                    4 Slot Tersedia · QRIS & E-Wallet
                </span>
            </div>

            <!-- Grid 4 Slot Produk: 4 col desktop, 2 col mobile -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($products as $p): ?>
                    <?php $isOutOfStock = ($p['stock'] <= 0); ?>
                    <div class="card overflow-hidden flex flex-col <?= $isOutOfStock ? 'opacity-60' : '' ?>">
                        <div class="relative" style="border-bottom:1px solid rgba(147,178,248,.3)">
                            <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://placehold.co/300x180/fbedb0/0a1680?text=Slot+' . $p['slot_number']) ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 class="w-full h-36 object-contain p-2" style="background:#fff">
                            <span class="badge-slot absolute top-2.5 left-2.5 text-[11px] font-bold px-2 py-0.5">
                                Slot <?= $p['slot_number'] ?>
                            </span>
                            <?php if ($isOutOfStock): ?>
                                <div class="absolute inset-0 flex items-center justify-center" style="background:rgba(252,253,255,.85)">
                                    <span class="text-xs font-extrabold px-3 py-1 rounded-md" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5">HABIS</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-3.5 flex flex-col flex-grow justify-between">
                            <div>
                                <h2 class="font-bold text-sm line-clamp-1" style="color:var(--navy)"><?= htmlspecialchars($p['name']) ?></h2>
                                <p class="text-base font-extrabold mt-0.5" style="color:var(--navy)">
                                    Rp <?= number_format($p['price'], 0, ',', '.') ?>
                                </p>
                            </div>

                            <div class="mt-3 pt-2.5 flex items-center justify-between" style="border-top:1px solid rgba(147,178,248,.25)">
                                <span class="text-xs font-semibold <?= $p['stock'] > 3 ? 'tag-stock-ok' : 'tag-stock-low' ?>">
                                    Stok: <?= $p['stock'] ?>
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
    </div>

    <footer class="max-w-5xl w-full mx-auto px-4 py-6 mt-10 text-xs flex flex-col sm:flex-row items-center justify-between gap-2" style="border-top:1px solid rgba(147,178,248,.35);color:#64748b">
        <span>Proyek TI-Chill · Vending Machine Otomatis</span>
        <span>Pembayaran QRIS via Midtrans</span>
    </footer>

    <!-- Modal Status Pengeluaran Minuman -->
    <div id="dispenseModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(10,22,128,.4)">
        <div class="rounded-2xl p-6 w-full max-w-sm text-center relative" style="background:#fff;border:1.5px solid var(--sky)">
            <div id="modalIcon" class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3" style="background:var(--butter);color:var(--navy)">
            </div>
            <h3 id="modalTitle" class="text-base font-extrabold mb-1" style="color:var(--navy)">Memproses Pesanan</h3>
            <p id="modalDesc" class="text-xs leading-relaxed mb-5" style="color:#475569">
                Menghubungi gerbang pembayaran...
            </p>

            <div id="loadingBar" class="w-full h-1.5 rounded-full overflow-hidden mb-4" style="background:rgba(147,178,248,.25)">
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

        const ICONS = {
            wait: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            warn: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
            err: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
            gear: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
            ok: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>'
        };

        async function orderProduct(productId, productName) {
            showModal(ICONS.wait, 'Membuat Pesanan...', 'Memproses pesanan ' + productName + '...');

            try {
                const response = await fetch('api/create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });

                const data = await response.json();

                if (!data.success) {
                    showModal(ICONS.warn, 'Gagal Membuat Pesanan', data.error || 'Terjadi kesalahan sistem', true);
                    return;
                }

                currentOrderId = data.order_id;
                hideModal();

                window.snap.pay(data.snap_token, {
                    onSuccess: function() {
                        handlePaymentSuccess();
                    },
                    onPending: function() {
                        checkOrCancelOrder(currentOrderId);
                    },
                    onError: function() {
                        showModal(ICONS.err, 'Pembayaran Gagal', 'Transaksi dibatalkan atau waktu pembayaran habis.', true);
                    },
                    onClose: function() {
                        checkOrCancelOrder(currentOrderId);
                    }
                });

            } catch (err) {
                showModal(ICONS.err, 'Koneksi Gagal', err.message, true);
            }
        }

        function handlePaymentSuccess() {
            showModal(ICONS.gear, 'Pembayaran Diterima', 'Mengirim perintah ke ESP32 untuk memutar motor slot...', false);
            startPollingStatus(currentOrderId);
        }

        async function checkOrCancelOrder(orderId) {
            if (!orderId) return;
            try {
                const res = await fetch('api/check_status.php?order_id=' + encodeURIComponent(orderId) + '&cancel=1');
                const data = await res.json();
                if (data.payment_status === 'PAID') {
                    handlePaymentSuccess();
                } else {
                    if (pollInterval) clearInterval(pollInterval);
                    hideModal();
                }
            } catch (e) {
                hideModal();
            }
        }

        // Auto-resume pengecekan jika kembali dari halaman Midtrans Simulator (?order_id=...)
        window.addEventListener('DOMContentLoaded', async () => {
            const params = new URLSearchParams(window.location.search);
            const returnedOrderId = params.get('order_id');
            if (!returnedOrderId) return;

            currentOrderId = returnedOrderId;
            window.history.replaceState({}, document.title, window.location.pathname);
            showModal(ICONS.wait, 'Memeriksa Pembayaran...', 'Mengecek status pembayaran pesanan ' + returnedOrderId + '...', false);

            try {
                const res = await fetch('api/check_status.php?order_id=' + encodeURIComponent(returnedOrderId));
                const data = await res.json();
                if (data.payment_status === 'PAID') {
                    if (data.dispense_status === 'COMPLETED') {
                        showModal(ICONS.ok, 'Selesai', 'Minuman dari Slot ' + data.slot_number + ' sudah keluar. Silakan ambil di wadah bawah.', true);
                    } else {
                        handlePaymentSuccess();
                    }
                } else {
                    showModal(ICONS.warn, 'Pembayaran Belum Selesai', 'Status pembayaran untuk pesanan ini belum lunas atau dibatalkan.', true);
                }
            } catch (e) {
                showModal(ICONS.err, 'Gagal Mengecek Status', 'Tidak dapat menghubungi server.', true);
            }
        });

        function startPollingStatus(orderId) {
            if (pollInterval) clearInterval(pollInterval);

            pollInterval = setInterval(async () => {
                try {
                    const res = await fetch('api/check_status.php?order_id=' + encodeURIComponent(orderId));
                    const data = await res.json();

                    if (!data.success) return;

                    if (data.dispense_status === 'DISPENSING') {
                        showModal(ICONS.gear, 'Mengeluarkan Minuman...', 'Motor Slot ' + data.slot_number + ' sedang berputar.', false);
                    } else if (data.dispense_status === 'COMPLETED') {
                        clearInterval(pollInterval);
                        showModal(ICONS.ok, 'Selesai', 'Minuman dari Slot ' + data.slot_number + ' sudah keluar. Silakan ambil di wadah bawah.', true);
                    }
                } catch (e) {
                    console.error('Polling error:', e);
                }
            }, 2000);
        }

        function showModal(iconSvg, title, desc, showClose = false) {
            document.getElementById('modalIcon').innerHTML = iconSvg;
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
