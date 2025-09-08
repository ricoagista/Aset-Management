# Manajemen Aset Pribadi

Website PHP untuk mengelola aset barang pribadi seperti pulpen, penghapus, charger, router mikrotik, dan barang lainnya.

## Fitur

- Dashboard dengan statistik aset
- Tambah, edit, dan hapus aset
- Pencarian dan filter berdasarkan kategori
- Detail informasi setiap aset
- Responsive design dengan Bootstrap

## Instalasi

1. **Persiapan Database**
   - Buka phpMyAdmin
   - Import file `database/aset_pribadi.sql`
   - Atau jalankan query SQL yang ada di file tersebut

2. **Konfigurasi Database**
   - Edit file `config/database.php` jika diperlukan
   - Sesuaikan kredensial database (username, password, nama database)

3. **Akses Website**
   - Buka browser dan akses: `http://localhost/aset-pribadi`

## Struktur File

```
aset-pribadi/
├── config/
│   └── database.php       # Konfigurasi koneksi database
├── database/
│   └── aset_pribadi.sql   # File SQL database
├── includes/
│   ├── header.php         # Template header
│   └── footer.php         # Template footer
├── index.php              # Halaman utama/dashboard
├── tambah.php             # Form tambah aset
├── edit.php               # Form edit aset
├── detail.php             # Detail aset
├── hapus.php              # Proses hapus aset
└── README.md              # Dokumentasi
```

## Kategori Aset

- Alat Tulis (pulpen, penghapus, dll)
- Elektronik (charger, laptop, dll)
- Jaringan (router, switch, dll)
- Furniture (meja, kursi, dll)
- Kendaraan
- Pakaian
- Lainnya

## Status Kondisi

- Baik
- Rusak Ringan
- Rusak Berat
- Hilang

## Teknologi

- PHP 7.4+
- MySQL/MariaDB
- Bootstrap 5
- Font Awesome
- PDO untuk database

## Lisensi

Open source - bebas digunakan dan dimodifikasi.