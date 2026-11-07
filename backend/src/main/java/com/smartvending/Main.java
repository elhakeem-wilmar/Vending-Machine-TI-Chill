package com.smartvending;

import com.smartvending.controller.VendingMachine;
import com.smartvending.model.Inventory;
import com.smartvending.model.Product;
import com.smartvending.model.Transaction;

import java.util.List;

/**
 * Kelas utama untuk menjalankan demo Smart Vending Machine.
 * Mendemonstrasikan alur lengkap dari pembuatan inventaris, pemilihan produk,
 * pembayaran QRIS, hingga pengeluaran produk.
 */
public class Main {

    /**
     * Metode utama yang menjalankan simulasi mesin vending.
     *
     * @param args argumen baris perintah (tidak digunakan)
     */
    public static void main(String[] args) {

        // ============================================
        // 1. Membuat produk dan menambahkan ke inventaris
        // ============================================
        System.out.println(">> Inisialisasi Smart Vending Machine...\n");

        Product kopiKotak = new Product("MK01", "Kopi Kotak", 5000, 5, "Minuman", "kopi.png");
        Product susuCoklat = new Product("MK02", "Susu Coklat Kotak", 5000, 5, "Minuman", "susu-coklat.png");
        Product susuUHT = new Product("MK03", "Susu UHT Kotak", 5000, 5, "Minuman", "susu-uht.png");
        Product tehKotak = new Product("MK04", "Teh Kotak", 5000, 5, "Minuman", "teh.png");

        Inventory inventory = new Inventory();
        inventory.addProduct(kopiKotak);
        inventory.addProduct(susuCoklat);
        inventory.addProduct(susuUHT);
        inventory.addProduct(tehKotak);

        System.out.println(">> Produk tersedia:");
        for (Product p : inventory.getAllProducts()) {
            System.out.println("   " + p);
        }
        System.out.println();

        // ============================================
        // 2. Membuat mesin vending dengan inventaris
        // ============================================
        VendingMachine vendingMachine = new VendingMachine(inventory);
        System.out.println(">> Status Mesin: " + vendingMachine.getStatus());
        System.out.println();

        // ============================================
        // 3. Memulai sesi dengan scan QR code
        // ============================================
        vendingMachine.scanQRCodeToStart();
        System.out.println();

        // ============================================
        // 4. Memilih produk
        // ============================================
        vendingMachine.selectProduct("MK01", 1);  // 1x Kopi Kotak
        vendingMachine.selectProduct("MK04", 2);  // 2x Teh Kotak
        System.out.println();

        // ============================================
        // 5. Checkout - menghitung total
        // ============================================
        double total = vendingMachine.checkout();
        System.out.println();

        // ============================================
        // 6. Konfirmasi pembayaran via QRIS DANA
        // ============================================
        vendingMachine.confirmPayment();

        // ============================================
        // 7. Keluarkan produk
        // ============================================
        vendingMachine.dispenseProducts();

        // ============================================
        // 8. Tampilkan riwayat transaksi
        // ============================================
        System.out.println(">> RIWAYAT TRANSAKSI:");
        List<Transaction> history = vendingMachine.getTransactionHistory();
        for (Transaction trx : history) {
            System.out.println(trx);
        }
        System.out.println();

        // ============================================
        // 9. Cek peringatan stok rendah
        // ============================================
        System.out.println(">> PERINGATAN STOK RENDAH:");
        List<Product> lowStock = inventory.lowStockAlert();
        if (lowStock.isEmpty()) {
            System.out.println("   Semua stok aman.");
        } else {
            for (Product p : lowStock) {
                System.out.println("   [!] " + p);
            }
        }
    }
}
