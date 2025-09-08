<?php
// Security Headers (hanya jika belum di-set)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// Session Security
if (session_status() === PHP_SESSION_ACTIVE) {
    if (function_exists('secureSession')) {
        try {
            secureSession();
        } catch (Exception $e) {
            // Log error tapi jangan stop execution
            error_log("Session security error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Aset Pribadi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-action {
            margin: 2px;
        }
        .status-baik { color: #28a745; }
        .status-rusak-ringan { color: #ffc107; }
        .status-rusak-berat { color: #dc3545; }
        .status-hilang { color: #6c757d; }
        
        /* Style untuk gambar */
        .img-thumbnail {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
        }
        .img-fluid {
            max-width: 300px;
            max-height: 300px;
            object-fit: cover;
        }
        .no-image {
            width: 150px;
            height: 150px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .table-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .btn-action {
                padding: 0.25rem 0.5rem;
                margin: 1px;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .mobile-card {
                display: none;
            }
            
            .mobile-list {
                display: block;
            }
            
            .mobile-item {
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 15px;
                background: white;
            }
            
            .mobile-item-header {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }
            
            .mobile-item-image {
                width: 60px;
                height: 60px;
                object-fit: cover;
                border-radius: 8px;
                margin-right: 15px;
            }
            
            .mobile-item-title {
                font-weight: bold;
                font-size: 1.1rem;
                color: #333;
                margin-bottom: 5px;
            }
            
            .mobile-item-subtitle {
                color: #6c757d;
                font-size: 0.9rem;
            }
            
            .mobile-item-details {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .mobile-detail-item {
                font-size: 0.9rem;
            }
            
            .mobile-detail-label {
                font-weight: 600;
                color: #495057;
                display: block;
            }
            
            .mobile-actions {
                display: flex;
                gap: 5px;
                justify-content: flex-end;
            }
            
            .img-thumbnail {
                max-width: 100px;
                max-height: 100px;
            }
            
            .img-fluid {
                max-width: 100%;
                max-height: 250px;
            }
            
            .no-image {
                width: 100px;
                height: 100px;
            }
            
            .stats-card {
                margin-bottom: 10px;
            }
            
            .stats-card .card-body {
                padding: 15px;
            }
            
            .stats-card h4 {
                font-size: 1.5rem;
            }
            
            /* Form responsive */
            .form-label {
                font-weight: 600;
                margin-bottom: 5px;
            }
            
            .form-control, .form-select {
                font-size: 16px; /* Prevent zoom on iOS */
            }
            
            /* Navigation responsive */
            .navbar-nav {
                margin-top: 10px;
            }
            
            .navbar-nav .nav-link {
                padding: 8px 0;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            
            /* Table hide on mobile */
            .desktop-table {
                display: none;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-list {
                display: none;
            }
            
            .desktop-table {
                display: block;
            }
        }
        
        /* Touch-friendly buttons */
        .btn {
            min-height: 44px;
            padding: 10px 15px;
        }
        
        .btn-sm {
            min-height: 38px;
            padding: 8px 12px;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Loading states */
        .btn:active {
            transform: scale(0.98);
        }
        
        /* Focus states for accessibility */
        .btn:focus, .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Hover effects */
        .mobile-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        
        /* Better spacing for mobile */
        @media (max-width: 576px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .mobile-item {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .mobile-item-details {
                gap: 8px;
            }
            
            .btn {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-box"></i> Aset Pribadi
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav me-auto">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link d-none d-md-block" href="tambah.php">
                        <i class="fas fa-plus"></i> Tambah Aset
                    </a>
                </div>
                <div class="navbar-nav">
                    <?php 
                    // Check if auth_helper is already included
                    if (!function_exists('getLoggedInUser')) {
                        require_once __DIR__ . '/auth_helper.php';
                    }
                    $user = getLoggedInUser(); 
                    if ($user): 
                    ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['nama_lengkap']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?= htmlspecialchars($user['username']) ?></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        endif; 
        ?>