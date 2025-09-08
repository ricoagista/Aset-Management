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

// CSRF Protection untuk hapus
if (!isset($_GET['token']) || !validateCSRFToken($_GET['token'])) {
    logSecurityEvent('CSRF_ATTACK', 'Invalid CSRF token in delete operation');
    $_SESSION['message'] = 'Token keamanan tidak valid!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

try {
    // Cek apakah aset ada dan ambil data gambar
    $stmt = $pdo->prepare("SELECT nama_barang, gambar FROM aset WHERE id = ?");
    $stmt->execute([$id]);
    $aset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aset) {
        $_SESSION['message'] = 'Aset tidak ditemukan!';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit;
    }
    
    // Hapus gambar jika ada
    if ($aset['gambar']) {
        hapusGambar($aset['gambar']);
    }
    
    // Hapus aset dari database
    $stmt = $pdo->prepare("DELETE FROM aset WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = 'Aset "' . htmlspecialchars($aset['nama_barang']) . '" berhasil dihapus!';
    $_SESSION['message_type'] = 'success';
    
} catch (PDOException $e) {
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

header('Location: index.php');
exit;
?>