#!/bin/bash
# Matikan module MPM lain yang bentrok secara paksa
a2dismod mpm_event mpm_worker > /dev/null 2>&1 || true
a2enmod mpm_prefork > /dev/null 2>&1 || true

# Jalankan migrasi otomatis
echo "Menjalankan migrasi database..."
php artisan migrate --force

# Nyalakan server apache
echo "Menyalakan web server..."
apache2-foreground
