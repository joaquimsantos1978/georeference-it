#!/bin/bash
# georeference.it — server setup script
# Ubuntu 24.04, MariaDB 10.11, PHP 8.2, Nginx, Certbot
# Run as root: bash setup.sh

set -e
export DEBIAN_FRONTEND=noninteractive

echo "============================================"
echo " georeference.it — server setup"
echo "============================================"

# ── 1. System update ─────────────────────────────────────────────────────────
echo "[1/9] Updating system..."
apt-get update -qq && apt-get upgrade -y -qq

# ── 2. Base packages ─────────────────────────────────────────────────────────
echo "[2/9] Installing base packages..."
apt-get install -y -qq \
    git curl unzip zip wget gnupg2 ca-certificates lsb-release \
    software-properties-common apt-transport-https ufw fail2ban \
    htop screen ntp

# ── 3. PHP 8.2 ───────────────────────────────────────────────────────────────
echo "[3/9] Installing PHP 8.2..."
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
apt-get update -qq
apt-get install -y -qq \
    php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl \
    php8.2-gd php8.2-redis php8.2-opcache

# PHP production settings
cat > /etc/php/8.2/fpm/conf.d/99-georef.ini << 'EOF'
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 512M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
EOF

cp /etc/php/8.2/fpm/conf.d/99-georef.ini /etc/php/8.2/cli/conf.d/99-georef.ini

# ── 4. Composer ──────────────────────────────────────────────────────────────
echo "[4/9] Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ── 5. Node.js 20 ────────────────────────────────────────────────────────────
echo "[5/9] Installing Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - > /dev/null 2>&1
apt-get install -y -qq nodejs

# ── 6. MariaDB 10.11 ─────────────────────────────────────────────────────────
echo "[6/9] Installing MariaDB 10.11..."
curl -LsSo /tmp/mariadb_repo.sh https://downloads.mariadb.com/MariaDB/mariadb_repo_setup
bash /tmp/mariadb_repo.sh --mariadb-server-version=10.11 > /dev/null 2>&1
apt-get install -y -qq mariadb-server mariadb-client

# MariaDB tuning for 48GB RAM — large import optimised
cat > /etc/mysql/mariadb.conf.d/99-georef.cnf << 'EOF'
[mysqld]
# Buffer pool: 75% of RAM for large imports
innodb_buffer_pool_size         = 36G
innodb_buffer_pool_instances    = 8
innodb_log_file_size            = 2G
innodb_log_buffer_size          = 256M
innodb_flush_log_at_trx_commit  = 2
innodb_flush_method             = O_DIRECT

# LOAD DATA LOCAL INFILE
local_infile                    = 1

# Large imports
max_allowed_packet              = 256M
bulk_insert_buffer_size         = 256M
key_buffer_size                 = 256M
sort_buffer_size                = 64M
read_buffer_size                = 16M
read_rnd_buffer_size            = 32M
join_buffer_size                = 32M
tmp_table_size                  = 2G
max_heap_table_size             = 2G
thread_cache_size               = 32
table_open_cache                = 4000

# Query cache off (MariaDB 10.11 default)
query_cache_type                = 0
query_cache_size                = 0

# Connection
max_connections                 = 200
wait_timeout                    = 600
interactive_timeout             = 600

# Character set
character-set-server            = utf8mb4
collation-server                = utf8mb4_unicode_ci

[mysql]
local_infile                    = 1

[client]
default-character-set           = utf8mb4
EOF

systemctl restart mariadb

# Secure MariaDB + create database
echo "[6/9] Configuring MariaDB..."
read -s -p "  Enter password for MariaDB 'georef' user: " DB_PASS
echo ""

mysql -u root << SQL
CREATE DATABASE IF NOT EXISTS georeference_it CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'georef'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON georeference_it.* TO 'georef'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "  Database created."

# ── 7. Nginx ─────────────────────────────────────────────────────────────────
echo "[7/9] Installing Nginx..."
apt-get install -y -qq nginx

cat > /etc/nginx/sites-available/georef << 'NGINXCONF'
server {
    listen 80;
    listen [::]:80;
    server_name georeference.it www.georeference.it;
    root /var/www/georef/public;
    index index.php;

    client_max_body_size 100M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXCONF

ln -sf /etc/nginx/sites-available/georef /etc/nginx/sites-enabled/georef
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# ── 8. Deploy application ────────────────────────────────────────────────────
echo "[8/9] Deploying application..."
mkdir -p /var/www
git clone https://github.com/joaquimsantos1978/georeference-it.git /var/www/georef
cd /var/www/georef

# .env
cp .env.example .env
sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env
sed -i "s/APP_URL=http:\/\/localhost/APP_URL=https:\/\/georeference.it/" .env
sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/" .env
sed -i "s/DB_DATABASE=laravel/DB_DATABASE=georeference_it/" .env
sed -i "s/DB_USERNAME=root/DB_USERNAME=georef/" .env
sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env

# Dependencies
composer install --no-dev --optimize-autoloader --no-interaction -q
npm ci && npm run build

# Laravel setup
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chown -R www-data:www-data /var/www/georef
chmod -R 755 /var/www/georef/storage
chmod -R 755 /var/www/georef/bootstrap/cache

# ── 9. Firewall + SSL ────────────────────────────────────────────────────────
echo "[9/9] Firewall + SSL..."
ufw --force enable
ufw allow ssh
ufw allow 'Nginx Full'

apt-get install -y -qq certbot python3-certbot-nginx
echo ""
echo "  Run after DNS is pointed to this server:"
echo "  certbot --nginx -d georeference.it -d www.georeference.it"

# ── Done ─────────────────────────────────────────────────────────────────────
echo ""
echo "============================================"
echo " Setup complete!"
echo " Next steps:"
echo " 1. Point georeference.it DNS → 13.140.184.232"
echo " 2. Edit /var/www/georef/.env (APP_KEY, mail, GBIF credentials)"
echo " 3. certbot --nginx -d georeference.it -d www.georeference.it"
echo " 4. php artisan gbif:import-download <key> --file=..."
echo "============================================"
