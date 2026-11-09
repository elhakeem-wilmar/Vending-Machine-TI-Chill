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
            $message = "Data produk Slot {$id} berhasil disimpan.";
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
            $message = "Uji putar Slot {$slotNumber} berhasil dikirim ke antrean mesin.";
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
    <title>Login Administrator - Vending Machine TI-Chill</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0a1680; --sky:#93b2f8; --gold:#f1b94c; --butter:#fbedb0; --canvas:#fcfdff; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--canvas); color:var(--navy); }
        button, a, [role="button"], input[type="submit"] { cursor:pointer; }
        :focus-visible { outline:2px solid var(--navy); outline-offset:2px; }
        .input-field { background:#fff; border:1px solid #cbd5e1; border-radius:.5rem; color:var(--navy); transition:border-color .15s; }
        .input-field:focus { border-color:var(--navy); }
        .btn-navy { background:var(--navy); color:#fff; border-radius:.5rem; border-bottom:2px solid #050b4c; transition:background .15s; }
        .btn-navy:hover { background:#1424a8; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <main class="w-full max-w-sm rounded-xl p-7" style="background:#fff;border:1px solid #dbe4f8;box-shadow:0 4px 14px rgba(10,22,128,.05)">
        <div class="text-center mb-6">
            <img src="../assets/logo.png" alt="Logo Kelas TI-Chill" width="48" height="48" fetchpriority="high" class="h-12 w-12 mx-auto object-contain mb-2.5">
            <h1 class="text-lg font-extrabold" style="color:var(--navy)">Login Administrator</h1>
            <p class="text-xs mt-0.5" style="color:#475569">Vending Machine TI-Chill</p>
        </div>

        <?php if ($message): ?>
            <div role="alert" class="p-3 mb-4 text-xs font-semibold rounded-lg" style="background:#fee2e2;color:#7f1d1d;border:1px solid #fca5a5">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <div>
                <label for="login_username" class="block text-xs font-semibold mb-1" style="color:#334155">Username</label>
                <input id="login_username" type="text" name="username" autocomplete="username" required class="input-field w-full px-3 py-2 text-sm">
            </div>
            <div>
                <label for="login_password" class="block text-xs font-semibold mb-1" style="color:#334155">Password</label>
                <input id="login_password" type="password" name="password" autocomplete="current-password" required class="input-field w-full px-3 py-2 text-sm">
            </div>
            <button type="submit" class="btn-navy w-full py-2.5 text-sm font-bold">
                Masuk
            </button>
        </form>
        <div class="mt-5 text-center">
            <a href="../index.php" class="text-xs font-semibold hover:underline" style="color:var(--navy)">&larr; Kembali ke Halaman Pembeli</a>
        </div>
    </main>
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
    <title>Admin Dashboard - Vending Machine TI-Chill</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0a1680; --sky:#93b2f8; --gold:#f1b94c; --butter:#fbedb0; --canvas:#fcfdff; --well:#f2f6ff; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--canvas); color:var(--navy); }
        button, a, [role="button"], input[type="submit"] { cursor:pointer; }
        :focus-visible { outline:2px solid var(--navy); outline-offset:2px; }
        .num-tabular { font-variant-numeric:tabular-nums; }
        .card { background:#fff; border:1px solid #dbe4f8; border-radius:.875rem; box-shadow:0 2px 6px rgba(10,22,128,.04); }
        .input-field { background:#fff; border:1px solid #cbd5e1; border-radius:.5rem; color:var(--navy); transition:border-color .15s; }
        .input-field:focus { border-color:var(--navy); }
        .btn-navy { background:var(--navy); color:#fff; border-radius:.5rem; border-bottom:2px solid #050b4c; transition:background .15s; }
        .btn-navy:hover { background:#1424a8; }
        .btn-gold { background:var(--gold); color:var(--navy); border:1.5px solid var(--gold); border-radius:.5rem; font-weight:700; transition:filter .15s; }
        .btn-gold:hover { filter:brightness(.95); }
        .btn-secondary { background:var(--well); color:var(--navy); border:1px solid #c7d7fe; border-radius:.5rem; font-weight:600; transition:background .15s; }
        .btn-secondary:hover { background:#e4edff; }
        .btn-logout { background:#fff; color:#991b1b; border:1px solid #fecaca; border-radius:.5rem; font-weight:600; transition:background .15s; }
        .btn-logout:hover { background:#fef2f2; }
        .btn-wa { background:var(--gold); color:var(--navy); border:1.5px solid var(--gold); border-radius:.5rem; transition:filter .15s; }
        .btn-wa:hover { filter:brightness(.95); }
        .btn-ig { background:rgba(252,253,255,.1); color:var(--canvas); border:1.5px solid rgba(147,178,248,.5); border-radius:.5rem; transition:background .15s, border-color .15s; }
        .btn-ig:hover { background:rgba(252,253,255,.18); border-color:var(--sky); }
        .badge-slot { background:var(--butter); color:var(--navy); border:1px solid var(--gold); border-radius:.375rem; font-weight:800; font-size:.7rem; padding:.15rem .5rem; display:inline-block; white-space:nowrap; font-variant-numeric:tabular-nums; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration:0.01ms !important; animation-iteration-count:1 !important; transition-duration:0.01ms !important; scroll-behavior:auto !important; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">
    <div>
    <!-- Navbar -->
    <header class="sticky top-0 z-40 px-4 sm:px-6 py-3" style="background:#fff;border-top:3px solid var(--navy);border-bottom:1px solid #dbe4f8">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-2.5 sm:gap-3">
            <div class="flex items-center gap-2.5">
                <img src="../assets/logo.png" alt="Logo Kelas TI-Chill" width="36" height="36" fetchpriority="high" class="h-9 w-9 object-contain shrink-0">
                <div>
                    <h1 class="text-base font-extrabold leading-tight" style="color:var(--navy)">Admin TI-Chill</h1>
                    <p class="text-[11px]" style="color:#475569">Kelola stok, harga &amp; riwayat transaksi</p>
                </div>
            </div>
            <div class="w-full sm:w-auto flex items-center justify-between gap-1.5 sm:gap-2">
                <a href="print_qr.php" target="_blank" class="btn-gold flex-1 sm:flex-initial text-[11px] sm:text-xs px-2 sm:px-3.5 py-1.5 inline-flex items-center justify-center gap-1.5 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
                    Cetak QR
                </a>
                <a href="../index.php" target="_blank" class="btn-secondary flex-1 sm:flex-initial text-[11px] sm:text-xs px-2 sm:px-3.5 py-1.5 inline-flex items-center justify-center gap-1.5 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"/><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"/><circle cx="18" cy="20" r="2"/><circle cx="8" cy="20" r="2"/></svg>
                    Halaman Pembeli
                </a>
                <a href="logout.php" class="btn-logout flex-1 sm:flex-initial text-[11px] sm:text-xs px-2 sm:px-3.5 py-1.5 inline-flex items-center justify-center whitespace-nowrap">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 mt-6">
        <?php if ($message): ?>
            <div role="status" class="p-3.5 mb-6 text-xs font-semibold rounded-lg" style="<?= $messageType === 'success' ? 'background:#dcfce7;color:#14532d;border:1px solid #86efac' : 'background:#fee2e2;color:#7f1d1d;border:1px solid #fca5a5' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Ringkasan Data Nyata (2 kolom berdampingan di mobile, 3 kolom di desktop) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-8">
            <div class="card p-3.5 sm:p-4">
                <p class="text-[11px] sm:text-xs font-semibold" style="color:#475569">Penjualan Hari Ini</p>
                <p class="num-tabular text-lg sm:text-2xl font-extrabold mt-1 tracking-tight" style="color:var(--navy)">
                    Rp <?= number_format($statsToday['today_revenue'], 0, ',', '.') ?>
                </p>
                <span class="num-tabular text-[11px] mt-1 block" style="color:#475569"><?= (int)$statsToday['today_orders'] ?> transaksi lunas</span>
            </div>

            <div class="card p-3.5 sm:p-4">
                <p class="text-[11px] sm:text-xs font-semibold" style="color:#475569">Total Akumulasi</p>
                <p class="num-tabular text-lg sm:text-2xl font-extrabold mt-1 tracking-tight" style="color:var(--navy)">
                    Rp <?= number_format($statsTotal['total_revenue'], 0, ',', '.') ?>
                </p>
                <span class="num-tabular text-[11px] mt-1 block" style="color:#475569"><?= (int)$statsTotal['total_orders'] ?> total transaksi lunas</span>
            </div>

            <div class="card p-3.5 sm:p-4 col-span-2 sm:col-span-1 flex sm:block items-center justify-between gap-2">
                <div>
                    <p class="text-[11px] sm:text-xs font-semibold" style="color:#475569">Mode Pembayaran</p>
                    <span class="text-[11px] mt-1 hidden sm:block" style="color:#475569">QRIS, E-Wallet &amp; Virtual Account</span>
                </div>
                <div class="sm:mt-2">
                    <span class="text-xs font-extrabold px-2.5 py-1 rounded-md inline-block" style="background:var(--butter);color:var(--navy);border:1px solid var(--gold)">
                        Midtrans <?= MIDTRANS_IS_PRODUCTION ? 'Production' : 'Sandbox' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Pengaturan 4 Slot Produk -->
        <h2 class="text-base font-extrabold mb-3" style="color:var(--navy)">Pengaturan 4 Slot Minuman</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
            <?php foreach ($products as $p): ?>
                <?php
                $slotCode = str_pad((string)$p['slot_number'], 2, '0', STR_PAD_LEFT);
                if ($p['stock'] <= 0) {
                    $stockBadgeStyle = 'background:#fee2e2;color:#7f1d1d;border:1px solid #fca5a5';
                    $stockText = 'Habis';
                } elseif ($p['stock'] <= 3) {
                    $stockBadgeStyle = 'background:var(--butter);color:#7c2d12;border:1px solid var(--gold)';
                    $stockText = 'Sisa ' . (int)$p['stock'];
                } else {
                    $stockBadgeStyle = 'background:#dcfce7;color:#14532d;border:1px solid #86efac';
                    $stockText = 'Stok ' . (int)$p['stock'];
                }
                ?>
                <div class="card p-4 flex flex-col justify-between">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                        <!-- Header Slot + Preview Gambar -->
                        <div class="flex items-center gap-3 mb-3.5 p-2.5 rounded-lg" style="background:var(--well)">
                            <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://placehold.co/120x120/fbedb0/0a1680?text=S' . $p['slot_number']) ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 width="44" height="44" loading="lazy"
                                 class="w-11 h-11 rounded-md object-contain p-1 shrink-0" style="background:#fff;border:1px solid #dbe4f8">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1.5">
                                    <span class="badge-slot">Slot <?= $slotCode ?></span>
                                    <span class="num-tabular text-[11px] font-bold px-2 py-0.5 rounded" style="<?= $stockBadgeStyle ?>">
                                        <?= htmlspecialchars($stockText) ?>
                                    </span>
                                </div>
                                <p class="text-xs font-bold truncate mt-1" style="color:var(--navy)"><?= htmlspecialchars($p['name']) ?></p>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <div>
                                <label for="name_<?= (int)$p['id'] ?>" class="block text-[11px] font-semibold mb-1" style="color:#334155">Nama Minuman</label>
                                <input id="name_<?= (int)$p['id'] ?>" type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label for="price_<?= (int)$p['id'] ?>" class="block text-[11px] font-semibold mb-1" style="color:#334155">Harga (Rp)</label>
                                    <input id="price_<?= (int)$p['id'] ?>" type="number" name="price" value="<?= (int)$p['price'] ?>" required min="0" step="500" class="input-field num-tabular w-full px-2.5 py-1.5 text-xs">
                                </div>
                                <div>
                                    <label for="stock_<?= (int)$p['id'] ?>" class="block text-[11px] font-semibold mb-1" style="color:#334155">Jumlah Stok</label>
                                    <input id="stock_<?= (int)$p['id'] ?>" type="number" name="stock" value="<?= (int)$p['stock'] ?>" required min="0" class="input-field num-tabular w-full px-2.5 py-1.5 text-xs">
                                </div>
                            </div>
                            <div>
                                <label for="img_<?= (int)$p['id'] ?>" class="block text-[11px] font-semibold mb-1" style="color:#334155">URL Gambar</label>
                                <input id="img_<?= (int)$p['id'] ?>" type="text" name="image_url" value="<?= htmlspecialchars($p['image_url']) ?>" class="input-field w-full px-2.5 py-1.5 text-xs">
                            </div>
                        </div>

                        <button type="submit" class="btn-navy w-full mt-3.5 py-2 text-xs font-bold">
                            Simpan Slot <?= $slotCode ?>
                        </button>
                    </form>

                    <!-- Test Dispense -->
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="action" value="test_dispense">
                        <input type="hidden" name="slot_number" value="<?= (int)$p['slot_number'] ?>">
                        <button type="submit" onclick="return confirm('Uji coba putar servo Slot <?= (int)$p['slot_number'] ?>?')"
                                class="btn-secondary w-full py-1.5 text-[11px] font-bold">
                            Tes Putar Servo
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Riwayat Transaksi -->
        <?php
        function renderPayBadge($status) {
            if ($status === 'PAID') {
                return '<span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap" style="background:#dcfce7;color:#14532d;border:1px solid #86efac">LUNAS</span>';
            }
            if ($status === 'PENDING') {
                return '<span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap" style="background:var(--butter);color:#7c2d12;border:1px solid var(--gold)">MENUNGGU</span>';
            }
            $label = ($status === 'EXPIRED') ? 'KEDALUWARSA' : 'BATAL';
            return '<span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap" style="background:#fee2e2;color:#7f1d1d;border:1px solid #fca5a5">' . $label . '</span>';
        }

        function renderDispenseText($status) {
            if ($status === 'COMPLETED') return '<span class="font-bold" style="color:#14532d">Selesai</span>';
            if ($status === 'DISPENSING') return '<span class="font-bold" style="color:var(--navy)">Memutar Motor...</span>';
            if ($status === 'READY_TO_DISPENSE') return '<span class="font-bold" style="color:#9a3412">Dalam Antrean</span>';
            if ($status === 'WAITING_PAYMENT') return '<span style="color:#475569">Menunggu Bayar</span>';
            return '<span style="color:#475569">Dibatalkan</span>';
        }
        ?>
        <h2 class="text-base font-extrabold mb-3" style="color:var(--navy)">Riwayat Transaksi Terakhir</h2>

        <div class="card overflow-hidden">
            <!-- Tampilan Kartu Ringkas (Khusus Mobile) -->
            <div class="md:hidden">
                <?php if (empty($orders)): ?>
                    <p class="py-6 text-center text-xs" style="color:#475569">Belum ada transaksi tercatat</p>
                <?php else: ?>
                    <?php foreach ($orders as $idx => $o): ?>
                        <?php $oSlot = str_pad((string)$o['slot_number'], 2, '0', STR_PAD_LEFT); ?>
                        <div class="p-3.5 space-y-1.5" <?= $idx > 0 ? 'style="border-top:1px solid #dbe4f8"' : '' ?>>
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="badge-slot shrink-0">Slot <?= $oSlot ?></span>
                                    <span class="font-bold text-xs truncate" style="color:var(--navy)"><?= htmlspecialchars($o['product_name'] ?? 'Uji Putar Manual') ?></span>
                                </div>
                                <?= renderPayBadge($o['payment_status']) ?>
                            </div>
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="num-tabular font-extrabold" style="color:var(--navy)">Rp <?= number_format($o['amount'], 0, ',', '.') ?></span>
                                <?= renderDispenseText($o['dispense_status']) ?>
                            </div>
                            <div class="flex items-center justify-between gap-2 text-[11px]" style="color:#475569">
                                <span class="font-mono truncate"><?= htmlspecialchars($o['order_id']) ?></span>
                                <span class="num-tabular shrink-0"><?= htmlspecialchars($o['created_at']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tampilan Tabel (Desktop & Tablet) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead style="background:var(--well);border-bottom:1px solid #dbe4f8">
                        <tr>
                            <th class="py-3 px-4 font-bold whitespace-nowrap" style="color:var(--navy)">Order ID</th>
                            <th class="py-3 px-4 font-bold whitespace-nowrap" style="color:var(--navy)">Produk</th>
                            <th class="py-3 px-4 font-bold whitespace-nowrap" style="color:var(--navy)">Slot</th>
                            <th class="py-3 px-4 font-bold whitespace-nowrap" style="color:var(--navy)">Nominal</th>
                            <th class="py-3 px-4 font-bold whitespace-nowrap" style="color:var(--navy)">Status Bayar</th>
                            <th class="py-3 px-4 font-bold whitespace-nowrap" style="color:var(--navy)">Status Dispenser</th>
                            <th class="py-3 px-4 font-bold whitespace-nowrap" style="color:var(--navy)">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="py-6 text-center" style="color:#475569">Belum ada transaksi tercatat</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <?php $oSlot = str_pad((string)$o['slot_number'], 2, '0', STR_PAD_LEFT); ?>
                                <tr style="border-top:1px solid #e2e8f0">
                                    <td class="py-3 px-4 font-mono whitespace-nowrap" style="color:#334155"><?= htmlspecialchars($o['order_id']) ?></td>
                                    <td class="py-3 px-4 font-semibold" style="color:var(--navy)"><?= htmlspecialchars($o['product_name'] ?? 'Uji Putar Manual') ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="badge-slot">Slot <?= $oSlot ?></span>
                                    </td>
                                    <td class="num-tabular py-3 px-4 font-bold whitespace-nowrap" style="color:var(--navy)">Rp <?= number_format($o['amount'], 0, ',', '.') ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap"><?= renderPayBadge($o['payment_status']) ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap"><?= renderDispenseText($o['dispense_status']) ?></td>
                                    <td class="num-tabular py-3 px-4 whitespace-nowrap" style="color:#475569"><?= htmlspecialchars($o['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    </div>

    <footer class="w-full mt-14" style="background:var(--navy);color:var(--canvas);border-top:3px solid var(--gold)">
        <div class="max-w-6xl mx-auto px-4 py-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-5" style="border-bottom:1px solid rgba(147,178,248,.25)">
                <div class="flex items-center gap-3">
                    <img src="../assets/logo.png" alt="Logo Kelas TI-Chill" width="48" height="48" loading="lazy" class="h-12 w-12 object-contain rounded-xl p-1.5 shrink-0" style="background:var(--canvas)">
                    <span class="text-base sm:text-lg font-extrabold tracking-tight" style="color:var(--canvas)">TI-Chill</span>
                </div>

                <div class="w-full sm:w-auto grid grid-cols-2 sm:flex items-center gap-2.5">
                    <a href="https://wa.me/6281266906714" target="_blank" rel="noopener noreferrer" class="btn-wa text-xs font-bold px-3.5 py-2.5 inline-flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/></svg>
                        <span>+62 812-6690-6714</span>
                    </a>
                    <a href="https://www.instagram.com/ti.chill._?vrfl=bnZqMWNlcXlmZDJ5" target="_blank" rel="noopener noreferrer" class="btn-ig text-xs font-bold px-3.5 py-2.5 inline-flex items-center justify-center gap-2 whitespace-nowrap">
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
</body>
</html>
