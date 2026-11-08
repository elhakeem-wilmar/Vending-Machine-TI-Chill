<?php
// admin/index.php - Dashboard Admin & Login
session_start();
require_once __DIR__ . '/../config.php';

$message = '';
$messageType = '';

// Handle Login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: index.php');
        exit;
    } else {
        $message = 'Username atau password salah!';
        $messageType = 'error';
    }
}

// Cek Login
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle Aksi Admin jika sudah login
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update Produk
    if ($action === 'update_product') {
        $id = (int)$_POST['product_id'];
        $name = trim($_POST['name'] ?? '');
        $price = (int)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $imageUrl = trim($_POST['image_url'] ?? '');

        if ($id > 0 && !empty($name)) {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$name, $price, $stock, $imageUrl, $id]);
            $message = "Produk Slot {$id} berhasil diperbarui!";
            $messageType = 'success';
        }
    }

    // Manual Trigger Test Dispense
    if ($action === 'test_dispense') {
        $slotNumber = (int)$_POST['slot_number'];
        $testOrderId = 'TEST-' . date('YmdHis') . '-S' . $slotNumber;

        $stmt = $pdo->prepare("SELECT id FROM products WHERE slot_number = ?");
        $stmt->execute([$slotNumber]);
        $prod = $stmt->fetch();

        if ($prod) {
            $insert = $pdo->prepare("
                INSERT INTO orders (order_id, product_id, slot_number, amount, payment_status, dispense_status)
                VALUES (?, ?, ?, 0, 'PAID', 'READY_TO_DISPENSE')
            ");
            $insert->execute([$testOrderId, $prod['id'], $slotNumber]);
            $message = "Perintah test dispense Slot {$slotNumber} berhasil dimasukkan ke antrean ESP32!";
            $messageType = 'success';
        }
    }
}

