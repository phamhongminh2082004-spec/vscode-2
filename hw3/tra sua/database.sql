SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS trasua CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trasua;

CREATE TABLE IF NOT EXISTS admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tendangnhap VARCHAR(50) NOT NULL UNIQUE,
  matkhau VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS sanpham (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ten VARCHAR(120) NOT NULL,
  loai VARCHAR(60) NOT NULL DEFAULT 'Trà sữa',
  gia INT UNSIGNED NOT NULL,
  mota VARCHAR(255) DEFAULT NULL,
  dang_ban TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS donhang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ten_khach VARCHAR(100) NOT NULL,
  sdt VARCHAR(15) NOT NULL,
  diachi VARCHAR(255) NOT NULL,
  ghichu VARCHAR(255) DEFAULT NULL,
  tongtien INT UNSIGNED NOT NULL,
  trangthai ENUM('moi','dang_lam','xong','huy') NOT NULL DEFAULT 'moi',
  ngaytao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (sdt), INDEX (ngaytao)
);

CREATE TABLE IF NOT EXISTS chitiet_don (
  id INT AUTO_INCREMENT PRIMARY KEY,
  donhang_id INT NOT NULL,
  sanpham_id INT DEFAULT NULL,
  tensp VARCHAR(120) NOT NULL,
  gia INT UNSIGNED NOT NULL,
  soluong INT UNSIGNED NOT NULL,
  FOREIGN KEY (donhang_id) REFERENCES donhang(id) ON DELETE CASCADE,
  FOREIGN KEY (sanpham_id) REFERENCES sanpham(id) ON DELETE SET NULL
);

-- Tài khoản chủ tiệm mặc định: admin / admin123  (HÃY ĐỔI SAU KHI ĐĂNG NHẬP LẦN ĐẦU)
INSERT INTO admin (tendangnhap, matkhau) VALUES ('admin', '$2y$10$w3pZfhEr.RNS.ZRXK5c.g.vdgnV6JNHZkLOBSw8qBIS4NwaUZeogS');

INSERT INTO sanpham (ten, loai, gia, mota) VALUES
('Trà sữa truyền thống', 'Trà sữa', 30000, 'Vị trà đậm, sữa béo nhẹ'),
('Trà sữa trân châu đường đen', 'Trà sữa', 38000, 'Trân châu dai, đường đen thơm'),
('Trà đào cam sả', 'Trà trái cây', 35000, 'Chua ngọt thanh mát'),
('Trà vải', 'Trà trái cây', 35000, 'Vải thiều tươi'),
('Matcha latte', 'Đặc biệt', 42000, 'Matcha Nhật pha sữa tươi');
