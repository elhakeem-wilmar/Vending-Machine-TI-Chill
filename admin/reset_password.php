<?php
// admin/reset_password.php - Reset Password Akun Admin
require_once __DIR__ . '/../config.php';

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    // Cek apakah user admin sudah ada
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $update->execute([$hash, $username]);
        echo "<h2 style='color:green;'>✔️ Berhasil! Password admin telah di-reset:</h2>";
    } else {
        $insert = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $insert->execute([$username, $hash]);
        echo "<h2 style='color:green;'>✔️ Berhasil! Akun admin baru telah dibuat:</h2>";
    }

    echo "<p><b>Username:</b> <code>admin</code></p>";
    echo "<p><b>Password:</b> <code>admin123</code></p>";
    echo "<p><a href='index.php' style='display:inline-block; padding:10px 16px; background:#2563eb; color:white; text-decoration:none; border-radius:8px;'>Klik di sini untuk Login ke Dashboard</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Gagal reset: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
