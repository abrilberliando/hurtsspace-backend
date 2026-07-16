#!/bin/bash
# Jalankan migrasi otomatis
echo "Menjalankan migrasi database..."
php artisan migrate --force

# Nyalakan server apache
echo "Menyalakan web server..."
apache2-foreground
