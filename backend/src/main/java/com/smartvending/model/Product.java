package com.smartvending.model;

/**
 * Kelas model yang merepresentasikan produk dalam mesin vending.
 * Menyimpan informasi lengkap produk seperti ID, nama, harga, stok, kategori, dan URL gambar.
 */
public class Product {

    /** ID unik produk */
    private String id;

    /** Nama produk */
    private String name;

    /** Harga produk dalam Rupiah */
    private double price;

    /** Jumlah stok produk yang tersedia */
    private int stock;

    /** Kategori produk (contoh: "Minuman", "Makanan") */
    private String category;

    /** URL gambar produk */
    private String imageUrl;

    /**
     * Konstruktor untuk membuat objek Product dengan semua atribut.
     *
     * @param id       ID unik produk
     * @param name     nama produk
     * @param price    harga produk
     * @param stock    jumlah stok awal
     * @param category kategori produk
     * @param imageUrl URL gambar produk
     */
    public Product(String id, String name, double price, int stock, String category, String imageUrl) {
        this.id = id;
        this.name = name;
        this.price = price;
        this.stock = stock;
        this.category = category;
        this.imageUrl = imageUrl;
    }

    /**
     * Mengecek apakah produk masih tersedia (stok lebih dari 0).
     *
     * @return true jika stok lebih dari 0, false jika habis
     */
    public boolean isAvailable() {
        return stock > 0;
    }

    /**
     * Mengurangi stok produk sejumlah qty.
     *
     * @param qty jumlah yang akan dikurangi
     * @throws IllegalArgumentException jika qty melebihi stok yang tersedia
     */
    public void reduceStock(int qty) {
        if (qty > stock) {
            throw new IllegalArgumentException("Stok tidak mencukupi untuk produk " + name
                    + ". Tersedia: " + stock + ", diminta: " + qty);
        }
        stock -= qty;
    }

    // === Getter dan Setter ===

    public String getId() {
        return id;
    }

    public void setId(String id) {
        this.id = id;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public double getPrice() {
        return price;
    }

    public void setPrice(double price) {
        this.price = price;
    }

    public int getStock() {
        return stock;
    }

    public void setStock(int stock) {
        this.stock = stock;
    }

    public String getCategory() {
        return category;
    }

    public void setCategory(String category) {
        this.category = category;
    }

    public String getImageUrl() {
        return imageUrl;
    }

    public void setImageUrl(String imageUrl) {
        this.imageUrl = imageUrl;
    }

    /**
     * Representasi string dari produk dalam format yang mudah dibaca.
     *
     * @return string berisi informasi lengkap produk
     */
    @Override
    public String toString() {
        return String.format("[%s] %s - Rp%.0f (Stok: %d, Kategori: %s)", id, name, price, stock, category);
    }
}
