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
        .input-field { background:#fff; border:1.5px solid rgba(147,178,248,.5); border-radius:.75rem; color:var(--navy); transition:border-color .15s, box-shadow .15s; }
        .input-field:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(147,178,248,.3); }
        .btn-navy { background:var(--navy); color:#fff; border-radius:9999px; transition:background .15s, transform .12s; box-shadow:0 4px 14px rgba(10,22,128,.2); }
        .btn-navy:hover { background:#0d1fa0; transform:translateY(-1px); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-sm rounded-2xl p-7 shadow-xl" style="background:#fff;border:1px solid rgba(147,178,248,.3)">
        <div class="text-center mb-6">
            <img src="../assets/logo.png" alt="TI Chill" class="h-14 w-14 mx-auto object-contain mb-2">
            <h1 class="text-xl font-extrabold" style="color:var(--navy)">Login Administrator</h1>
            <p class="text-xs mt-1" style="color:#888">Vending Machine TI-Chill</p>
        </div>

        <?php if ($message): ?>
            <div class="p-3 mb-4 text-xs font-semibold rounded-xl" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5">
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
                Login
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="../index.php" class="text-xs hover:underline" style="color:var(--sky)">← Kembali ke Halaman Pembeli</a>
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
        .card { background:#fff; border:1px solid rgba(147,178,248,.3); border-radius:1rem; box-shadow:0 2px 10px rgba(10,22,128,.06); }
        .input-field { background:#f7f8ff; border:1.5px solid rgba(147,178,248,.5); border-radius:.625rem; color:var(--navy); transition:border-color .15s, box-shadow .15s; }
        .input-field:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(147,178,248,.25); }
        .btn-navy { background:var(--navy); color:#fff; border-radius:9999px; transition:background .15s; box-shadow:0 3px 12px rgba(10,22,128,.2); }
        .btn-navy:hover { background:#0d1fa0; }
        .btn-gold { background:var(--gold); color:var(--navy); border-radius:9999px; font-weight:700; transition:filter .15s; }
        .btn-gold:hover { filter:brightness(1.08); }
        .badge-slot { background:rgba(147,178,248,.2); color:var(--navy); border:1px solid rgba(147,178,248,.5); border-radius:9999px; font-weight:700; font-size:.7rem; padding:.2rem .6rem; }
    </style>
</head>
<body class="min-h-screen pb-12">
    <!-- Navbar -->
    <header class="sticky top-0 z-40 px-6 py-3" style="background:rgba(252,253,255,.9);backdrop-filter:blur(12px);border-bottom:1px solid rgba(147,178,248,.3);box-shadow:0 2px 10px rgba(10,22,128,.06)">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="../assets/logo.png" alt="TI Chill" class="h-9 w-9 object-contain">
                <div>
                    <h1 class="text-base font-extrabold" style="color:var(--navy)">Admin Dashboard</h1>
                    <p class="text-[11px]" style="color:#888">Kelola Produk, Stok & Transaksi</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="print_qr.php" target="_blank" class="btn-gold text-xs px-4 py-1.5 inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
                    Cetak QR
                </a>
                <a href="../index.php" target="_blank" class="text-xs font-semibold px-4 py-1.5 rounded-full transition inline-flex items-center gap-1.5" style="border:1.5px solid var(--sky);color:var(--navy);background:rgba(147,178,248,.1)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"/><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"/><circle cx="18" cy="20" r="2"/><circle cx="8" cy="20" r="2"/></svg>
                    Halaman Pembeli
                </a>
                <a href="logout.php" class="text-xs font-semibold px-4 py-1.5 rounded-full text-white" style="background:#dc2626">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 mt-6">
        <?php if ($message): ?>
            <div class="p-4 mb-6 text-sm font-semibold rounded-xl" style="<?= $messageType === 'success' ? 'background:#dcfce7;color:#15803d;border:1px solid #86efac' : 'background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="card p-4">
                <p class="text-xs font-semibold" style="color:#888">Pendapatan Hari Ini</p>
                <h3 class="text-xl font-extrabold mt-1" style="color:#15803d">
                    Rp <?= number_format($statsToday['today_revenue'], 0, ',', '.') ?>
                </h3>
                <span class="text-[11px] mt-2 block" style="color:#aaa"><?= $statsToday['today_orders'] ?> terjual hari ini</span>
            </div>

            <div class="card p-4">
                <p class="text-xs font-semibold" style="color:#888">Total Pendapatan</p>
                <h3 class="text-xl font-extrabold mt-1" style="color:var(--navy)">
                    Rp <?= number_format($statsTotal['total_revenue'], 0, ',', '.') ?>
                </h3>
                <span class="text-[11px] mt-2 block" style="color:#aaa">Total <?= $statsTotal['total_orders'] ?> pesanan</span>
            </div>

            <div class="card p-4">
                <p class="text-xs font-semibold" style="color:#888">Status ESP32</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-sm font-bold" style="color:var(--navy)">Endpoint Aktif</span>
                </div>
                <span class="text-[11px] mt-2 block font-mono" style="color:#aaa">api/dispense_queue.php</span>
            </div>

            <div class="card p-4">
                <p class="text-xs font-semibold" style="color:#888">Mode Midtrans</p>
                <h3 class="text-base font-bold mt-1" style="color:<?= MIDTRANS_IS_PRODUCTION ? '#15803d' : '#d97706' ?>">
                    <?= MIDTRANS_IS_PRODUCTION ? 'PRODUCTION' : 'SANDBOX' ?>
                </h3>
                <span class="text-[11px] mt-2 block" style="color:#aaa">Auto Webhook Callback</span>
            </div>
        </div>

        <!-- Pengaturan 4 Slot Produk -->
        <h2 class="text-base font-extrabold mb-4" style="color:var(--navy)">Pengaturan 4 Slot Minuman</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
            <?php foreach ($products as $p): ?>
                <div class="card p-4 flex flex-col justify-between">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">

                        <div class="flex items-center justify-between mb-3">
                            <span class="badge-slot">Slot #<?= $p['slot_number'] ?></span>
                            <span class="text-xs font-semibold" style="color:<?= $p['stock'] > 0 ? '#15803d' : '#dc2626' ?>">
                                <?= $p['stock'] > 0 ? "Stok: {$p['stock']}" : 'Habis' ?>
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-[11px] font-semibold mb-1" style="color:#888">Nama Minuman</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold mb-1" style="color:#888">Harga (Rp)</label>
                                <input type="number" name="price" value="<?= $p['price'] ?>" required min="0" step="500" class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold mb-1" style="color:#888">Jumlah Stok</label>
                                <input type="number" name="stock" value="<?= $p['stock'] ?>" required min="0" class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold mb-1" style="color:#888">URL Gambar</label>
                                <input type="text" name="image_url" value="<?= htmlspecialchars($p['image_url']) ?>" class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                        </div>

                        <button type="submit" class="btn-navy w-full mt-4 py-2 text-xs font-bold">
                            Simpan Slot <?= $p['slot_number'] ?>
                        </button>
                    </form>

                    <!-- Test Dispense -->
                    <form method="POST" class="mt-2 pt-2" style="border-top:1px solid rgba(147,178,248,.25)">
                        <input type="hidden" name="action" value="test_dispense">
                        <input type="hidden" name="slot_number" value="<?= $p['slot_number'] ?>">
                        <button type="submit" onclick="return confirm('Uji coba putar servo Slot <?= $p['slot_number'] ?>?')"
                                class="w-full py-1.5 text-[11px] font-bold rounded-full transition" style="background:var(--butter);color:var(--navy);border:1px solid rgba(241,185,76,.5)">
                            Test Putar Servo
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Riwayat Transaksi -->
        <h2 class="text-base font-extrabold mb-4" style="color:var(--navy)">Riwayat Transaksi Terakhir</h2>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead style="background:rgba(147,178,248,.1);border-bottom:1px solid rgba(147,178,248,.3)">
                        <tr>
                            <th class="py-3 px-4 font-semibold" style="color:#888">Order ID</th>
                            <th class="py-3 px-4 font-semibold" style="color:#888">Produk</th>
                            <th class="py-3 px-4 font-semibold" style="color:#888">Slot</th>
                            <th class="py-3 px-4 font-semibold" style="color:#888">Nominal</th>
                            <th class="py-3 px-4 font-semibold" style="color:#888">Status Bayar</th>
                            <th class="py-3 px-4 font-semibold" style="color:#888">Status Dispenser</th>
                            <th class="py-3 px-4 font-semibold" style="color:#888">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="py-6 text-center" style="color:#bbb">Belum ada transaksi</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <tr class="transition" style="border-top:1px solid rgba(147,178,248,.15)" onmouseover="this.style.background='rgba(147,178,248,.07)'" onmouseout="this.style.background=''">
                                    <td class="py-3 px-4 font-mono" style="color:#555"><?= htmlspecialchars($o['order_id']) ?></td>
                                    <td class="py-3 px-4 font-semibold" style="color:var(--navy)"><?= htmlspecialchars($o['product_name'] ?? 'Uji Coba') ?></td>
                                    <td class="py-3 px-4">
                                        <span class="badge-slot">Slot <?= $o['slot_number'] ?></span>
                                    </td>
                                    <td class="py-3 px-4 font-bold" style="color:var(--gold)">Rp <?= number_format($o['amount'], 0, ',', '.') ?></td>
                                    <td class="py-3 px-4">
                                        <?php if ($o['payment_status'] === 'PAID'): ?>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#15803d;border:1px solid #86efac">PAID</span>
                                        <?php elseif ($o['payment_status'] === 'PENDING'): ?>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#fef9c3;color:#a16207;border:1px solid #fde047">PENDING</span>
                                        <?php else: ?>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5"><?= $o['payment_status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if ($o['dispense_status'] === 'COMPLETED'): ?>
                                            <span class="font-bold" style="color:#15803d">✓ Keluar</span>
                                        <?php elseif ($o['dispense_status'] === 'DISPENSING'): ?>
                                            <span class="font-bold animate-pulse" style="color:var(--navy)">⚙ Memutar Servo...</span>
                                        <?php elseif ($o['dispense_status'] === 'READY_TO_DISPENSE'): ?>
                                            <span class="font-bold" style="color:var(--gold)">Antre ke ESP32</span>
                                        <?php else: ?>
                                            <span style="color:#bbb"><?= $o['dispense_status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4" style="color:#aaa"><?= htmlspecialchars($o['created_at']) ?></td>
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