// Jika belum login, tampilkan form login
if (!$isLoggedIn) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0a1680; --sky:#93b2f8; --gold:#f1b94c; --butter:#fbedb0; --canvas:#fcfdff; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--canvas); color:var(--navy); }
        .input-field { background:#fff; border:1.5px solid rgba(147,178,248,.6); border-radius:.5rem; color:var(--navy); transition:border-color .15s; }
        .input-field:focus { outline:none; border-color:var(--navy); }
        .btn-navy { background:var(--navy); color:#fff; border-radius:.5rem; transition:background .15s; }
        .btn-navy:hover { background:#1424a8; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-sm rounded-xl p-7" style="background:#fff;border:1.5px solid rgba(147,178,248,.45)">
        <div class="text-center mb-6">
            <img src="../assets/logo.png" alt="TI Chill" class="h-12 w-12 mx-auto object-contain mb-2">
            <h1 class="text-lg font-extrabold" style="color:var(--navy)">Login Administrator</h1>
            <p class="text-xs mt-0.5" style="color:#64748b">Vending Machine TI-Chill</p>
        </div>

        <?php if ($message): ?>
            <div class="p-3 mb-4 text-xs font-semibold rounded-lg" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:var(--navy)">Username</label>
                <input type="text" name="username" required class="input-field w-full px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:var(--navy)">Password</label>
                <input type="password" name="password" required class="input-field w-full px-3 py-2 text-sm">
            </div>
            <button type="submit" class="btn-navy w-full py-2.5 text-sm font-bold">
                Masuk
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="../index.php" class="text-xs font-semibold hover:underline" style="color:var(--navy)">← Kembali ke Halaman Pembeli</a>
        </div>
    </div>
</body>
</html>
<?php
exit;
}

// Mengambil Data untuk Dashboard
try {
    $products = $pdo->query("SELECT * FROM products ORDER BY slot_number ASC")->fetchAll();

    // Total Pendapatan & Penjualan
    $statsTotal = $pdo->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(amount), 0) as total_revenue FROM orders WHERE payment_status = 'PAID'")->fetch();
    $statsToday = $pdo->query("SELECT COUNT(*) as today_orders, COALESCE(SUM(amount), 0) as today_revenue FROM orders WHERE payment_status = 'PAID' AND DATE(paid_at) = CURDATE()")->fetch();

    // 20 Transaksi Terakhir
    $orders = $pdo->query("
        SELECT o.*, p.name as product_name 
        FROM orders o 
        LEFT JOIN products p ON o.product_id = p.id 
        ORDER BY o.id DESC 
        LIMIT 20
    ")->fetchAll();
} catch (PDOException $e) {
    die("<h3>Koneksi database sukses, tetapi tabel belum dibuat.</h3><p>Error: " . htmlspecialchars($e->getMessage()) . "</p><p><b>Solusi:</b> Buka phpMyAdmin di cPanel ➔ pilih database Anda ➔ klik tab <b>Import</b> ➔ pilih file <b>database.sql</b>.</p>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Vending Machine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0a1680; --sky:#93b2f8; --gold:#f1b94c; --butter:#fbedb0; --canvas:#fcfdff; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--canvas); color:var(--navy); }
        .card { background:#fff; border:1.5px solid rgba(147,178,248,.45); border-radius:.75rem; }
        .input-field { background:#fff; border:1.5px solid rgba(147,178,248,.55); border-radius:.5rem; color:var(--navy); transition:border-color .15s; }
        .input-field:focus { outline:none; border-color:var(--navy); }
        .btn-navy { background:var(--navy); color:#fff; border-radius:.5rem; transition:background .15s; }
        .btn-navy:hover { background:#1424a8; }
        .btn-gold { background:var(--gold); color:var(--navy); border-radius:.5rem; font-weight:700; transition:filter .15s; }
        .btn-gold:hover { filter:brightness(.95); }
        .badge-slot { background:var(--butter); color:var(--navy); border:1px solid var(--gold); border-radius:.375rem; font-weight:700; font-size:.7rem; padding:.15rem .5rem; }
    </style>
</head>
<body class="min-h-screen pb-12">
    <!-- Navbar -->
    <header class="sticky top-0 z-40 px-4 sm:px-6 py-3" style="background:var(--canvas);border-bottom:1.5px solid rgba(147,178,248,.4)">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <img src="../assets/logo.png" alt="TI Chill" class="h-9 w-9 object-contain">
                <div>
                    <h1 class="text-base font-extrabold leading-tight" style="color:var(--navy)">Admin TI-Chill</h1>
                    <p class="text-[11px]" style="color:#64748b">Stok, Harga & Riwayat Pesanan</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="print_qr.php" target="_blank" class="btn-gold text-xs px-3.5 py-1.5 inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
                    Cetak QR
                </a>
                <a href="../index.php" target="_blank" class="text-xs font-semibold px-3.5 py-1.5 rounded-lg transition inline-flex items-center gap-1.5" style="border:1.5px solid var(--sky);color:var(--navy);background:#fff">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"/><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"/><circle cx="18" cy="20" r="2"/><circle cx="8" cy="20" r="2"/></svg>
                    Halaman Pembeli
                </a>
                <a href="logout.php" class="text-xs font-semibold px-3.5 py-1.5 rounded-lg text-white" style="background:#dc2626">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 mt-6">
        <?php if ($message): ?>
            <div class="p-3.5 mb-6 text-xs font-semibold rounded-lg" style="<?= $messageType === 'success' ? 'background:#dcfce7;color:#15803d;border:1px solid #86efac' : 'background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Ringkasan Data Nyata -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="card p-4">
                <p class="text-xs font-semibold" style="color:#64748b">Penjualan Hari Ini</p>
                <h3 class="text-xl font-extrabold mt-1" style="color:var(--navy)">
                    Rp <?= number_format($statsToday['today_revenue'], 0, ',', '.') ?>
                </h3>
                <span class="text-[11px] mt-1.5 block" style="color:#64748b"><?= $statsToday['today_orders'] ?> transaksi lunas hari ini</span>
            </div>

            <div class="card p-4">
                <p class="text-xs font-semibold" style="color:#64748b">Total Akumulasi</p>
                <h3 class="text-xl font-extrabold mt-1" style="color:var(--navy)">
                    Rp <?= number_format($statsTotal['total_revenue'], 0, ',', '.') ?>
                </h3>
                <span class="text-[11px] mt-1.5 block" style="color:#64748b"><?= $statsTotal['total_orders'] ?> total transaksi lunas</span>
            </div>

            <div class="card p-4">
                <p class="text-xs font-semibold" style="color:#64748b">Konfigurasi Sistem</p>
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="text-xs font-bold px-2 py-0.5 rounded" style="background:var(--butter);color:var(--navy);border:1px solid var(--gold)">
                        Midtrans: <?= MIDTRANS_IS_PRODUCTION ? 'PRODUCTION' : 'SANDBOX' ?>
                    </span>
                </div>
                <span class="text-[11px] mt-2 block font-mono" style="color:#64748b">Queue: api/dispense_queue.php</span>
            </div>
        </div>

        <!-- Pengaturan 4 Slot Produk -->
        <h2 class="text-base font-extrabold mb-3" style="color:var(--navy)">Pengaturan 4 Slot Minuman</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
            <?php foreach ($products as $p): ?>
                <div class="card p-4 flex flex-col justify-between">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">

                        <div class="flex items-center justify-between mb-3 pb-2" style="border-bottom:1px solid rgba(147,178,248,.3)">
                            <span class="badge-slot">Slot <?= $p['slot_number'] ?></span>
                            <span class="text-xs font-semibold" style="color:<?= $p['stock'] > 0 ? '#15803d' : '#dc2626' ?>">
                                <?= $p['stock'] > 0 ? "Stok: {$p['stock']}" : 'Habis' ?>
                            </span>
                        </div>

                        <div class="space-y-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold mb-1" style="color:#475569">Nama Minuman</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold mb-1" style="color:#475569">Harga (Rp)</label>
                                <input type="number" name="price" value="<?= $p['price'] ?>" required min="0" step="500" class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold mb-1" style="color:#475569">Jumlah Stok</label>
                                <input type="number" name="stock" value="<?= $p['stock'] ?>" required min="0" class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold mb-1" style="color:#475569">URL Gambar</label>
                                <input type="text" name="image_url" value="<?= htmlspecialchars($p['image_url']) ?>" class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                        </div>

                        <button type="submit" class="btn-navy w-full mt-4 py-2 text-xs font-bold">
                            Simpan Slot <?= $p['slot_number'] ?>
                        </button>
                    </form>

                    <!-- Test Dispense -->
                    <form method="POST" class="mt-2.5 pt-2.5" style="border-top:1px solid rgba(147,178,248,.3)">
                        <input type="hidden" name="action" value="test_dispense">
                        <input type="hidden" name="slot_number" value="<?= $p['slot_number'] ?>">
                        <button type="submit" onclick="return confirm('Uji coba putar servo Slot <?= $p['slot_number'] ?>?')"
                                class="w-full py-1.5 text-[11px] font-bold rounded-lg transition" style="background:var(--butter);color:var(--navy);border:1px solid var(--gold)">
                            Test Putar Servo
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Riwayat Transaksi -->
        <h2 class="text-base font-extrabold mb-3" style="color:var(--navy)">Riwayat Transaksi Terakhir</h2>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead style="background:rgba(147,178,248,.12);border-bottom:1.5px solid rgba(147,178,248,.35)">
                        <tr>
                            <th class="py-3 px-4 font-bold" style="color:var(--navy)">Order ID</th>
                            <th class="py-3 px-4 font-bold" style="color:var(--navy)">Produk</th>
                            <th class="py-3 px-4 font-bold" style="color:var(--navy)">Slot</th>
                            <th class="py-3 px-4 font-bold" style="color:var(--navy)">Nominal</th>
                            <th class="py-3 px-4 font-bold" style="color:var(--navy)">Status Bayar</th>
                            <th class="py-3 px-4 font-bold" style="color:var(--navy)">Status Dispenser</th>
                            <th class="py-3 px-4 font-bold" style="color:var(--navy)">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="py-6 text-center" style="color:#94a3b8">Belum ada transaksi tercatat</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <tr style="border-top:1px solid rgba(147,178,248,.2)">
                                    <td class="py-3 px-4 font-mono" style="color:#475569"><?= htmlspecialchars($o['order_id']) ?></td>
                                    <td class="py-3 px-4 font-semibold" style="color:var(--navy)"><?= htmlspecialchars($o['product_name'] ?? 'Test Manual') ?></td>
                                    <td class="py-3 px-4">
                                        <span class="badge-slot">Slot <?= $o['slot_number'] ?></span>
                                    </td>
                                    <td class="py-3 px-4 font-bold" style="color:var(--navy)">Rp <?= number_format($o['amount'], 0, ',', '.') ?></td>
                                    <td class="py-3 px-4">
                                        <?php if ($o['payment_status'] === 'PAID'): ?>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded" style="background:#dcfce7;color:#15803d;border:1px solid #86efac">PAID</span>
                                        <?php elseif ($o['payment_status'] === 'PENDING'): ?>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded" style="background:var(--butter);color:#9a3412;border:1px solid var(--gold)">PENDING</span>
                                        <?php else: ?>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5"><?= htmlspecialchars($o['payment_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if ($o['dispense_status'] === 'COMPLETED'): ?>
                                            <span class="font-bold" style="color:#15803d">Selesai</span>
                                        <?php elseif ($o['dispense_status'] === 'DISPENSING'): ?>
                                            <span class="font-bold" style="color:var(--navy)">Memutar Servo...</span>
                                        <?php elseif ($o['dispense_status'] === 'READY_TO_DISPENSE'): ?>
                                            <span class="font-bold" style="color:#b45309">Antre ESP32</span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8"><?= htmlspecialchars($o['dispense_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4" style="color:#64748b"><?= htmlspecialchars($o['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
