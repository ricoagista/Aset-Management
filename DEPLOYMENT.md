# Deployment ke VPS - Manajemen Aset Pribadi

Panduan lengkap untuk deploy aplikasi Manajemen Aset Pribadi ke VPS (Virtual Private Server).

## Prerequisites

- VPS dengan Ubuntu 20.04/22.04 atau CentOS 7/8
- Domain name (opsional, bisa pakai IP)
- Access SSH ke VPS
- Minimal 1GB RAM dan 20GB Storage

## Step 1: Persiapan VPS

### 1.1 Update System
```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
# atau untuk CentOS 8+
sudo dnf update -y
```

### 1.2 Install Dependencies
```bash
# Ubuntu/Debian
sudo apt install -y nginx mysql-server php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd unzip git

# CentOS 8+
sudo dnf install -y nginx mysql-server php php-fpm php-mysqlnd php-mbstring php-xml php-curl php-zip php-gd unzip git
```

## Step 2: Konfigurasi MySQL

### 2.1 Setup MySQL
```bash
# Start dan enable MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure installation
sudo mysql_secure_installation
```

### 2.2 Buat Database dan User
```bash
# Login ke MySQL
sudo mysql -u root -p

# Buat database dan user
CREATE DATABASE aset_pribadi;
CREATE USER 'aset_user'@'localhost' IDENTIFIED BY 'password_kuat_123';
GRANT ALL PRIVILEGES ON aset_pribadi.* TO 'aset_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 3: Upload dan Setup Aplikasi

### 3.1 Upload Code
```bash
# Clone atau upload ke direktori web
cd /var/www
sudo git clone https://github.com/username/aset-pribadi.git
# atau upload via SCP/SFTP

# Set permissions
sudo chown -R www-data:www-data /var/www/aset-pribadi
sudo chmod -R 755 /var/www/aset-pribadi
sudo chmod -R 777 /var/www/aset-pribadi/uploads
sudo chmod -R 777 /var/www/aset-pribadi/logs
```

### 3.2 Konfigurasi Environment
```bash
# Copy dan edit file .env
cd /var/www/aset-pribadi
sudo cp .env.example .env
sudo nano .env
```

Edit file `.env`:
```bash
DB_HOST=localhost
DB_NAME=aset_pribadi
DB_USER=aset_user
DB_PASS=password_kuat_123
DB_CHARSET=utf8mb4

# Security
SESSION_LIFETIME=3600
UPLOAD_MAX_SIZE=5242880
ALLOWED_FILE_TYPES=image/jpeg,image/png,image/gif,image/webp

# Rate Limiting
LOGIN_MAX_ATTEMPTS=5
LOGIN_LOCKOUT_TIME=900

# Paths
UPLOAD_PATH=uploads/
LOG_PATH=logs/
```

### 3.3 Import Database
```bash
# Import struktur database
mysql -u aset_user -p aset_pribadi < database/aset_pribadi.sql
```

## Step 4: Konfigurasi Nginx

### 4.1 Buat Virtual Host
```bash
sudo nano /etc/nginx/sites-available/aset-pribadi
```

Isi file konfigurasi:
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;  # Ganti dengan domain Anda
    root /var/www/aset-pribadi;
    index index.php index.html;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Hide Nginx version
    server_tokens off;
    
    # File upload limit
    client_max_body_size 10M;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\.(env|git|htaccess) {
        deny all;
    }
    
    location ~ \.(log|sql)$ {
        deny all;
    }
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Logs
    access_log /var/log/nginx/aset-pribadi.access.log;
    error_log /var/log/nginx/aset-pribadi.error.log;
}
```

### 4.2 Enable Site
```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/aset-pribadi /etc/nginx/sites-enabled/

# Test konfigurasi
sudo nginx -t

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

## Step 5: SSL dengan Let's Encrypt (Opsional)

### 5.1 Install Certbot
```bash
# Ubuntu/Debian
sudo apt install -y certbot python3-certbot-nginx

# CentOS 8+
sudo dnf install -y certbot python3-certbot-nginx
```

### 5.2 Generate SSL Certificate
```bash
# Generate certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Setup auto renewal
sudo crontab -e
# Tambahkan baris ini:
0 12 * * * /usr/bin/certbot renew --quiet
```

## Step 6: Konfigurasi Firewall

### 6.1 UFW (Ubuntu)
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### 6.2 Firewalld (CentOS)
```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --reload
```

## Step 7: Optimasi dan Monitoring

### 7.1 PHP Optimization
```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

Edit pengaturan:
```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
max_input_vars = 3000
```

### 7.2 Setup Monitoring
```bash
# Install htop untuk monitoring
sudo apt install htop

# Setup log rotation
sudo nano /etc/logrotate.d/aset-pribadi
```

Isi logrotate:
```
/var/www/aset-pribadi/logs/*.log {
    weekly
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    copytruncate
}
```

## Step 8: Backup Setup

### 8.1 Database Backup Script
```bash
sudo nano /usr/local/bin/backup-aset.sh
```

Isi script:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/aset-pribadi"
DB_NAME="aset_pribadi"
DB_USER="aset_user"
DB_PASS="password_kuat_123"

mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/aset-pribadi

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

```bash
# Set permissions dan add to crontab
sudo chmod +x /usr/local/bin/backup-aset.sh
sudo crontab -e
# Tambahkan untuk backup harian jam 2 pagi:
0 2 * * * /usr/local/bin/backup-aset.sh
```

## Step 9: Testing dan Troubleshooting

### 9.1 Test Website
- Akses http://your-domain.com atau http://your-vps-ip
- Test login dengan: username `admin`, password `admin123`
- Test upload gambar
- Test semua fitur CRUD

### 9.2 Common Issues

**502 Bad Gateway:**
```bash
sudo systemctl status php8.1-fpm
sudo systemctl restart php8.1-fpm nginx
```

**Permission Denied:**
```bash
sudo chown -R www-data:www-data /var/www/aset-pribadi
sudo chmod -R 755 /var/www/aset-pribadi
```

**Database Connection:**
```bash
# Test koneksi database
mysql -u aset_user -p aset_pribadi
```

### 9.3 Log Locations
- Nginx: `/var/log/nginx/`
- PHP: `/var/log/php8.1-fpm.log`
- Application: `/var/www/aset-pribadi/logs/`
- MySQL: `/var/log/mysql/`

## Step 10: Security Hardening

### 10.1 Disable Root Login
```bash
sudo nano /etc/ssh/sshd_config
# Set: PermitRootLogin no
sudo systemctl restart sshd
```

### 10.2 Fail2ban (Optional)
```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 10.3 Regular Updates
```bash
# Setup auto updates (Ubuntu)
sudo apt install unattended-upgrades
sudo dpkg-reconfigure unattended-upgrades
```

## Maintenance

### Daily Tasks
- Check disk space: `df -h`
- Check memory: `free -h`
- Check logs: `tail -f /var/log/nginx/aset-pribadi.error.log`

### Weekly Tasks
- Update system packages
- Check backup files
- Review security logs

### Monthly Tasks
- Update application if needed
- Database optimization
- Security audit

## Production URLs

- **Website**: http://your-domain.com
- **Admin Login**: http://your-domain.com/login.php
- **Security Monitor**: http://your-domain.com/security_monitor.php (admin only)

## Support

Jika mengalami masalah:
1. Check error logs
2. Verify file permissions
3. Test database connection
4. Check service status: `sudo systemctl status nginx mysql php8.1-fpm`

---

**Note**: Ganti `your-domain.com` dengan domain aktual Anda, dan `password_kuat_123` dengan password yang kuat.