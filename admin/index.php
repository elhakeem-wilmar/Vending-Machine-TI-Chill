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
    <title>Login Admin - Vending Machine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-slate-800 border border-slate-700/80 rounded-2xl p-6 w-full max-w-sm shadow-xl">
        <div class="text-center mb-6">
            <span class="text-3xl">🔐</span>
            <h1 class="text-xl font-bold text-white mt-2">Login Administrator</h1>
            <p class="text-xs text-slate-400">Smart Vending Machine</p>
        </div>

        <?php if ($message): ?>
            <div class="p-3 mb-4 text-xs font-semibold rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Username</label>
                <input type="text" name="username" required 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                <input type="password" name="password" required 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition">
            </div>
            <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-md shadow-blue-600/30 transition">
                Masuk ke Dashboard
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="../index.php" class="text-xs text-slate-400 hover:text-white transition">← Kembali ke Halaman Pembeli</a>
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
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen pb-12">
    <!-- Navbar -->
    <header class="bg-slate-800/80 backdrop-blur border-b border-slate-700/60 sticky top-0 z-40 px-6 py-3 shadow-md">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">🤖</span>
                <div>
                    <h1 class="text-lg font-bold text-white">Vending Admin Dashboard</h1>
                    <p class="text-xs text-slate-400">Kelola Produk, Stok, & Transaksi</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="print_qr.php" target="_blank" class="text-xs bg-blue-600/80 hover:bg-blue-600 px-3 py-1.5 rounded-lg text-white font-semibold transition">
                    🖨️ Cetak Stiker QR
                </a>
                <a href="../index.php" target="_blank" class="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded-lg border border-slate-600 text-slate-200 transition">
                    📱 Buka Halaman Pembeli
                </a>
                <a href="logout.php" class="text-xs bg-rose-600/80 hover:bg-rose-600 px-3 py-1.5 rounded-lg text-white font-semibold transition">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 mt-6">
        <?php if ($message): ?>
            <div class="p-4 mb-6 text-sm font-semibold rounded-2xl <?= $messageType === 'success' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-slate-800 border border-slate-700/80 rounded-2xl p-4">
                <p class="text-xs text-slate-400 font-medium">Pendapatan Hari Ini</p>
                <h3 class="text-xl font-extrabold text-emerald-400 mt-1">
                    Rp <?= number_format($statsToday['today_revenue'], 0, ',', '.') ?>
                </h3>
                <span class="text-[11px] text-slate-400 mt-2 block"><?= $statsToday['today_orders'] ?> botol/kaleng terjual hari ini</span>
            </div>

            <div class="bg-slate-800 border border-slate-700/80 rounded-2xl p-4">
                <p class="text-xs text-slate-400 font-medium">Total Pendapatan</p>
                <h3 class="text-xl font-extrabold text-blue-400 mt-1">
                    Rp <?= number_format($statsTotal['total_revenue'], 0, ',', '.') ?>
                </h3>
                <span class="text-[11px] text-slate-400 mt-2 block">Total <?= $statsTotal['total_orders'] ?> pesanan berhasil</span>
            </div>

            <div class="bg-slate-800 border border-slate-700/80 rounded-2xl p-4">
                <p class="text-xs text-slate-400 font-medium">Status ESP32 Polling</p>
                <div class="flex items-center space-x-2 mt-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-sm font-bold text-white">Endpoint Aktif</span>
                </div>
                <span class="text-[11px] text-slate-400 mt-2 block">api/dispense_queue.php</span>
            </div>

            <div class="bg-slate-800 border border-slate-700/80 rounded-2xl p-4">
                <p class="text-xs text-slate-400 font-medium">Mode Midtrans</p>
                <h3 class="text-base font-bold <?= MIDTRANS_IS_PRODUCTION ? 'text-emerald-400' : 'text-amber-400' ?> mt-1">
                    <?= MIDTRANS_IS_PRODUCTION ? 'PRODUCTION' : 'SANDBOX' ?>
                </h3>
                <span class="text-[11px] text-slate-400 mt-2 block">Auto Webhook Callback</span>
            </div>
        </div>

        <!-- Pengaturan 4 Slot Produk -->
        <h2 class="text-lg font-bold text-white mb-4 flex items-center space-x-2">
            <span>🥤</span>
            <span>Pengaturan 4 Slot Minuman</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
            <?php foreach ($products as $p): ?>
                <div class="bg-slate-800 border border-slate-700/80 rounded-2xl p-4 flex flex-col justify-between">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">

                        <div class="flex items-center justify-between mb-3">
                            <span class="bg-blue-600/30 text-blue-400 text-xs font-bold px-2.5 py-1 rounded-lg border border-blue-500/30">
                                Slot #<?= $p['slot_number'] ?>
                            </span>
                            <span class="text-xs <?= $p['stock'] > 0 ? 'text-emerald-400' : 'text-rose-400' ?> font-semibold">
                                <?= $p['stock'] > 0 ? "Stok: {$p['stock']}" : 'Habis' ?>
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400">Nama Minuman</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required
                                       class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-blue-500 mt-1">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400">Harga (Rp)</label>
                                <input type="number" name="price" value="<?= $p['price'] ?>" required min="0" step="500"
                                       class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-blue-500 mt-1">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400">Jumlah Stok</label>
                                <input type="number" name="stock" value="<?= $p['stock'] ?>" required min="0"
                                       class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-blue-500 mt-1">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400">URL Gambar</label>
                                <input type="text" name="image_url" value="<?= htmlspecialchars($p['image_url']) ?>"
                                       class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-blue-500 mt-1">
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-4 py-2 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
                            Simpan Slot <?= $p['slot_number'] ?>
                        </button>
                    </form>

                    <!-- Test Dispense Button -->
                    <form method="POST" class="mt-2 pt-2 border-t border-slate-700/60">
                        <input type="hidden" name="action" value="test_dispense">
                        <input type="hidden" name="slot_number" value="<?= $p['slot_number'] ?>">
                        <button type="submit" onclick="return confirm('Uji coba putar servo Slot <?= $p['slot_number'] ?>?')" 
                                class="w-full py-1.5 bg-slate-700 hover:bg-slate-600 text-amber-300 text-[11px] font-bold rounded-lg border border-amber-400/20 transition">
                            ⚡ Test Putar Servo
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Riwayat Transaksi -->
        <h2 class="text-lg font-bold text-white mb-4 flex items-center space-x-2">
            <span>📋</span>
            <span>Riwayat Transaksi Terakhir</span>
        </h2>

        <div class="bg-slate-800 border border-slate-700/80 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-700/80">
                        <tr>
                            <th class="py-3 px-4">Order ID</th>
                            <th class="py-3 px-4">Produk</th>
                            <th class="py-3 px-4">Slot</th>
                            <th class="py-3 px-4">Nominal</th>
                            <th class="py-3 px-4">Status Bayar</th>
                            <th class="py-3 px-4">Status Dispenser</th>
                            <th class="py-3 px-4">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-500">Belum ada transaksi</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="py-3 px-4 font-mono font-medium text-slate-300"><?= htmlspecialchars($o['order_id']) ?></td>
                                    <td class="py-3 px-4 font-semibold text-white"><?= htmlspecialchars($o['product_name'] ?? 'Uji Coba') ?></td>
                                    <td class="py-3 px-4">
                                        <span class="bg-slate-700 text-slate-200 px-2 py-0.5 rounded text-[10px] font-bold">Slot <?= $o['slot_number'] ?></span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-amber-400">Rp <?= number_format($o['amount'], 0, ',', '.') ?></td>
                                    <td class="py-3 px-4">
                                        <?php if ($o['payment_status'] === 'PAID'): ?>
                                            <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full text-[10px] font-bold">PAID</span>
                                        <?php elseif ($o['payment_status'] === 'PENDING'): ?>
                                            <span class="bg-amber-500/20 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded-full text-[10px] font-bold">PENDING</span>
                                        <?php else: ?>
                                            <span class="bg-rose-500/20 text-rose-400 border border-rose-500/30 px-2 py-0.5 rounded-full text-[10px] font-bold"><?= $o['payment_status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if ($o['dispense_status'] === 'COMPLETED'): ?>
                                            <span class="text-emerald-400 font-bold">✓ Keluar</span>
                                        <?php elseif ($o['dispense_status'] === 'DISPENSING'): ?>
                                            <span class="text-blue-400 font-bold animate-pulse">⚙ Memutar Servo...</span>
                                        <?php elseif ($o['dispense_status'] === 'READY_TO_DISPENSE'): ?>
                                            <span class="text-amber-400 font-bold">Antre ke ESP32</span>
                                        <?php else: ?>
                                            <span class="text-slate-500"><?= $o['dispense_status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-400"><?= htmlspecialchars($o['created_at']) ?></td>
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

