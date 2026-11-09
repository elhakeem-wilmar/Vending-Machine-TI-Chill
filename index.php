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
    <script src="<?= $snapJsUrl ?>" data-client-key="<?= htmlspecialchars(MIDTRANS_CLIENT_KEY) ?>" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0a1680;
            --sky: #93b2f8;
            --gold: #f1b94c;
            --butter: #fbedb0;
            --canvas: #fcfdff;
            --well: #f2f6ff;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--canvas);
            color: var(--navy);
        }
        button, a, [role="button"] { cursor: pointer; }
        button:disabled { cursor: not-allowed; }
        :focus-visible {
            outline: 2px solid var(--navy);
            outline-offset: 2px;
        }
        .num-tabular { font-variant-numeric: tabular-nums; }
        .btn-primary {
            background: var(--navy);
            color: #fff;
            border-radius: 0.5rem;
            border-bottom: 2px solid #050b4c;
            transition: background .15s, transform .1s;
        }
        .btn-primary:hover:not(:disabled) { background: #1424a8; }
        .btn-primary:active:not(:disabled) { background: #070f5c; transform: translateY(1px); }
        .btn-wa {
            background: var(--gold);
            color: var(--navy);
            border: 1.5px solid var(--gold);
            border-radius: 0.5rem;
            transition: filter .15s;
        }
        .btn-wa:hover { filter: brightness(.95); }
        .btn-ig {
            background: rgba(252,253,255,.1);
            color: var(--canvas);
            border: 1.5px solid rgba(147,178,248,.5);
            border-radius: 0.5rem;
            transition: background .15s, border-color .15s;
        }
        .btn-ig:hover { background: rgba(252,253,255,.18); border-color: var(--sky); }
        .slot-card {
            background: #fff;
            border: 1px solid #dbe4f8;
            border-radius: 0.875rem;
            box-shadow: 0 2px 6px rgba(10, 22, 128, 0.04);
            transition: border-color .15s, box-shadow .15s;
        }
        .slot-card:hover {
            border-color: var(--sky);
            box-shadow: 0 6px 16px rgba(10, 22, 128, 0.07);
        }
        .slot-window {
            background: var(--well);
            border-radius: 0.625rem;
        }
        .slot-plate {
            background: var(--butter);
            color: var(--navy);
            border: 1px solid var(--gold);
            border-radius: 0.375rem;
            font-variant-numeric: tabular-nums;
        }
        .stock-ok {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
        }
        .stock-low {
            background: var(--butter);
            color: #7c2d12;
            border: 1px solid var(--gold);
            border-radius: 0.375rem;
        }
        .stock-empty {
            background: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fca5a5;
            border-radius: 0.375rem;
        }
        .loading-bar-fill { background: var(--gold); }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">
    <div>
        <!-- Header -->
        <header class="sticky top-0 z-40 px-4 py-3" style="background:#fff;border-top:3px solid var(--navy);border-bottom:1px solid #dbe4f8">
            <div class="max-w-5xl mx-auto flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <img src="./assets/logo.png" alt="Logo Kelas TI-Chill" width="40" height="40" fetchpriority="high" class="h-9 w-9 sm:h-10 sm:w-10 object-contain shrink-0">
                    <div class="min-w-0">
                        <span class="block text-sm sm:text-base font-extrabold tracking-tight leading-tight truncate" style="color:var(--navy)">Vending Machine TI-Chill</span>
                        <span class="block text-[11px] font-medium" style="color:#475569">Pemesanan minuman mandiri</span>
                    </div>
                </div>
                <span class="text-[11px] sm:text-xs font-semibold px-2.5 py-1 rounded-md shrink-0" style="background:var(--well);color:var(--navy)">
                    QRIS &middot; E-Wallet &middot; VA
                </span>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 pt-6 pb-8 sm:pt-8 sm:pb-12">
            <!-- Section Header -->
            <div class="mb-5 sm:mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-2">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color:var(--navy)">Pilih Minuman</h1>
                    <p class="text-xs sm:text-sm mt-0.5" style="color:#334155">Pilih slot yang tersedia, bayar via QRIS atau E-Wallet, lalu ambil minuman di wadah bawah mesin.</p>
                </div>
            </div>

            <!-- Grid 4 Slot Produk: 4 col desktop, 2 col mobile -->
            <?php if (empty($products)): ?>
                <div class="slot-card p-8 text-center">
                    <p class="text-sm font-bold" style="color:var(--navy)">Belum ada slot minuman yang terdaftar.</p>
                    <p class="text-xs mt-1" style="color:#475569">Silakan hubungi tim TI-Chill melalui kontak di bawah.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 sm:gap-5">
                    <?php foreach ($products as $index => $p): ?>
                        <?php
                        $isOutOfStock = ($p['stock'] <= 0);
                        $slotCode = str_pad((string)$p['slot_number'], 2, '0', STR_PAD_LEFT);
                        if ($isOutOfStock) {
                            $stockClass = 'stock-empty';
                            $stockLabel = 'Habis';
                        } elseif ($p['stock'] <= 3) {
                            $stockClass = 'stock-low';
                            $stockLabel = 'Sisa ' . (int)$p['stock'];
                        } else {
                            $stockClass = 'stock-ok';
                            $stockLabel = 'Stok ' . (int)$p['stock'];
                        }
                        ?>
                        <article class="slot-card p-2.5 sm:p-3 flex flex-col justify-between <?= $isOutOfStock ? 'opacity-65' : '' ?>">
                            <div>
                                <!-- Etalase Visual Minuman -->
                                <div class="slot-window relative p-3 sm:p-4 flex items-center justify-center">
                                    <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-1 z-10">
                                        <span class="slot-plate text-[11px] font-extrabold px-2 py-0.5">
                                            Slot <?= $slotCode ?>
                                        </span>
                                        <span class="<?= $stockClass ?> num-tabular text-[11px] font-bold px-2 py-0.5">
                                            <?= htmlspecialchars($stockLabel) ?>
                                        </span>
                                    </div>

                                    <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://placehold.co/300x180/fbedb0/0a1680?text=Slot+' . $p['slot_number']) ?>"
                                         alt="<?= htmlspecialchars($p['name']) ?>"
                                         width="240" height="160"
                                         <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                                         class="w-full h-36 sm:h-44 object-contain pt-5 pb-1">

                                    <?php if ($isOutOfStock): ?>
                                        <div class="absolute inset-0 rounded-[0.625rem] flex items-center justify-center" style="background:rgba(252,253,255,.82)">
                                            <span class="stock-empty text-xs font-extrabold px-3 py-1">STOK HABIS</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Detail Nama & Harga -->
                                <div class="px-1 pt-3">
                                    <h2 class="font-bold text-xs sm:text-sm line-clamp-1" style="color:#334155"><?= htmlspecialchars($p['name']) ?></h2>
                                    <p class="num-tabular text-base sm:text-xl font-extrabold mt-0.5 tracking-tight" style="color:var(--navy)">
                                        Rp <?= number_format($p['price'], 0, ',', '.') ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Tombol Aksi Utama -->
                            <div class="px-1 pt-3 mt-1">
                                <button
                                    type="button"
                                    onclick="orderProduct(<?= (int)$p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')"
                                    <?= $isOutOfStock ? 'disabled' : '' ?>
                                    class="btn-primary w-full py-2.5 text-xs sm:text-sm font-bold <?= $isOutOfStock ? 'opacity-45' : '' ?>">
                                    <?= $isOutOfStock ? 'Stok Habis' : 'Beli' ?>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <footer class="w-full mt-10" style="background:var(--navy);color:var(--canvas);border-top:3px solid var(--gold)">
        <div class="max-w-5xl mx-auto px-4 py-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-5" style="border-bottom:1px solid rgba(147,178,248,.25)">
                <div class="flex items-center gap-3">
                    <img src="./assets/logo.png" alt="Logo Kelas TI-Chill" width="48" height="48" loading="lazy" class="h-12 w-12 object-contain rounded-xl p-1.5 shrink-0" style="background:var(--canvas)">
                    <span class="text-base sm:text-lg font-extrabold tracking-tight" style="color:var(--canvas)">TI-Chill</span>
                </div>

                <div class="w-full sm:w-auto grid grid-cols-2 sm:flex items-center gap-2.5">
                    <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-wa text-xs font-bold px-3.5 py-2.5 inline-flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/></svg>
                        <span>+62 812-6690-6714</span>
                    </a>
                    <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-ig text-xs font-bold px-3.5 py-2.5 inline-flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                        <span>@ti.chill._</span>
                    </a>
                </div>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-[11px] text-center sm:text-left" style="color:#dbeafe">
                <span>&copy; <?= date('Y') ?> Kelas TI-Chill &middot; Pembayaran diproses melalui Midtrans</span>
                <button type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})" class="sm:hidden inline-flex items-center gap-1 font-semibold px-3.5 py-1.5 rounded-lg" style="background:rgba(252,253,255,.12);color:var(--butter);border:1px solid rgba(251,237,176,.35)">
                    &uarr; Kembali ke atas
                </button>
            </div>
        </div>
    </footer>

    <!-- Modal Status Pengeluaran Minuman -->
    <div id="dispenseModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(10,22,128,.45)">
        <div class="rounded-xl p-6 w-full max-w-sm text-center relative" style="background:#fff;border:1.5px solid var(--sky)">
            <div id="modalIcon" class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3" style="background:var(--butter);color:var(--navy)">
            </div>
            <h3 id="modalTitle" class="text-base font-extrabold mb-1" style="color:var(--navy)">Menyiapkan Pesanan</h3>
            <p id="modalDesc" class="text-xs leading-relaxed mb-5" style="color:#334155">
                Membuka halaman pembayaran...
            </p>

            <div id="loadingBar" class="w-full h-1.5 rounded-full overflow-hidden mb-4" style="background:#dbe4f8">
                <div class="loading-bar-fill h-full animate-pulse rounded-full w-full"></div>
            </div>

            <button id="modalCloseBtn" type="button" onclick="closeModal()" class="btn-primary w-full py-2.5 text-xs font-bold hidden">
                Tutup
            </button>
        </div>
    </div>

    <script>
        let currentOrderId = null;
        let pollInterval = null;

        const ICONS = {
            wait: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            warn: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
            err: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
            gear: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
            ok: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>'
        };

        async function orderProduct(productId, productName) {
            showModal(ICONS.wait, 'Menyiapkan Pesanan...', 'Membuka pilihan pembayaran untuk ' + productName + '...');

            try {
                const response = await fetch('api/create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });

                const data = await response.json();

                if (!data.success) {
                    showModal(ICONS.warn, 'Tidak Dapat Membuat Pesanan', data.error || 'Terjadi kendala saat membuat pesanan.', true);
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
                        showModal(ICONS.err, 'Pembayaran Gagal', 'Transaksi dibatalkan atau batas waktu pembayaran habis.', true);
                    },
                    onClose: function() {
                        checkOrCancelOrder(currentOrderId);
                    }
                });

            } catch (err) {
                showModal(ICONS.err, 'Koneksi Terputus', 'Gagal menghubungi server. Periksa koneksi internet Anda.', true);
            }
        }

        function handlePaymentSuccess() {
            showModal(ICONS.gear, 'Pembayaran Berhasil', 'Pesanan sedang disiapkan oleh mesin. Mohon tunggu sebentar...', false);
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
            showModal(ICONS.wait, 'Memeriksa Pembayaran...', 'Mengecek konfirmasi pembayaran untuk pesanan ' + returnedOrderId + '...', false);

            try {
                const res = await fetch('api/check_status.php?order_id=' + encodeURIComponent(returnedOrderId));
                const data = await res.json();
                if (data.payment_status === 'PAID') {
                    if (data.dispense_status === 'COMPLETED') {
                        showModal(ICONS.ok, 'Silakan Ambil Minuman', 'Minuman dari Slot ' + data.slot_number + ' sudah keluar di wadah bawah mesin.', true);
                    } else {
                        handlePaymentSuccess();
                    }
                } else {
                    showModal(ICONS.warn, 'Pembayaran Belum Selesai', 'Pembayaran untuk pesanan ini belum lunas atau telah dibatalkan.', true);
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
                        showModal(ICONS.gear, 'Mengeluarkan Minuman...', 'Minuman dari Slot ' + data.slot_number + ' sedang diturunkan ke wadah bawah.', false);
                    } else if (data.dispense_status === 'COMPLETED') {
                        clearInterval(pollInterval);
                        showModal(ICONS.ok, 'Silakan Ambil Minuman', 'Minuman dari Slot ' + data.slot_number + ' sudah keluar di wadah bawah mesin.', true);
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
                closeBtn.focus();
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

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const closeBtn = document.getElementById('modalCloseBtn');
                if (closeBtn && !closeBtn.classList.contains('hidden')) {
                    closeModal();
                }
            }
        });
    </script>
</body>
</html>
