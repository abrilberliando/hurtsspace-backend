# 🐳 Catatan Penggunaan Docker (hs-be)

Dokumen ini berisi daftar perintah (command) penting untuk menjalankan dan mengelola project Laravel `hs-be` menggunakan Docker murni (tanpa Laravel Sail). 

Semua perintah di bawah ini harus dijalankan di dalam direktori project:
`cd /Users/abrilberliando/Dev/hspace/hs-be`

---

## ⭐️ Top 5 Perintah Paling Sering Dipakai (Daily Use)

Jika Anda hanya ingin mengingat yang paling penting, cukup ingat 5 perintah ini:

1. **Jalankan aplikasi (Local):** `docker-compose up -d`
2. **Matikan aplikasi:** `docker-compose down`
3. **Masuk ke terminal Laravel:** `docker-compose exec app bash` *(sangat disarankan agar tidak perlu mengetik panjang-panjang saat ngoding)*
4. **Lihat log/error:** `docker-compose logs -f app`
5. **Manajemen Database (Migrasi):**
   - **Migrasi Biasa:** `docker-compose exec app php artisan migrate`
     *(Hanya menjalankan file migrasi baru tanpa menghapus data yang sudah ada di tabel).*
   - **Migrasi & Seed (Reset Data):** `docker-compose exec app php artisan migrate:fresh --seed`
     *(Menghapus/Drop SEMUA tabel dari awal, lalu membuat ulang tabel, dan mengisi data bohongan/awal dari file Seeder).*

---

## 1. Manajemen Server (Container)

| Perintah | Deskripsi |
| --- | --- |
| `docker-compose up -d` | Menjalankan server di background (Detached mode). |
| `docker-compose down` | Menghentikan semua server dan menghapus container. |
| `docker-compose stop` | Menghentikan sementara server tanpa menghapus container. |
| `docker-compose start` | Menjalankan kembali server yang di-stop. |
| `docker-compose restart` | Merestart server. |
| `docker-compose up -d --build` | Membangun ulang (rebuild) image dari awal. **Wajib dijalankan jika Anda mengubah isi `Dockerfile`.** |

---

## 2. Menjalankan Perintah Laravel (Artisan)

Karena aplikasi berjalan di dalam container `app`, setiap perintah artisan harus diawali dengan `docker-compose exec app`.

| Perintah | Deskripsi |
| --- | --- |
| `docker-compose exec app php artisan migrate` | Menjalankan migrasi database. |
| `docker-compose exec app php artisan migrate:fresh --seed` | Mereset seluruh database dan menjalankan seeder. |
| `docker-compose exec app php artisan make:controller NamaController` | Membuat controller baru. |
| `docker-compose exec app php artisan make:model NamaModel -m` | Membuat model beserta filenya migrasinya. |
| `docker-compose exec app php artisan key:generate` | Membuat ulang APP_KEY di file `.env`. |
| `docker-compose exec app php artisan optimize:clear` | Menghapus semua cache Laravel (config, view, route). |

---

## 3. Menjalankan Perintah PHP / Composer

Sama seperti Artisan, perintah Composer juga dijalankan di dalam container `app`.

| Perintah | Deskripsi |
| --- | --- |
| `docker-compose exec app composer install` | Menginstall semua package di `composer.json`. |
| `docker-compose exec app composer require nama/package` | Menambahkan library PHP baru. |
| `docker-compose exec app composer dump-autoload` | Merefresh autoload composer. |
| `docker-compose exec app php -v` | Mengecek versi PHP yang berjalan di dalam container. |

---

## 4. Debugging & Logs

Berguna ketika Anda mengalami error atau 500 Internal Server Error.

| Perintah | Deskripsi |
| --- | --- |
| `docker-compose logs -f` | Melihat log secara real-time dari semua container (PHP & MySQL). |
| `docker-compose logs -f app` | Melihat log khusus dari aplikasi (Apache/PHP). |
| `docker-compose logs -f mysql` | Melihat log khusus dari database MySQL. |

---

## 5. Akses Terminal Langsung (Masuk ke Container)

Jika Anda capek mengetik awalan `docker-compose exec app` berulang kali, Anda bisa langsung "masuk" ke dalam terminal Linux dari container tersebut.

**Cara Masuk:**
```bash
docker-compose exec app bash
```

Setelah Anda masuk, tampilan terminal akan berubah (berada di dalam `/var/www/html`). Di sini, Anda bisa mengetik perintah Laravel dengan normal seperti:
```bash
php artisan migrate
composer install
```

**Cara Keluar:**
Ketik `exit` lalu tekan Enter.

---

## 6. Railway Deployment Notes
- `Dockerfile` di repository ini sudah disesuaikan untuk deploy ke **Railway** (menggunakan Apache).
- Railway akan otomatis membaca `Dockerfile` dan menjalankan `composer install --no-dev`.
- Database di Railway berdiri secara terpisah (jangan gunakan container MySQL lokal ini untuk prod). Hubungkan lewat Environment Variables.
