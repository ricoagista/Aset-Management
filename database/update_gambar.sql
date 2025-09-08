-- Update database untuk menambahkan kolom gambar
-- Jalankan script ini jika database sudah ada sebelumnya

ALTER TABLE aset ADD COLUMN gambar VARCHAR(255) AFTER keterangan;