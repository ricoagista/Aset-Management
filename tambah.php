<?php
session_start();
require_once 'config/database.php';
require_once 'includes/upload_helper.php';
require_once 'includes/auth_helper.php';
require_once 'includes/security_helper.php';

// Require login
requireLogin();
secureSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF_ATTACK', 'Invalid CSRF token in tambah form');
        $_SESSION['message'] = 'Security token mismatch. Please try again.';
        $_SESSION['message_type'] = 'danger';
        header('Location: tambah.php');
        exit;
    }
    
    try {
        // Sanitize all inputs
        $nama_barang = sanitizeInput($_POST['nama_barang']);
        $kategori = sanitizeInput($_POST['kategori']);
        $merk = sanitizeInput($_POST['merk']);
        $model = sanitizeInput($_POST['model']);
        $tahun_beli = filter_var($_POST['tahun_beli'], FILTER_VALIDATE_INT);
        $harga = filter_var($_POST['harga'], FILTER_VALIDATE_FLOAT);
        $kondisi = sanitizeInput($_POST['kondisi']);
        $lokasi = sanitizeInput($_POST['lokasi']);
        $keterangan = sanitizeInput($_POST['keterangan']);
        
        // Validate required fields
        if (empty($nama_barang) || empty($kategori) || empty($kondisi)) {
            throw new Exception('Nama barang, kategori, dan kondisi harus diisi');
        }
        
        $namaGambar = null;
        
        // Handle upload gambar
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $namaGambar = uploadGambar($_FILES['gambar'], $nama_barang);
        }
        
        $sql = "INSERT INTO aset (nama_barang, kategori, merk, model, tahun_beli, harga, kondisi, lokasi, keterangan, gambar) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = executeQuery($pdo, $sql, [
            $nama_barang,
            $kategori,
            $merk,
            $model,
            $tahun_beli,
            $harga,
            $kondisi,
            $lokasi,
            $keterangan,
            $namaGambar
        ]);
        
        logSecurityEvent('ASET_CREATED', 'Aset: ' . $nama_barang);
        $_SESSION['message'] = 'Aset berhasil ditambahkan!';
        $_SESSION['message_type'] = 'success';
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        logSecurityEvent('ASET_CREATE_ERROR', $e->getMessage());
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tambah Aset Baru</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php outputCSRFToken(); ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama_barang" class="form-label">Nama Barang *</label>
                                <input type="text" class="form-control" id="nama_barang" name="nama_barang" required maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kategori" class="form-label">Kategori *</label>
                                <select class="form-select" id="kategori" name="kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="Alat Tulis">Alat Tulis</option>
                                    <option value="Elektronik">Elektronik</option>
                                    <option value="Jaringan">Jaringan</option>
                                    <option value="Furniture">Furniture</option>
                                    <option value="Kendaraan">Kendaraan</option>
                                    <option value="Pakaian">Pakaian</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="merk" class="form-label">Merk</label>
                                <input type="text" class="form-control" id="merk" name="merk">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tahun_beli" class="form-label">Tahun Beli</label>
                                <input type="number" class="form-control" id="tahun_beli" name="tahun_beli" 
                                       min="1900" max="<?= date('Y') ?>" value="<?= date('Y') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga (Rp)</label>
                                <input type="number" class="form-control" id="harga" name="harga" min="0" step="1000">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="kondisi" class="form-label">Kondisi *</label>
                                <select class="form-select" id="kondisi" name="kondisi" required>
                                    <option value="Baik">Baik</option>
                                    <option value="Rusak Ringan">Rusak Ringan</option>
                                    <option value="Rusak Berat">Rusak Berat</option>
                                    <option value="Hilang">Hilang</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="lokasi" class="form-label">Lokasi</label>
                        <input type="text" class="form-control" id="lokasi" name="lokasi" 
                               placeholder="Contoh: Meja Kerja, Kamar, Laci, dll">
                    </div>
                    
                    <div class="mb-3">
                        <label for="gambar" class="form-label">Gambar Barang</label>
                        <input type="file" class="form-control" id="gambar" name="gambar" 
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text">Format: JPG, PNG, GIF, WebP. Maksimal 5MB.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                                  placeholder="Deskripsi tambahan, catatan khusus, dll"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Aset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>