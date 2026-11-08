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

$igUrl = 'https://www.instagram.com/ti.chill._?vrfl=bnZqMWNlcXlmZDJ5';
$waUrl = 'https://wa.me/6281266906714';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vending Machine TI-Chill</title>
    <link rel="icon" type="image/png" href="./assets/logo.png">
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
        .btn-wa {
            background: var(--gold);
            color: var(--navy);
            border: 1.5px solid var(--gold);
            border-radius: 0.5rem;
            transition: filter .15s;
        }
        .btn-wa:hover { filter: brightness(.95); }
        .btn-ig {
            background: rgba(252,253,255,.08);
            color: var(--canvas);
            border: 1.5px solid rgba(147,178,248,.45);
            border-radius: 0.5rem;
            transition: background .15s, border-color .15s;
        }
        .btn-ig:hover { background: rgba(252,253,255,.16); border-color: var(--sky); }
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
        <header class="sticky top-0 z-40 px-4 py-3" style="background:#fff;border-top:3px solid var(--navy);border-bottom:1.5px solid rgba(147,178,248,.4)">
            <div class="max-w-5xl mx-auto flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    <img src="./assets/logo.png" alt="TI Chill Logo" class="h-9 w-9 sm:h-10 sm:w-10 object-contain shrink-0">
                    <div class="min-w-0">
                        <span class="block text-sm sm:text-base font-extrabold tracking-tight leading-tight truncate" style="color:var(--navy)">Vending Machine TI-Chill</span>
                        <span class="block text-[11px] font-medium" style="color:#64748b">Layanan Minuman Otomatis</span>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0" style="background:#dcfce7;color:#15803d;border:1px solid #86efac">
                    <span class="h-1.5 w-1.5 rounded-full" style="background:#16a34a"></span>
                    Online
                </span>
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
                    4 Slot Tersedia · QRIS, E-Wallet & Virtual Account
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

    <footer class="w-full mt-14" style="background:var(--navy);color:var(--canvas);border-top:3px solid var(--gold)">
        <div class="max-w-5xl mx-auto px-4 py-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-5" style="border-bottom:1px solid rgba(147,178,248,.22)">
                <img src="./assets/logo.png" alt="TI-Chill Logo" class="h-12 w-12 object-contain rounded-xl p-1.5 shrink-0" style="background:var(--canvas)">

                <div class="w-full sm:w-auto grid grid-cols-2 sm:flex items-center gap-2.5">
                    <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-wa text-xs font-bold px-3.5 py-2 inline-flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/></svg>
                        <span>+62 812-6690-6714</span>
                    </a>
                    <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-ig text-xs font-bold px-3.5 py-2 inline-flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                        <span>@ti.chill._</span>
                    </a>
                </div>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-[11px] text-center sm:text-left" style="color:rgba(252,253,255,.65)">
                <span>&copy; <?= date('Y') ?> Kelas TI-Chill &middot; Pembayaran diproses melalui Midtrans</span>
                <button type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})" class="sm:hidden inline-flex items-center gap-1 font-semibold px-3.5 py-1 rounded-full" style="background:rgba(252,253,255,.1);color:var(--butter);border:1px solid rgba(251,237,176,.3)">
                    &uarr; Kembali ke atas
                </button>
            </div>
        </div>
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
