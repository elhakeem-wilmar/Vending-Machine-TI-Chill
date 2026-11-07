package com.smartvending.model;

/**
 * Implementasi pembayaran menggunakan QRIS melalui DANA.
 * Menghasilkan data QR code yang berisi informasi pembayaran ke nomor DANA tujuan.
 */
public class QRISPayment extends Payment {

    /** Nomor DANA tujuan pembayaran */
    private final String danaNumber = "085167887467";

    /** Data QR code yang di-generate untuk pembayaran */
    private String qrCodeData;

    /**
     * Konstruktor untuk membuat pembayaran QRIS.
     * Otomatis men-generate data QR code berdasarkan jumlah dan ID pembayaran.
     *
     * @param amount    jumlah pembayaran dalam Rupiah
     * @param paymentId ID unik pembayaran
     */
    public QRISPayment(double amount, String paymentId) {
        super(amount, paymentId);
        this.qrCodeData = "DANA://pay?number=" + danaNumber + "&amount=" + (int) amount + "&id=" + paymentId;
    }

    /**
     * Menghasilkan string data QR code untuk ditampilkan sebagai kode QR.
     *
     * @return string data QR code
     */
    public String generateQRCode() {
        return qrCodeData;
    }

    /**
     * Memproses pembayaran QRIS.
     * Mengubah status dari PENDING ke PROCESSING, lalu ke CONFIRMED.
     */
    // shortcut: simulasi pembayaran, integrasikan dengan DANA API untuk produksi
    @Override
    public void processPayment() {
        System.out.println(">> Memproses pembayaran QRIS...");
        System.out.println(">> QR Code Data: " + qrCodeData);
        this.status = "PROCESSING";
        System.out.println(">> Status: " + status);

        // Simulasi konfirmasi pembayaran berhasil
        this.status = "CONFIRMED";
        System.out.println(">> Pembayaran DIKONFIRMASI!");
    }

    /**
     * Memvalidasi apakah pembayaran sudah terkonfirmasi.
     *
     * @return true jika status adalah "CONFIRMED"
     */
    @Override
    public boolean validatePayment() {
        return status.equals("CONFIRMED");
    }

    // === Getter ===

    public String getDanaNumber() {
        return danaNumber;
    }

    public String getQrCodeData() {
        return qrCodeData;
    }

    /**
     * Representasi string dari pembayaran QRIS.
     *
     * @return string berisi detail pembayaran QRIS
     */
    @Override
    public String toString() {
        return String.format("QRISPayment[ID=%s, DANA=%s, Amount=Rp%.0f, Status=%s]",
                paymentId, danaNumber, amount, status);
    }
}
