<?php
// check.php - Alat Diagnostik Cepat untuk Memeriksa Penyebab Error 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔧 Diagnostik Sistem Vending Machine</h2>";

// 1. Cek file config.php
if (!file_exists(__DIR__ . '/config.php')) {
    die("<p style='color:red;'>❌ File config.php tidak ditemukan!</p>");
}
require_once __DIR__ . '/config.php';
echo "<p style='color:green;'>✔️ File config.php berhasil dimuat.</p>";

// 2. Cek Koneksi PDO
if (isset($pdo)) {
    echo "<p style='color:green;'>✔️ Koneksi ke database MySQL <b>" . htmlspecialchars(DB_NAME) . "</b> BERHASIL!</p>";
} else {
    die("<p style='color:red;'>❌ Gagal menghubungkan ke PDO.</p>");
}

// 3. Cek Tabel Products
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        echo "<p style='color:green;'>✔️ Tabel <b>products</b> ditemukan. Berisi: <b>" . $count . " minuman</b>.</p>";
        echo "<h3 style='color:blue;'>🎉 Database Siap! Masalah 500 kemungkinan berasal dari file lain atau ekstensi.</h3>";
        echo "<p><a href='index.php'>Klik di sini untuk membuka index.php</a></p>";
    } else {
        echo "<p style='color:red; font-size:16px;'><b>❌ Tabel 'products' BELUM ADA di database!</b></p>";
        echo "<p>Penyebab error 500 adalah tabel belum di-import. Buka <b>phpMyAdmin</b> di cPanel ➔ pilih database <b>" . htmlspecialchars(DB_NAME) . "</b> ➔ klik tab <b>Import</b> ➔ pilih file <b>database.sql</b>.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Terjadi error saat query: " . htmlspecialchars($e->getMessage()) . "</p>";
}
