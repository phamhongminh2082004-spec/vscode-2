-- Tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- Bảng sản phẩm (món)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price INT NOT NULL,
    quantity INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng đơn hàng
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address VARCHAR(255) NOT NULL,
    total INT NOT NULL,
    status ENUM('pending','confirmed','shipping','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (phone)
);

-- Chi tiết đơn (lưu sẵn tên + giá lúc đặt nên xoá món không mất lịch sử)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(255) NOT NULL,
    price INT NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Tài khoản chủ tiệm (mặc định: admin / admin123 -> hãy đổi mật khẩu ngay sau khi đăng nhập)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);
INSERT INTO admins (username, password) VALUES
('admin', '$2b$12$V8VcNWlfTnFlgR05CuueoO8xLE.XqNbFhzJa6CR1lvh4mPs4Xldim');

-- Dữ liệu demo
INSERT INTO products (name, price, quantity, description) VALUES
('Gundam HG 1/144 RX-78-2', 380000, 12, 'Bản HG kinh điển, lắp ráp không cần keo'),
('Gundam MG 1/100 Strike Freedom', 1650000, 4, 'Master Grade có khung xương bên trong'),
('Xe Tamiya 1/24 Nissan GT-R', 720000, 6, 'Mô hình xe tĩnh, cần keo và sơn'),
('Máy bay Revell 1/72 Spitfire', 450000, 5, 'Bộ lắp ráp máy bay chiến đấu, kèm decal'),
('Sơn Mr.Color C1 trắng 10ml', 38000, 40, 'Sơn gốc dung môi, dùng cho airbrush'),
('Kìm cắt nhựa chuyên dụng', 185000, 15, 'Lưỡi mỏng, cắt sát linh kiện');
