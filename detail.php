<?php
session_start();
require_once 'config/database.php';
require_once 'includes/upload_helper.php';
require_once 'includes/auth_helper.php';

// Require login
requireLogin();

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

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <h5 class="mb-0">Detail Aset</h5>
                    <div class="d-flex gap-2">
                        <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button onclick="confirmDelete(<?= $aset['id'] ?>, '<?= htmlspecialchars($aset['nama_barang']) ?>')" 
                                class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($aset['gambar']): ?>
                <!-- Mobile: Gambar di atas -->
                <div class="d-md-none text-center mb-4">
                    <h6><strong>Gambar:</strong></h6>
                    <?= tampilkanGambar($aset['gambar'], $aset['nama_barang'], 'img-fluid rounded shadow-sm') ?>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <?php if ($aset['gambar']): ?>
                    <!-- Desktop: Gambar di samping -->
                    <div class="col-md-4 mb-4 d-none d-md-block">
                        <div class="text-center">
                            <h6><strong>Gambar:</strong></h6>
                            <?= tampilkanGambar($aset['gambar'], $aset['nama_barang'], 'img-fluid rounded shadow-sm') ?>
                        </div>
                    </div>
                    <div class="col-md-8">
                    <?php else: ?>
                    <div class="col-md-12">
                    <?php endif; ?>
                        <!-- Mobile: Stack vertically -->
                        <div class="d-md-none">
                            <div class="mb-3">
                                <strong class="text-primary">Informasi Dasar</strong>
                                <hr class="my-2">
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <small class="text-muted d-block">Nama Barang</small>
                                    <strong><?= htmlspecialchars($aset['nama_barang']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Kategori</small>
                                    <span><?= htmlspecialchars($aset['kategori']) ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Merk</small>
                                    <span><?= htmlspecialchars($aset['merk']) ?: '-' ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Model</small>
                                    <span><?= htmlspecialchars($aset['model']) ?: '-' ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Tahun Beli</small>
                                    <span><?= $aset['tahun_beli'] ?: '-' ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Harga</small>
                                    <span>
                                        <?php if ($aset['harga']): ?>
                                            <strong class="text-success">Rp <?= number_format($aset['harga'], 0, ',', '.') ?></strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Kondisi</small>
                                    <span class="status-<?= strtolower(str_replace(' ', '-', $aset['kondisi'])) ?>">
                                        <i class="fas fa-circle"></i> <?= $aset['kondisi'] ?>
                                    </span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Lokasi</small>
                                    <span><?= htmlspecialchars($aset['lokasi']) ?: '-' ?></span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <strong class="text-primary">Informasi Sistem</strong>
                                <hr class="my-2">
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Tanggal Input</small>
                                    <small><?= date('d/m/Y H:i', strtotime($aset['tanggal_input'])) ?></small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Terakhir Update</small>
                                    <small><?= date('d/m/Y H:i', strtotime($aset['tanggal_update'])) ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Desktop: Table layout -->
                        <div class="d-none d-md-block">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Nama Barang:</strong></td>
                                            <td><?= htmlspecialchars($aset['nama_barang']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kategori:</strong></td>
                                            <td><?= htmlspecialchars($aset['kategori']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Merk:</strong></td>
                                            <td><?= htmlspecialchars($aset['merk']) ?: '-' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Model:</strong></td>
                                            <td><?= htmlspecialchars($aset['model']) ?: '-' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tahun Beli:</strong></td>
                                            <td><?= $aset['tahun_beli'] ?: '-' ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Harga:</strong></td>
                                            <td>
                                                <?php if ($aset['harga']): ?>
                                                    Rp <?= number_format($aset['harga'], 0, ',', '.') ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kondisi:</strong></td>
                                            <td>
                                                <span class="status-<?= strtolower(str_replace(' ', '-', $aset['kondisi'])) ?>">
                                                    <i class="fas fa-circle"></i> <?= $aset['kondisi'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Lokasi:</strong></td>
                                            <td><?= htmlspecialchars($aset['lokasi']) ?: '-' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Input:</strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($aset['tanggal_input'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Terakhir Update:</strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($aset['tanggal_update'])) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($aset['keterangan']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><strong>Keterangan:</strong></h6>
                        <div class="p-3 bg-light rounded">
                            <?= nl2br(htmlspecialchars($aset['keterangan'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>