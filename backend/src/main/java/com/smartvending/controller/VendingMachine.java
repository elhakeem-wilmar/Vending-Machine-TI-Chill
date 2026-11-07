package com.smartvending.controller;

import com.smartvending.model.Inventory;
import com.smartvending.model.Payment;
import com.smartvending.model.Product;
import com.smartvending.model.QRISPayment;
import com.smartvending.model.Transaction;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.UUID;

/**
 * Controller utama yang mengatur seluruh alur kerja mesin vending.
 * Mengelola sesi pembelian mulai dari scan QR, pemilihan produk,
 * pembayaran, hingga pengeluaran produk.
 */
public class VendingMachine {

    /** Inventaris produk yang tersedia di mesin */
    private Inventory inventory;

    /** Transaksi yang sedang berjalan */
    private Transaction currentTransaction;

    /** Riwayat seluruh transaksi yang berhasil */
    private List<Transaction> transactionHistory;

    /** Status mesin vending (AKTIF, NONAKTIF, MAINTENANCE) */
    private String status;

    /**
     * Konstruktor untuk membuat mesin vending dengan inventaris tertentu.
     *
     * @param inventory inventaris produk awal
     */
    public VendingMachine(Inventory inventory) {
        this.inventory = inventory;
        this.transactionHistory = new ArrayList<>();
        this.status = "AKTIF";
    }

    /**
     * Memulai sesi pembelian baru dengan men-scan QR code.
     * Membuat transaksi baru dengan ID unik berbasis UUID.
     */
    public void scanQRCodeToStart() {
        String transactionId = "TRX-" + UUID.randomUUID().toString().substring(0, 8).toUpperCase();
        currentTransaction = new Transaction(transactionId);
        System.out.println("================================================");
        System.out.println(">> Sesi dimulai! ID Transaksi: " + transactionId);
        System.out.println("================================================");
    }

    /**
     * Memilih produk yang akan dibeli berdasarkan ID dan jumlah.
     * Melakukan validasi ketersediaan stok sebelum menambahkan ke transaksi.
     *
     * @param productId ID produk yang dipilih
     * @param qty       jumlah yang ingin dibeli
     */
    public void selectProduct(String productId, int qty) {
        if (currentTransaction == null) {
            System.out.println(">> Silakan scan QR code terlebih dahulu!");
            return;
        }

        Product product = inventory.getProductById(productId);
        if (product == null) {
            System.out.println(">> Produk dengan ID " + productId + " tidak ditemukan.");
            return;
        }

        int availableStock = inventory.checkStock(productId);
        if (availableStock < qty) {
            System.out.println(">> Stok tidak cukup untuk " + product.getName()
                    + ". Tersedia: " + availableStock);
            return;
        }

        currentTransaction.addItem(product, qty);
        System.out.println(">> Ditambahkan: " + product.getName() + " x" + qty);
    }

    /**
     * Melakukan checkout: menghitung total belanja dari seluruh item yang dipilih.
     *
     * @return total harga yang harus dibayar
     */
    public double checkout() {
        if (currentTransaction == null) {
            System.out.println(">> Tidak ada transaksi aktif.");
            return 0;
        }

        double total = currentTransaction.calculateTotal();
        System.out.println("------------------------------------------------");
        System.out.println(">> Total belanja: Rp" + String.format("%,.0f", total));
        System.out.println(">> Silakan lakukan pembayaran via QRIS (DANA).");
        System.out.println("------------------------------------------------");
        return total;
    }

    /**
     * Mengkonfirmasi pembayaran menggunakan QRIS DANA.
     * Jika pembayaran berhasil, stok akan dikurangi dan transaksi disimpan ke riwayat.
     */
    public void confirmPayment() {
        if (currentTransaction == null) {
            System.out.println(">> Tidak ada transaksi aktif.");
            return;
        }

        double total = currentTransaction.getTotalAmount();
        String paymentId = "PAY-" + UUID.randomUUID().toString().substring(0, 8).toUpperCase();

        QRISPayment qrisPayment = new QRISPayment(total, paymentId);
        System.out.println("\n>> QR Code untuk pembayaran:");
        System.out.println(">> " + qrisPayment.generateQRCode());
        System.out.println();

        // Proses dan validasi pembayaran
        qrisPayment.processPayment();

        if (qrisPayment.validatePayment()) {
            // Kurangi stok di inventaris
            for (Map.Entry<Product, Integer> entry : currentTransaction.getItems().entrySet()) {
                inventory.reduceStock(entry.getKey().getId(), entry.getValue());
            }

            // Simpan transaksi
            currentTransaction.createTransaction(qrisPayment);
            currentTransaction.saveHistory();
            transactionHistory.add(currentTransaction);

            System.out.println("\n>> Pembayaran berhasil! Produk akan segera dikeluarkan.");
        } else {
            System.out.println(">> Pembayaran gagal. Silakan coba lagi.");
        }
    }

    /**
     * Mengeluarkan produk yang sudah dibayar (simulasi).
     * Mencetak informasi setiap produk yang dikeluarkan dari mesin.
     */
    // shortcut: simulasi dispensing, integrasikan dengan Arduino Serial untuk produksi
    public void dispenseProducts() {
        if (currentTransaction == null) {
            System.out.println(">> Tidak ada produk untuk dikeluarkan.");
            return;
        }

        System.out.println("\n>> Mengeluarkan produk...");
        for (Map.Entry<Product, Integer> entry : currentTransaction.getItems().entrySet()) {
            Product product = entry.getKey();
            int qty = entry.getValue();
            for (int i = 1; i <= qty; i++) {
                System.out.println("   [DISPENSE] " + product.getName() + " (" + i + "/" + qty + ")");
            }
        }
        System.out.println(">> Semua produk telah dikeluarkan. Terima kasih!\n");

        // Reset transaksi saat ini
        currentTransaction = null;
    }

    /**
     * Mendapatkan riwayat seluruh transaksi yang berhasil.
     *
     * @return list riwayat transaksi
     */
    public List<Transaction> getTransactionHistory() {
        return transactionHistory;
    }

    /**
     * Mendapatkan status mesin vending saat ini.
     *
     * @return status mesin (AKTIF, NONAKTIF, MAINTENANCE)
     */
    public String getStatus() {
        return status;
    }
}
