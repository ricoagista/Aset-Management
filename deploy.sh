#!/bin/bash

# Automated Deployment Script for Aset Pribadi
# Usage: chmod +x deploy.sh && sudo ./deploy.sh

set -e

echo "🚀 Starting Aset Pribadi Deployment..."

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
    print_error "This script should not be run as root. Use sudo when needed."
    exit 1
fi

# Detect OS
if [[ -f /etc/os-release ]]; then
    . /etc/os-release
    OS=$NAME
    VER=$VERSION_ID
else
    print_error "Cannot detect OS version"
    exit 1
fi

print_status "Detected OS: $OS $VER"

# Step 1: Update System
print_status "Updating system packages..."
if [[ $OS == *"Ubuntu"* ]] || [[ $OS == *"Debian"* ]]; then
    sudo apt update && sudo apt upgrade -y
elif [[ $OS == *"CentOS"* ]] || [[ $OS == *"Red Hat"* ]]; then
    if [[ $VER -ge 8 ]]; then
        sudo dnf update -y
    else
        sudo yum update -y
    fi
fi

# Step 2: Install Dependencies
print_status "Installing web server and PHP..."
if [[ $OS == *"Ubuntu"* ]] || [[ $OS == *"Debian"* ]]; then
    sudo apt install -y nginx mysql-server php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd unzip git curl
elif [[ $OS == *"CentOS"* ]] || [[ $OS == *"Red Hat"* ]]; then
    if [[ $VER -ge 8 ]]; then
        sudo dnf install -y nginx mysql-server php php-fpm php-mysqlnd php-mbstring php-xml php-curl php-zip php-gd unzip git curl
    else
        sudo yum install -y nginx mysql-server php php-fpm php-mysqlnd php-mbstring php-xml php-curl php-zip php-gd unzip git curl
    fi
fi

# Step 3: Start Services
print_status "Starting services..."
sudo systemctl start nginx
sudo systemctl start mysql
sudo systemctl start php*-fpm
sudo systemctl enable nginx
sudo systemctl enable mysql
sudo systemctl enable php*-fpm

# Step 4: Configure MySQL
print_status "Configuring MySQL..."
read -p "Enter MySQL root password (leave empty for new installation): " MYSQL_ROOT_PASS
read -p "Enter new database password for aset_user: " DB_PASS

if [[ -z "$MYSQL_ROOT_PASS" ]]; then
    # New installation
    sudo mysql_secure_installation
    read -p "Enter MySQL root password you just set: " MYSQL_ROOT_PASS
fi

# Create database and user
mysql -u root -p$MYSQL_ROOT_PASS << EOF
CREATE DATABASE IF NOT EXISTS aset_pribadi;
CREATE USER IF NOT EXISTS 'aset_user'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON aset_pribadi.* TO 'aset_user'@'localhost';
FLUSH PRIVILEGES;
EOF

print_status "Database created successfully!"

# Step 5: Setup Application
print_status "Setting up application..."
read -p "Enter domain name (or press Enter to use server IP): " DOMAIN_NAME

if [[ -z "$DOMAIN_NAME" ]]; then
    DOMAIN_NAME=$(curl -s ifconfig.me)
    print_warning "Using server IP: $DOMAIN_NAME"
fi

# Create web directory
sudo mkdir -p /var/www/aset-pribadi

# Check if we're in the project directory
if [[ -f "index.php" && -f "config/database.php" ]]; then
    print_status "Copying files from current directory..."
    sudo cp -r . /var/www/aset-pribadi/
else
    print_status "Project files not found in current directory."
    read -p "Enter Git repository URL (or path to project files): " REPO_URL
    
    if [[ $REPO_URL == http* ]]; then
        cd /var/www
        sudo git clone $REPO_URL aset-pribadi
    else
        sudo cp -r $REPO_URL /var/www/aset-pribadi/
    fi
fi

# Set permissions
print_status "Setting file permissions..."
sudo chown -R www-data:www-data /var/www/aset-pribadi
sudo chmod -R 755 /var/www/aset-pribadi
sudo mkdir -p /var/www/aset-pribadi/uploads /var/www/aset-pribadi/logs
sudo chmod -R 777 /var/www/aset-pribadi/uploads
sudo chmod -R 777 /var/www/aset-pribadi/logs

# Configure environment
print_status "Configuring environment..."
cd /var/www/aset-pribadi

if [[ ! -f .env ]]; then
    sudo cp .env.example .env
fi

sudo tee .env > /dev/null << EOF
DB_HOST=localhost
DB_NAME=aset_pribadi
DB_USER=aset_user
DB_PASS=$DB_PASS
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
EOF

# Import database
print_status "Importing database..."
if [[ -f database/aset_pribadi.sql ]]; then
    mysql -u aset_user -p$DB_PASS aset_pribadi < database/aset_pribadi.sql
    print_status "Database imported successfully!"
else
    print_warning "Database file not found. Please import manually later."
fi

# Step 6: Configure Nginx
print_status "Configuring Nginx..."
sudo tee /etc/nginx/sites-available/aset-pribadi > /dev/null << EOF
server {
    listen 80;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    root /var/www/aset-pribadi;
    index index.php index.html;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # Hide server info
    server_tokens off;
    
    # File upload limit
    client_max_body_size 10M;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php*-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\.(env|git|htaccess) {
        deny all;
    }
    
    location ~ \.(log|sql)\$ {
        deny all;
    }
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Logs
    access_log /var/log/nginx/aset-pribadi.access.log;
    error_log /var/log/nginx/aset-pribadi.error.log;
}
EOF

