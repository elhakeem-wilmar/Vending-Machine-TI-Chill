package com.smartvending.model;

/**
 * Kelas abstrak yang merepresentasikan pembayaran dalam mesin vending.
 * Menyediakan kerangka dasar untuk berbagai metode pembayaran (QRIS, e-wallet, dll).
 * Subclass harus mengimplementasikan processPayment() dan validatePayment().
 */
public abstract class Payment {

    /** Jumlah pembayaran dalam Rupiah */
    protected double amount;

    /** ID unik pembayaran */
    protected String paymentId;

    /** Status pembayaran (PENDING, PROCESSING, CONFIRMED, FAILED) */
    protected String status;

    /**
     * Konstruktor untuk membuat objek Payment.
     * Status awal selalu "PENDING".
     *
     * @param amount    jumlah pembayaran
     * @param paymentId ID unik pembayaran
     */
    public Payment(double amount, String paymentId) {
        this.amount = amount;
        this.paymentId = paymentId;
        this.status = "PENDING";
    }

    /**
     * Memproses pembayaran. Implementasi spesifik tergantung metode pembayaran.
     */
    public abstract void processPayment();

    /**
     * Memvalidasi apakah pembayaran sudah berhasil dikonfirmasi.
     *
     * @return true jika pembayaran valid/terkonfirmasi
     */
    public abstract boolean validatePayment();

    // === Getter dan Setter ===

    public double getAmount() {
        return amount;
    }

    public void setAmount(double amount) {
        this.amount = amount;
    }

    public String getPaymentId() {
        return paymentId;
    }

    public void setPaymentId(String paymentId) {
        this.paymentId = paymentId;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    /**
     * Representasi string dari informasi pembayaran.
     *
     * @return string berisi detail pembayaran
     */
    @Override
    public String toString() {
        return String.format("Payment[ID=%s, Amount=Rp%.0f, Status=%s]", paymentId, amount, status);
    }
}
