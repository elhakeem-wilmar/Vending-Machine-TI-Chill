# Smart Vending Machine System 🥤

Aplikasi Smart Vending Machine berbasis OOP Java (backend logic) dan Web (frontend interaktif).

## Struktur Proyek

```
smart-vending-machine/
├── backend/
│   └── src/main/java/com/smartvending/
│       ├── Main.java                        # Demo simulasi alur lengkap
│       ├── model/
│       │   ├── Product.java                 # Model produk
│       │   ├── Inventory.java               # Manajemen inventaris
│       │   ├── Payment.java                 # Kelas abstrak pembayaran
│       │   ├── QRISPayment.java             # Pembayaran QRIS/DANA
│       │   └── Transaction.java             # Data transaksi
│       └── controller/
│           └── VendingMachine.java           # Controller utama mesin
├── frontend/
│   └── index.html                           # Antarmuka web (langsung buka di browser)
└── README.md
```

## Cara Menjalankan

### Web + Midtrans Server (Node.js)
1. Salin file `.env.example` menjadi `.env` lalu masukkan Server Key & Client Key dari Midtrans Sandbox:
   ```bash
   # Di file .env:
   MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
   MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
   ```
2. Jalankan server:
   ```bash
   npm start
   ```
3. Buka browser:
   - **Web Pelanggan (Kiosk):** `http://localhost:3000`
   - **Dashboard Admin:** `http://localhost:3000/admin` (Default PIN: `1234`)

### Fitur Dashboard Admin
- **Login PIN Aman:** Proteksi akses admin dengan keypad PIN 4-digit (konfigurasi via `ADMIN_PIN` di `.env`).
- **Restock Stok Minuman:** Tambah atau kurangi stok per produk secara cepat (+1, +5, +10).
- **Ubah Harga Jual:** Update harga satuan minuman secara langsung tanpa restart server.
- **Log Riwayat Transaksi Lengkap:** Hanya dapat diakses oleh admin (disembunyikan dari web pembeli).
- **Statistik Penjualan:** Ringkasan Omzet, Total Minuman Terjual, Transaksi Lunas, dan Peringatan Stok Kritis.

### Beralih ke Mode Production (Setelah Aktivasi Midtrans Selesai)
Cukup ubah di file `.env`:
```env
IS_PRODUCTION=true
MIDTRANS_SERVER_KEY=Mid-server-xxxx
MIDTRANS_CLIENT_KEY=Mid-client-xxxx
```
Lalu restart server (`npm start`). Frontend akan otomatis beralih ke script Midtrans Production tanpa ubah kode!

### Backend Simulasi (Java OOP)
```bash
cd backend/src/main/java
javac com/smartvending/model/*.java com/smartvending/controller/*.java com/smartvending/Main.java
java com.smartvending.Main
```
> Membutuhkan JDK 11+ terinstal.

## Alur Transaksi Midtrans
1. **Pilih Minuman** → Tambah item ke keranjang.
2. **Buat Pesanan** → Backend me-request `snap_token` ke API Midtrans.
3. **Pop-up Midtrans Snap** → Pilih QRIS / GoPay / ShopeePay / Transfer Bank.
4. **Bayar via Simulator** → Uji bayar menggunakan [Midtrans QRIS Simulator](https://simulator.sandbox.midtrans.com/qris/index).
5. **Dispensing Otomatis** → Barang keluar animasi mesin, stok terpotong, riwayat tercatat.

## Integrasi Lanjutan (Placeholder)

- **Payment Gateway:** Lihat `QRISPayment.java` — ganti simulasi dengan DANA API
- **Arduino Dispensing:** Lihat `VendingMachine.java` `dispenseProducts()` — integrasikan Serial/WebSocket
