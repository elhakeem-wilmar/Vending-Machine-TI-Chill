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
    <title>Smart Vending Machine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= $snapJsUrl ?>" data-client-key="<?= htmlspecialchars(MIDTRANS_CLIENT_KEY) ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen pb-12">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur border-b border-slate-700/60 sticky top-0 z-40 px-4 py-3 shadow-md">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-2xl">🥤</span>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-white">Smart Drink Station</h1>
                    <p class="text-xs text-emerald-400 font-medium">● Mesin Siap & Online</p>
                </div>
            </div>
            <a href="admin/index.php" class="text-xs bg-slate-700 hover:bg-slate-600 text-slate-300 px-3 py-1.5 rounded-lg border border-slate-600 transition">
                Admin
            </a>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 mt-6">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-4 mb-6 shadow-lg shadow-blue-500/20 text-white">
            <h2 class="text-lg font-bold">Pilih Minuman Dingin Anda</h2>
            <p class="text-xs text-blue-100 mt-1">Pembayaran aman dengan QRIS & E-Wallet via Midtrans. Minuman otomatis keluar begitu pembayaran berhasil!</p>
        </div>

        <!-- Grid 4 Slot Produk -->
        <div class="grid grid-cols-2 gap-4">
            <?php foreach ($products as $p): ?>
                <?php $isOutOfStock = ($p['stock'] <= 0); ?>
                <div class="bg-slate-800 border border-slate-700/80 rounded-2xl overflow-hidden shadow-sm flex flex-col justify-between transition transform active:scale-98">
                    <div class="relative">
                        <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/300x200?text=Minuman') ?>" 
                             alt="<?= htmlspecialchars($p['name']) ?>" 
                             class="w-full h-36 object-cover bg-slate-900">
                        <span class="absolute top-2 left-2 bg-black/70 backdrop-blur text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-white/20">
                            Slot <?= $p['slot_number'] ?>
                        </span>
                        <?php if ($isOutOfStock): ?>
                            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-[2px] flex items-center justify-center">
                                <span class="bg-rose-600 text-white font-bold text-xs px-3 py-1 rounded-full shadow">HABIS</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-3.5 flex flex-col flex-grow justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-slate-100 line-clamp-1"><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="text-base font-extrabold text-amber-400 mt-1">
                                Rp <?= number_format($p['price'], 0, ',', '.') ?>
                            </p>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-700/60 flex items-center justify-between">
                            <span class="text-[11px] <?= $p['stock'] > 3 ? 'text-slate-400' : 'text-amber-400 font-semibold' ?>">
                                Sisa: <?= $p['stock'] ?>
                            </span>

                            <button 
                                onclick="orderProduct(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')"
                                <?= $isOutOfStock ? 'disabled' : '' ?>
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center justify-center <?= $isOutOfStock ? 'bg-slate-700 text-slate-500 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white shadow-md shadow-blue-600/30' ?>">
                                Beli
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal Status Pengeluaran Minuman -->
    <div id="dispenseModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 w-full max-w-sm text-center shadow-2xl relative">
            <div id="modalIcon" class="w-16 h-16 bg-blue-500/20 text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                ⏳
            </div>
            <h3 id="modalTitle" class="text-lg font-bold text-white mb-1">Memproses Order...</h3>
            <p id="modalDesc" class="text-xs text-slate-300 leading-relaxed mb-6">
                Mohon tunggu sebentar, sistem sedang menghubungi gerbang pembayaran.
            </p>

            <div id="loadingBar" class="w-full bg-slate-700 h-2 rounded-full overflow-hidden mb-4">
                <div class="bg-blue-500 h-full animate-pulse rounded-full w-full"></div>
            </div>

            <button id="modalCloseBtn" onclick="closeModal()" class="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold rounded-xl transition hidden">
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

