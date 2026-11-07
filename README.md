# Smart Vending Machine 4 Minuman (PHP + Midtrans + ESP32)

Sistem vending machine sederhana berbasis web mobile (scan QR untuk pesan di HP pembeli), backend PHP PDO MySQL, gateway pembayaran Midtrans (Sandbox), dan controller perangkat keras Arduino ESP32 Dev Module dengan 4 motor servo.

---

## 📁 Struktur Direktori

```text
├── .htaccess                   # Proteksi database.sql, security headers Apache cPanel
├── config.php                  # Konfigurasi Database, Midtrans, dan API Key Mesin
├── database.sql                # Skema database MySQL & data awal produk
├── index.php                   # Halaman pemesanan pembeli (Mobile-First responsive)
├── README.md                   # Petunjuk setup & pemasangan
├── admin/
│   ├── index.php               # Dashboard admin (kelola produk, stok, harga & riwayat transaksi)
│   ├── logout.php              # Logout sesi admin
│   └── print_qr.php            # Halaman cetak stiker fisik QR Code untuk ditempel di mesin
├── api/
│   ├── create_order.php        # Endpoint pembuatan order & request Snap Token
│   ├── midtrans_webhook.php    # Callback/Webhook verifikasi pembayaran otomatis dari Midtrans
│   ├── check_status.php        # Polling status pesanan oleh HP pembeli
│   ├── dispense_queue.php      # Polling antrean pengeluaran minuman oleh ESP32
│   └── dispense_confirm.php    # Konfirmasi servo selesai dari ESP32
└── esp32/
    └── vending_machine.ino     # Kode Arduino sketch untuk ESP32 Dev Module
```

---

## 🚀 Panduan Hosting di AnymHost (cPanel)

1. **Buat Database MySQL di cPanel:**
   - Masuk ke cPanel AnymHost -> **MySQL Database Wizard**.
   - Buat database baru (contoh: `u12345_vending`).
   - Buat user database dan beri akses **ALL PRIVILEGES**.
   - Buka **phpMyAdmin**, pilih database tersebut, lalu klik tab **Import** dan pilih file [`database.sql`](file:///d:/New%20Vending%20Machine/database.sql).

2. **Upload Berkas Web:**
   - Buka **File Manager** cPanel -> masuk ke `public_html` (atau subdomain Anda).
   - Unggah semua file proyek ini (`config.php`, `index.php`, folder `admin/`, folder `api/`).

3. **Sesuaikan `config.php`:**
   - Edit file [`config.php`](file:///d:/New%20Vending%20Machine/config.php):
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'u12345_vending');
     define('DB_USER', 'u12345_user');
     define('DB_PASS', 'PasswordDatabaseAnda');

     define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-xxxx');
     define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-xxxx');
     define('BASE_URL', 'https://domainanda.com');
     ```

4. **Konfigurasi Webhook di Midtrans Dashboard:**
   - Masuk ke portal Midtrans Sandbox: [dashboard.midtrans.com](https://dashboard.midtrans.com).
   - Buka **Settings** -> **Configuration**.
   - Pada kolom **Payment Notification URL**, isi:
     `https://domainanda.com/api/midtrans_webhook.php`
   - Klik **Save Changes**.

---

## 🔐 Akun Default Admin

- **URL Dashboard:** `https://domainanda.com/admin/index.php`
- **Username:** `admin`
- **Password:** `admin123`
*(Disarankan ganti password setelah berhasil login).*

---

## ⚡ Panduan Rangkaian Hardware ESP32 & Servo

### Pinout ESP32:
| Komponen | Pin ESP32 | Keterangan |
| :--- | :--- | :--- |
| **Servo Slot 1** | **GPIO 13** | Sinyal PWM (kabel oranye/kuning) |
| **Servo Slot 2** | **GPIO 12** | Sinyal PWM (kabel oranye/kuning) |
| **Servo Slot 3** | **GPIO 14** | Sinyal PWM (kabel oranye/kuning) |
| **Servo Slot 4** | **GPIO 27** | Sinyal PWM (kabel oranye/kuning) |
| **LED Indikator**| **GPIO 2**  | Built-in LED pada board ESP32 |

> [!CAUTION]
> **Penting untuk Daya Servo:**
> Jangan memberi daya 4 buah servo langsung dari pin 5V/VIN atau 3.3V ESP32!
> Gunakan **Power Supply eksternal 5V (2A - 3A)** khusus untuk kabel merah (VCC) dan hitam/coklat (GND) servo.
> **Wajib menghubungkan GND Power Supply Eksternal ke GND ESP32 (Common Ground).**

---

## 🛠️ Upload Kode ESP32 (Arduino IDE)

1. Buka Arduino IDE.
2. Install board ESP32 via Boards Manager: `esp32 by Espressif Systems`.
3. Buka **Tools** -> **Manage Libraries...**, lalu install:
   - **ESP32Servo** (oleh Kevin Harrington)
   - **ArduinoJson** (oleh Benoit Blanchon)
4. Buka file [`esp32/vending_machine.ino`](file:///d:/New%20Vending%20Machine/esp32/vending_machine.ino).
5. Ubah variabel:
   ```cpp
   const char* WIFI_SSID     = "Nama_WiFi_Anda";
   const char* WIFI_PASSWORD = "Password_WiFi_Anda";
   const String SERVER_BASE_URL = "https://domainanda.com";
   const String MACHINE_API_KEY = "VM_SECRET_TOKEN_2026_XYZ";
   ```
6. Pilih board `ESP32 Dev Module`, tancapkan kabel USB, dan klik **Upload**.
7. Buka **Serial Monitor** pada baudrate `115200` untuk memantau status koneksi dan pergerakan servo.

