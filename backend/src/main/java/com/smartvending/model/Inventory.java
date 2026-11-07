package com.smartvending.model;

import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;

/**
 * Kelas yang mengelola inventaris produk dalam mesin vending.
 * Menyediakan operasi CRUD dan pengecekan stok untuk seluruh produk.
 */
public class Inventory {

    /** Daftar seluruh produk dalam inventaris */
    private List<Product> products;

    /**
     * Konstruktor untuk membuat inventaris kosong.
     */
    public Inventory() {
        this.products = new ArrayList<>();
    }

    /**
     * Menambahkan produk baru ke dalam inventaris.
     *
     * @param product produk yang akan ditambahkan
     */
    public void addProduct(Product product) {
        products.add(product);
    }

    /**
     * Mencari produk berdasarkan ID.
     *
     * @param id ID produk yang dicari
     * @return objek Product jika ditemukan, null jika tidak ada
     */
    public Product getProductById(String id) {
        for (Product product : products) {
            if (product.getId().equals(id)) {
                return product;
            }
        }
        return null;
    }

    /**
     * Mendapatkan seluruh daftar produk dalam inventaris.
     *
     * @return list seluruh produk
     */
    public List<Product> getAllProducts() {
        return products;
    }

    /**
     * Mengecek jumlah stok produk berdasarkan ID.
     *
     * @param productId ID produk yang dicek
     * @return jumlah stok, atau -1 jika produk tidak ditemukan
     */
    public int checkStock(String productId) {
        Product product = getProductById(productId);
        if (product != null) {
            return product.getStock();
        }
        return -1;
    }

    /**
     * Mengurangi stok produk berdasarkan ID.
     *
     * @param productId ID produk
     * @param qty       jumlah yang akan dikurangi
     */
    public void reduceStock(String productId, int qty) {
        Product product = getProductById(productId);
        if (product != null) {
            product.reduceStock(qty);
        }
    }

    /**
     * Mendapatkan daftar produk yang stoknya rendah (stok <= 2).
     * Berguna untuk notifikasi ke operator mesin vending.
     *
     * @return list produk dengan stok rendah
     */
    public List<Product> lowStockAlert() {
        return products.stream()
                .filter(p -> p.getStock() <= 2)
                .collect(Collectors.toList());
    }

    /**
     * Mendapatkan daftar produk yang masih tersedia (stok > 0).
     *
     * @return list produk yang tersedia
     */
    public List<Product> getAvailableProducts() {
        return products.stream()
                .filter(Product::isAvailable)
                .collect(Collectors.toList());
    }
}
