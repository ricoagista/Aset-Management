<?php
session_start();
require_once 'config/database.php';
require_once 'includes/upload_helper.php';
require_once 'includes/auth_helper.php';
require_once 'includes/security_helper.php';

// Require login
requireLogin();
secureSession();

// Ambil data aset dengan filter
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? sanitizeInput($_GET['kategori']) : '';

$sql = "SELECT * FROM aset WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nama_barang LIKE ? OR merk LIKE ? OR model LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($kategori)) {
    $sql .= " AND kategori = ?";
    $params[] = $kategori;
}

$sql .= " ORDER BY tanggal_input DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$asets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar kategori untuk filter
$kategoriStmt = $pdo->query("SELECT DISTINCT kategori FROM aset ORDER BY kategori");
$kategoris = $kategoriStmt->fetchAll(PDO::FETCH_COLUMN);

// Statistik
$totalAset = $pdo->query("SELECT COUNT(*) FROM aset")->fetchColumn();
$asetBaik = $pdo->query("SELECT COUNT(*) FROM aset WHERE kondisi = 'Baik'")->fetchColumn();
$asetRusak = $pdo->query("SELECT COUNT(*) FROM aset WHERE kondisi IN ('Rusak Ringan', 'Rusak Berat')")->fetchColumn();
$totalNilai = $pdo->query("SELECT SUM(harga) FROM aset WHERE kondisi != 'Hilang'")->fetchColumn();

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-6 col-md-3 mb-3">
        <div class="card bg-primary text-white stats-card">
            <div class="card-body text-center">
                <h4><?= $totalAset ?></h4>
                <small>Total Aset</small>
                <div class="mt-2">
                    <i class="fas fa-box fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="card bg-success text-white stats-card">
            <div class="card-body text-center">
                <h4><?= $asetBaik ?></h4>
                <small>Kondisi Baik</small>
                <div class="mt-2">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="card bg-warning text-white stats-card">
            <div class="card-body text-center">
                <h4><?= $asetRusak ?></h4>
                <small>Perlu Perbaikan</small>
                <div class="mt-2">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="card bg-info text-white stats-card">
            <div class="card-body text-center">
                <h4>Rp <?= number_format($totalNilai, 0, ',', '.') ?></h4>
                <small>Total Nilai</small>
                <div class="mt-2">
                    <i class="fas fa-money-bill fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Aset</h5>
        <a href="tambah.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Aset
        </a>
    </div>
    <div class="card-body">
        <!-- Filter dan Pencarian -->
        <form method="GET" class="mb-4">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <input type="text" class="form-control" name="search" placeholder="Cari nama barang, merk, atau model..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-8 col-md-4">
                    <select name="kategori" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategoris as $kat): ?>
                        <option value="<?= htmlspecialchars($kat) ?>" <?= $kategori === $kat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kat) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-4 col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-search d-none d-md-inline"></i>
                        <span class="d-md-none">Cari</span>
                        <span class="d-none d-md-inline"> Cari</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Desktop Table -->
        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Merk/Model</th>
                            <th>Tahun</th>
                            <th>Harga</th>
                            <th>Kondisi</th>
                            <th>Lokasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($asets)): ?>
                        <tr>
                            <td colspan="10" class="text-center">Tidak ada data aset</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($asets as $index => $aset): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <?php if ($aset['gambar']): ?>
                                    <?= tampilkanGambar($aset['gambar'], $aset['nama_barang'], 'table-img') ?>
                                <?php else: ?>
                                    <div class="text-muted">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($aset['nama_barang']) ?></td>
                            <td><?= htmlspecialchars($aset['kategori']) ?></td>
                            <td><?= htmlspecialchars($aset['merk'] . ' ' . $aset['model']) ?></td>
                            <td><?= $aset['tahun_beli'] ?></td>
                            <td>Rp <?= number_format($aset['harga'], 0, ',', '.') ?></td>
                            <td>
                                <span class="status-<?= strtolower(str_replace(' ', '-', $aset['kondisi'])) ?>">
                                    <i class="fas fa-circle"></i> <?= htmlspecialchars($aset['kondisi']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($aset['lokasi']) ?></td>
                            <td>
                                <a href="detail.php?id=<?= $aset['id'] ?>" class="btn btn-sm btn-info btn-action" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $aset['id'] ?>" class="btn btn-sm btn-warning btn-action" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete(<?= $aset['id'] ?>, '<?= htmlspecialchars($aset['nama_barang']) ?>')" 
                                        class="btn btn-sm btn-danger btn-action" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile List -->
        <div class="mobile-list">
            <?php if (empty($asets)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Tidak ada data aset</h5>
            </div>
            <?php else: ?>
            <?php foreach ($asets as $aset): ?>
            <div class="mobile-item">
                <div class="mobile-item-header">
                    <?php if ($aset['gambar']): ?>
                        <?= tampilkanGambar($aset['gambar'], $aset['nama_barang'], 'mobile-item-image') ?>
                    <?php else: ?>
                        <div class="mobile-item-image bg-light d-flex align-items-center justify-content-center">
                            <i class="fas fa-image text-muted"></i>
                        </div>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <div class="mobile-item-title"><?= htmlspecialchars($aset['nama_barang']) ?></div>
                        <div class="mobile-item-subtitle"><?= htmlspecialchars($aset['kategori']) ?></div>
                    </div>
                </div>
                
                <div class="mobile-item-details">
                    <div class="mobile-detail-item">
                        <span class="mobile-detail-label">Merk/Model:</span>
                        <?= htmlspecialchars($aset['merk'] . ' ' . $aset['model']) ?>
                    </div>
                    <div class="mobile-detail-item">
                        <span class="mobile-detail-label">Tahun:</span>
                        <?= $aset['tahun_beli'] ?: '-' ?>
                    </div>
                    <div class="mobile-detail-item">
                        <span class="mobile-detail-label">Harga:</span>
                        Rp <?= number_format($aset['harga'], 0, ',', '.') ?>
                    </div>
                    <div class="mobile-detail-item">
                        <span class="mobile-detail-label">Kondisi:</span>
                        <span class="status-<?= strtolower(str_replace(' ', '-', $aset['kondisi'])) ?>">
                            <i class="fas fa-circle"></i> <?= htmlspecialchars($aset['kondisi']) ?>
                        </span>
                    </div>
                    <div class="mobile-detail-item">
                        <span class="mobile-detail-label">Lokasi:</span>
                        <?= htmlspecialchars($aset['lokasi']) ?: '-' ?>
                    </div>
                </div>
                
                <div class="mobile-actions">
                    <a href="detail.php?id=<?= $aset['id'] ?>" class="btn btn-sm btn-info" title="Detail">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                    <a href="edit.php?id=<?= $aset['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button onclick="confirmDelete(<?= $aset['id'] ?>, '<?= htmlspecialchars($aset['nama_barang']) ?>')" 
                            class="btn btn-sm btn-danger" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Floating Action Button untuk Mobile -->
<div class="d-md-none position-fixed" style="bottom: 20px; right: 20px; z-index: 1000;">
    <a href="tambah.php" class="btn btn-primary rounded-circle shadow" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-plus fa-lg"></i>
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>