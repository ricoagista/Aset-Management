<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_helper.php';

// Require login
requireLogin();

$user = getLoggedInUser();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    try {
        // Update nama dan email
        if (!empty($nama_lengkap) && !empty($email)) {
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $email, $user['id']]);
            
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $message = 'Profil berhasil diperbarui!';
            $message_type = 'success';
        }
        
        // Update password jika diisi
        if (!empty($password_lama) && !empty($password_baru)) {
            // Verify password lama
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $current_password = $stmt->fetchColumn();
            
            if (!verifyPassword($password_lama, $current_password)) {
                throw new Exception('Password lama tidak benar!');
            }
            
            if ($password_baru !== $konfirmasi_password) {
                throw new Exception('Konfirmasi password tidak cocok!');
            }
            
            if (strlen($password_baru) < 6) {
                throw new Exception('Password baru minimal 6 karakter!');
            }
            
            $new_password_hash = hashPassword($password_baru);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password_hash, $user['id']]);
            
            $message = 'Password berhasil diubah!';
            $message_type = 'success';
        }
        
        // Refresh user data
        $user = getLoggedInUser();
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user"></i> Profil Pengguna</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Informasi Dasar</h6>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                <div class="form-text">Username tidak dapat diubah</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                       value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Ubah Password</h6>
                            <div class="form-text mb-3">Kosongkan jika tidak ingin mengubah password</div>
                            
                            <div class="mb-3">
                                <label for="password_lama" class="form-label">Password Lama</label>
                                <input type="password" class="form-control" id="password_lama" name="password_lama">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_baru" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="password_baru" name="password_baru" minlength="6">
                                <div class="form-text">Minimal 6 karakter</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" minlength="6">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Informasi Akun -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Akun</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">ID Pengguna</small>
                        <strong><?= $user['id'] ?></strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Username</small>
                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Total Aset</small>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aset");
                        $total_aset = $stmt->fetchColumn();
                        ?>
                        <strong><?= $total_aset ?> item</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Bergabung</small>
                        <?php
                        $stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        $created_at = $stmt->fetchColumn();
                        ?>
                        <strong><?= date('d/m/Y', strtotime($created_at)) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validasi konfirmasi password
document.getElementById('konfirmasi_password').addEventListener('input', function() {
    const password = document.getElementById('password_baru').value;
    const confirm = this.value;
    
    if (password !== confirm && confirm !== '') {
        this.setCustomValidity('Password tidak cocok');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password_baru').addEventListener('input', function() {
    const confirm = document.getElementById('konfirmasi_password');
    confirm.dispatchEvent(new Event('input'));
});
</script>

<?php require_once 'includes/footer.php'; ?>