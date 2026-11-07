package com.smartvending.model;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.LinkedHashMap;
import java.util.Map;

/**
 * Kelas yang merepresentasikan satu transaksi pembelian pada mesin vending.
 * Menyimpan daftar item yang dibeli, total harga, informasi pembayaran, dan waktu transaksi.
 */
public class Transaction {

    /** ID unik transaksi */
    private String transactionId;

    /** Daftar item (produk dan jumlah) dalam transaksi ini */
    private Map<Product, Integer> items;

    /** Total harga seluruh item */
    private double totalAmount;

    /** Objek pembayaran yang terkait dengan transaksi */
    private Payment payment;

    /** Waktu transaksi dibuat */
    private LocalDateTime timestamp;

    /**
     * Konstruktor untuk membuat transaksi baru.
     * Inisialisasi daftar item kosong dan total 0.
     *
     * @param transactionId ID unik transaksi
     */
    public Transaction(String transactionId) {
        this.transactionId = transactionId;
        this.items = new LinkedHashMap<>();
        this.totalAmount = 0;
    }

    /**
     * Menambahkan item (produk dan jumlah) ke dalam transaksi.
     * Jika produk sudah ada, jumlahnya akan ditambahkan.
     *
     * @param product produk yang ditambahkan
     * @param qty     jumlah produk
     */
    public void addItem(Product product, int qty) {
        items.merge(product, qty, Integer::sum);
    }

    /**
     * Menghitung total harga seluruh item dalam transaksi.
     *
     * @return total harga dalam Rupiah
     */
    public double calculateTotal() {
        totalAmount = 0;
        for (Map.Entry<Product, Integer> entry : items.entrySet()) {
            totalAmount += entry.getKey().getPrice() * entry.getValue();
        }
        return totalAmount;
    }

    /**
     * Membuat transaksi final: menetapkan pembayaran, menghitung total, dan mencatat waktu.
     *
     * @param payment objek pembayaran yang digunakan
     */
    public void createTransaction(Payment payment) {
        this.payment = payment;
        calculateTotal();
        this.timestamp = LocalDateTime.now();
    }

    /**
     * Menyimpan dan mencetak struk transaksi ke konsol.
     */
    public void saveHistory() {
        System.out.println(toString());
    }

    // === Getter dan Setter ===

    public String getTransactionId() {
        return transactionId;
    }

    public Map<Product, Integer> getItems() {
        return items;
    }

    public double getTotalAmount() {
        return totalAmount;
    }

    public Payment getPayment() {
        return payment;
    }

    public LocalDateTime getTimestamp() {
        return timestamp;
    }

    /**
     * Format struk transaksi lengkap untuk ditampilkan.
     *
     * @return string struk transaksi
     */
    @Override
    public String toString() {
        StringBuilder sb = new StringBuilder();
        sb.append("=".repeat(45)).append("\n");
        sb.append("       STRUK SMART VENDING MACHINE\n");
        sb.append("=".repeat(45)).append("\n");
        sb.append("ID Transaksi : ").append(transactionId).append("\n");

        if (timestamp != null) {
            DateTimeFormatter fmt = DateTimeFormatter.ofPattern("dd-MM-yyyy HH:mm:ss");
            sb.append("Waktu        : ").append(timestamp.format(fmt)).append("\n");
        }

        sb.append("-".repeat(45)).append("\n");
        sb.append(String.format("%-20s %5s %12s\n", "Produk", "Qty", "Subtotal"));
        sb.append("-".repeat(45)).append("\n");

        for (Map.Entry<Product, Integer> entry : items.entrySet()) {
            Product p = entry.getKey();
            int qty = entry.getValue();
            double subtotal = p.getPrice() * qty;
            sb.append(String.format("%-20s %5d %12.0f\n", p.getName(), qty, subtotal));
        }

        sb.append("-".repeat(45)).append("\n");
        sb.append(String.format("%-26s Rp%,.0f\n", "TOTAL", totalAmount));
        sb.append("-".repeat(45)).append("\n");

        if (payment != null) {
            sb.append("Pembayaran   : QRIS (DANA)\n");
            sb.append("Status       : ").append(payment.getStatus()).append("\n");
        }

        sb.append("=".repeat(45));
        return sb.toString();
    }
}
