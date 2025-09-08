CREATE DATABASE IF NOT EXISTS aset_pribadi;
USE aset_pribadi;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, nama_lengkap) VALUES
('admin', 'admin@asetpribadi.com', '$2y$10$BZR9VWz8aV/l1.QHPkf/uuK4Zh7j/2dLj8J1CqN6UKxO.xZjhZfS2', 'Administrator');

CREATE TABLE IF NOT EXISTS aset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(255) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    merk VARCHAR(100),
    model VARCHAR(100),
    tahun_beli YEAR,
    harga DECIMAL(10,2),
    kondisi ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') DEFAULT 'Baik',
    lokasi VARCHAR(255),
    keterangan TEXT,
    gambar VARCHAR(255),
    tanggal_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO aset (nama_barang, kategori, merk, model, tahun_beli, harga, kondisi, lokasi, keterangan) VALUES
('Pulpen Pilot', 'Alat Tulis', 'Pilot', 'G2', 2023, 15000, 'Baik', 'Meja Kerja', 'Pulpen gel hitam'),
('Penghapus Faber Castell', 'Alat Tulis', 'Faber Castell', '7081N', 2023, 5000, 'Baik', 'Laci Meja', 'Penghapus putih'),
('Charger Laptop Dell', 'Elektronik', 'Dell', '65W', 2022, 350000, 'Baik', 'Kamar', 'Charger original laptop Dell'),
('Router Mikrotik', 'Jaringan', 'Mikrotik', 'hAP ac²', 2021, 1200000, 'Baik', 'Ruang Tamu', 'Router wireless AC dual band');