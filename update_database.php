<?php
require_once 'config/database.php';

try {
    // Cek apakah tabel users sudah ada
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $users_exists = $stmt->fetch();
    
    if (!$users_exists) {
        // Buat tabel users
        $pdo->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            nama_lengkap VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Insert default admin user (password: admin123)
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, nama_lengkap) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@asetpribadi.com', $password_hash, 'Administrator']);
        
        echo "✅ Tabel users berhasil dibuat!<br>";
        echo "✅ User default admin berhasil ditambahkan!<br>";
    } else {
        echo "ℹ️ Tabel users sudah ada.<br>";
    }

    // Cek apakah kolom gambar sudah ada
    $stmt = $pdo->query("SHOW COLUMNS FROM aset LIKE 'gambar'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Tambahkan kolom gambar
        $pdo->exec("ALTER TABLE aset ADD COLUMN gambar VARCHAR(255) AFTER keterangan");
        echo "✅ Kolom gambar berhasil ditambahkan ke tabel aset!<br>";
    } else {
        echo "ℹ️ Kolom gambar sudah ada di tabel aset.<br>";
    }
    
    echo "<br><h5>Informasi Login Default:</h5>";
    echo "<div class='alert alert-info'>";
    echo "<strong>Username:</strong> admin<br>";
    echo "<strong>Password:</strong> admin123";
    echo "</div>";
    
    echo "<br><a href='login.php' class='btn btn-primary'>← Login Sekarang</a>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Update Database</h5>
                    </div>
                    <div class="card-body">
                        <!-- Content sudah ada di atas -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>