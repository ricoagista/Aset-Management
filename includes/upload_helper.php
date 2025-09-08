<?php
require_once __DIR__ . '/security_helper.php';

function uploadGambar($file, $namaBarang) {
    $uploadDir = 'uploads/';
    
    // Buat direktori jika belum ada
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file upload dengan security checks
    $errors = validateFileUpload($file);
    if (!empty($errors)) {
        throw new Exception(implode(', ', $errors));
    }
    
    // Sanitize filename dengan lebih ketat
    $originalName = sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME));
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Prevent path traversal
    $originalName = str_replace(['../', '../', '..\\', '..'], '', $originalName);
    $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
    
    // Generate secure filename
    $fileName = date('YmdHis') . '_' . substr(md5($originalName . uniqid()), 0, 8) . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Additional security: Check file content
    $file_content = file_get_contents($file['tmp_name']);
    if (strpos($file_content, '<?php') !== false || strpos($file_content, '<?=') !== false || strpos($file_content, '<script') !== false) {
        logSecurityEvent('MALICIOUS_FILE_UPLOAD', 'Suspicious content detected in file: ' . $file['name']);
        throw new Exception('File contains suspicious content');
    }
    
    // Upload file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Gagal menyimpan file');
    }
    
    // Set secure permissions
    chmod($filePath, 0644);
    
    logSecurityEvent('FILE_UPLOAD_SUCCESS', 'File: ' . $fileName);
    return $fileName;
}

function hapusGambar($namaFile) {
    if ($namaFile && file_exists('uploads/' . $namaFile)) {
        unlink('uploads/' . $namaFile);
    }
}

function tampilkanGambar($namaFile, $alt = '', $class = '') {
    if ($namaFile && file_exists('uploads/' . $namaFile)) {
        return '<img src="uploads/' . htmlspecialchars($namaFile) . '" alt="' . htmlspecialchars($alt) . '" class="' . $class . '">';
    }
    return '<div class="no-image ' . $class . '"><i class="fas fa-image text-muted"></i><br><small class="text-muted">Tidak ada gambar</small></div>';
}
?>