# Enable site
sudo ln -sf /etc/nginx/sites-available/aset-pribadi /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test and restart nginx
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php*-fpm

# Step 7: Setup Firewall
print_status "Configuring firewall..."
if command -v ufw &> /dev/null; then
    sudo ufw --force reset
    sudo ufw default deny incoming
    sudo ufw default allow outgoing
    sudo ufw allow ssh
    sudo ufw allow 'Nginx Full'
    sudo ufw --force enable
elif command -v firewall-cmd &> /dev/null; then
    sudo firewall-cmd --permanent --add-service=http
    sudo firewall-cmd --permanent --add-service=https
    sudo firewall-cmd --permanent --add-service=ssh
    sudo firewall-cmd --reload
fi

# Step 8: Setup SSL (Optional)
read -p "Do you want to setup SSL with Let's Encrypt? (y/n): " SETUP_SSL

if [[ $SETUP_SSL == "y" || $SETUP_SSL == "Y" ]]; then
    print_status "Installing Let's Encrypt..."
    if [[ $OS == *"Ubuntu"* ]] || [[ $OS == *"Debian"* ]]; then
        sudo apt install -y certbot python3-certbot-nginx
    elif [[ $OS == *"CentOS"* ]] || [[ $OS == *"Red Hat"* ]]; then
        if [[ $VER -ge 8 ]]; then
            sudo dnf install -y certbot python3-certbot-nginx
        else
            sudo yum install -y certbot python3-certbot-nginx
        fi
    fi
    
    read -p "Enter email for Let's Encrypt: " LE_EMAIL
    sudo certbot --nginx -d $DOMAIN_NAME -d www.$DOMAIN_NAME --email $LE_EMAIL --agree-tos --non-interactive
    
    # Setup auto renewal
    (sudo crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | sudo crontab -
fi

# Step 9: Setup Backup
print_status "Setting up backup system..."
sudo mkdir -p /backup/aset-pribadi

sudo tee /usr/local/bin/backup-aset.sh > /dev/null << EOF
#!/bin/bash
DATE=\$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/aset-pribadi"
DB_NAME="aset_pribadi"
DB_USER="aset_user"
DB_PASS="$DB_PASS"

mkdir -p \$BACKUP_DIR

# Database backup
mysqldump -u \$DB_USER -p\$DB_PASS \$DB_NAME > \$BACKUP_DIR/db_\$DATE.sql

# Files backup
tar -czf \$BACKUP_DIR/files_\$DATE.tar.gz /var/www/aset-pribadi

# Keep only last 7 days
find \$BACKUP_DIR -name "*.sql" -mtime +7 -delete
find \$BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
EOF

sudo chmod +x /usr/local/bin/backup-aset.sh

# Add to crontab
(sudo crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-aset.sh") | sudo crontab -

# Step 10: Final checks
print_status "Running final checks..."

# Check services
if sudo systemctl is-active --quiet nginx; then
    print_status "✓ Nginx is running"
else
    print_error "✗ Nginx is not running"
fi

if sudo systemctl is-active --quiet mysql; then
    print_status "✓ MySQL is running"
else
    print_error "✗ MySQL is not running"
fi

if sudo systemctl is-active --quiet php*-fpm; then
    print_status "✓ PHP-FPM is running"
else
    print_error "✗ PHP-FPM is not running"
fi

# Test web server
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [[ $HTTP_CODE -eq 200 ]]; then
    print_status "✓ Web server is responding"
else
    print_warning "✗ Web server test failed (HTTP $HTTP_CODE)"
fi

print_status "🎉 Deployment completed!"
echo ""
echo -e "${BLUE}================================${NC}"
echo -e "${GREEN}   DEPLOYMENT SUMMARY${NC}"
echo -e "${BLUE}================================${NC}"
echo -e "Website URL: ${YELLOW}http://$DOMAIN_NAME${NC}"
if [[ $SETUP_SSL == "y" || $SETUP_SSL == "Y" ]]; then
    echo -e "SSL URL: ${YELLOW}https://$DOMAIN_NAME${NC}"
fi
echo -e "Login URL: ${YELLOW}http://$DOMAIN_NAME/login.php${NC}"
echo -e "Default Login: ${YELLOW}admin / admin123${NC}"
echo -e "Database: ${YELLOW}aset_pribadi${NC}"
echo -e "DB User: ${YELLOW}aset_user${NC}"
echo ""
echo -e "${YELLOW}Important files:${NC}"
echo -e "- Config: /var/www/aset-pribadi/.env"
echo -e "- Logs: /var/log/nginx/aset-pribadi.*"
echo -e "- Backup: /backup/aset-pribadi/"
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo -e "1. Change default admin password"
echo -e "2. Configure your domain DNS"
echo -e "3. Test all features"
echo -e "4. Setup monitoring"
echo ""
echo -e "${RED}Security reminder:${NC}"
echo -e "- Update all default passwords"
echo -e "- Configure fail2ban"
echo -e "- Regular security updates"
echo -e "${BLUE}================================${NC}"