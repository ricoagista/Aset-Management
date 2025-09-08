<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_helper.php';
require_once 'includes/security_helper.php';

// Secure session
secureSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF_ATTACK', 'Invalid CSRF token in login form');
        $error = 'Security token mismatch. Please try again.';
    }
    // Rate Limiting
    elseif (!checkRateLimit('login_attempt', 5, 900)) { // 5 attempts per 15 minutes
        logSecurityEvent('RATE_LIMIT_EXCEEDED', 'Too many login attempts');
        $error = 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.';
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi!';
        } else {
            try {
                $stmt = executeQuery($pdo, "SELECT id, username, password, nama_lengkap FROM users WHERE username = ? OR email = ?", [$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && verifyPassword($password, $user['password'])) {
                    // Reset rate limit on successful login
                    unset($_SESSION['rate_limit']['login_attempt_' . $_SERVER['REMOTE_ADDR']]);
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $_SESSION['last_regeneration'] = time();
                    
                    logSecurityEvent('LOGIN_SUCCESS', 'User: ' . $user['username']);
                    
                    header('Location: index.php');
                    exit;
                } else {
                    logSecurityEvent('LOGIN_FAILED', 'Username: ' . $username);
                    $error = 'Username atau password salah!';
                }
            } catch (Exception $e) {
                logSecurityEvent('LOGIN_ERROR', $e->getMessage());
                $error = 'Terjadi kesalahan sistem!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Manajemen Aset Pribadi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e1e5e9;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-box"></i>
            </div>
            <h1>Aset Pribadi</h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php outputCSRFToken(); ?>
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i> Username atau Email
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?= escapeOutput($_POST['username'] ?? '') ?>" required maxlength="50">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required maxlength="255">
            </div>
            
            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                Default: username <strong>admin</strong>, password <strong>admin123</strong>
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>