<?php
session_start();
require_once 'config/database.php';
require_once 'includes/upload_helper.php';
require_once 'includes/auth_helper.php';
require_once 'includes/security_helper.php';

// Require login
requireLogin();
secureSession();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'ID aset tidak valid!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

// Ambil data aset
$stmt = $pdo->prepare("SELECT * FROM aset WHERE id = ?");
$stmt->execute([$id]);
$aset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aset) {
    $_SESSION['message'] = 'Aset tidak ditemukan!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF_ATTACK', 'Invalid CSRF token in edit form');
        $_SESSION['message'] = 'Security token mismatch. Please try again.';
        $_SESSION['message_type'] = 'danger';
        header('Location: edit.php?id=' . $id);
        exit;
    }
    
    try {
        // Sanitize inputs
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
        
        $namaGambar = $aset['gambar']; // Gunakan gambar lama sebagai default
        
        // Handle upload gambar baru
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Hapus gambar lama
            if ($aset['gambar']) {
                hapusGambar($aset['gambar']);
            }
            $namaGambar = uploadGambar($_FILES['gambar'], $_POST['nama_barang']);
        }
        
        // Handle hapus gambar
        if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1') {
            if ($aset['gambar']) {
                hapusGambar($aset['gambar']);
            }
            $namaGambar = null;
        }
        
        $sql = "UPDATE aset SET nama_barang=?, kategori=?, merk=?, model=?, tahun_beli=?, 
                harga=?, kondisi=?, lokasi=?, keterangan=?, gambar=? WHERE id=?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nama_barang,
            $kategori,
            $merk,
            $model,
            $tahun_beli,
            $harga,
            $kondisi,
            $lokasi,
            $keterangan,
            $namaGambar,
            $id
        ]);
        
        $_SESSION['message'] = 'Aset berhasil diperbarui!';
        $_SESSION['message_type'] = 'success';
        header('Location: detail.php?id=' . $id);
        exit;
        
    } catch (Exception $e) {
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
                <h5 class="mb-0">Edit Aset: <?= htmlspecialchars($aset['nama_barang']) ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php outputCSRFToken(); ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama_barang" class="form-label">Nama Barang *</label>
                                <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                                       value="<?= htmlspecialchars($aset['nama_barang']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kategori" class="form-label">Kategori *</label>
                                <select class="form-select" id="kategori" name="kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php 
                                    $kategoris = ['Alat Tulis', 'Elektronik', 'Jaringan', 'Furniture', 'Kendaraan', 'Pakaian', 'Lainnya'];
                                    foreach ($kategoris as $kat): 
                                    ?>
                                    <option value="<?= $kat ?>" <?= $aset['kategori'] === $kat ? 'selected' : '' ?>>
                                        <?= $kat ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="merk" class="form-label">Merk</label>
                                <input type="text" class="form-control" id="merk" name="merk" 
                                       value="<?= htmlspecialchars($aset['merk']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" 
                                       value="<?= htmlspecialchars($aset['model']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tahun_beli" class="form-label">Tahun Beli</label>
                                <input type="number" class="form-control" id="tahun_beli" name="tahun_beli" 
                                       min="1900" max="<?= date('Y') ?>" value="<?= $aset['tahun_beli'] ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga (Rp)</label>
                                <input type="number" class="form-control" id="harga" name="harga" 
                                       min="0" step="1000" value="<?= $aset['harga'] ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="kondisi" class="form-label">Kondisi *</label>
                                <select class="form-select" id="kondisi" name="kondisi" required>
                                    <?php 
                                    $kondisis = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'];
                                    foreach ($kondisis as $kondisi): 
                                    ?>
                                    <option value="<?= $kondisi ?>" <?= $aset['kondisi'] === $kondisi ? 'selected' : '' ?>>
                                        <?= $kondisi ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="lokasi" class="form-label">Lokasi</label>
                        <input type="text" class="form-control" id="lokasi" name="lokasi" 
                               value="<?= htmlspecialchars($aset['lokasi']) ?>"
                               placeholder="Contoh: Meja Kerja, Kamar, Laci, dll">
                    </div>
                    
                    <!-- Gambar Saat Ini -->
                    <?php if ($aset['gambar']): ?>
                    <div class="mb-3">
                        <label class="form-label">Gambar Saat Ini</label>
                        <div class="d-flex align-items-center gap-3">
                            <?= tampilkanGambar($aset['gambar'], $aset['nama_barang'], 'img-thumbnail') ?>
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hapus_gambar" name="hapus_gambar" value="1">
                                    <label class="form-check-label" for="hapus_gambar">
                                        Hapus gambar ini
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="gambar" class="form-label">
                            <?= $aset['gambar'] ? 'Ganti Gambar' : 'Upload Gambar' ?>
                        </label>
                        <input type="file" class="form-control" id="gambar" name="gambar" 
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text">Format: JPG, PNG, GIF, WebP. Maksimal 5MB.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                                  placeholder="Deskripsi tambahan, catatan khusus, dll"><?= htmlspecialchars($aset['keterangan']) ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="detail.php?id=<?= $id ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>