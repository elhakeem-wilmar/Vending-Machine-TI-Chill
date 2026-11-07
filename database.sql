-- Database schema untuk Vending Machine 4 Minuman (Kompatibel cPanel / phpMyAdmin)

-- Tabel Admin
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Produk / Slot Minuman (4 slot: 1 s/d 4)
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slot_number` INT NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `price` INT NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `image_url` VARCHAR(255) DEFAULT '',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Transaksi & Dispense Queue
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL UNIQUE,
    `product_id` INT NOT NULL,
    `slot_number` INT NOT NULL,
    `amount` INT NOT NULL,
    `payment_status` ENUM('PENDING', 'PAID', 'EXPIRED', 'FAILED') DEFAULT 'PENDING',
    `dispense_status` ENUM('WAITING_PAYMENT', 'READY_TO_DISPENSE', 'DISPENSING', 'COMPLETED', 'FAILED') DEFAULT 'WAITING_PAYMENT',
    `snap_token` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `paid_at` DATETIME DEFAULT NULL,
    `dispensed_at` DATETIME DEFAULT NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- Data Default Admin: username: admin / password: admin123
-- Hash dihasilkan dari password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `admins` (`username`, `password`) VALUES
('admin', '$2y$10$w85G05ZpYlH.6n/93KqK2uI4vXmFf06B8tW5H83WfT8sT1bH2f56y')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Data Default 4 Slot Minuman
INSERT INTO `products` (`slot_number`, `name`, `price`, `stock`, `image_url`) VALUES
(1, 'Coca Cola Kaleng', 8000, 10, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=400&auto=format&fit=crop&q=80'),
(2, 'Teh Botol Sosro', 6000, 10, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&auto=format&fit=crop&q=80'),
(3, 'Sprite Dingin', 8000, 10, 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?w=400&auto=format&fit=crop&q=80'),
(4, 'Air Mineral Ades', 4000, 15, 'https://images.unsplash.com/photo-1548839140-29a749e1bc4e?w=400&auto=format&fit=crop&q=80')
ON DUPLICATE KEY UPDATE `slot_number`=`slot_number`;

