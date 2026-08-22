# Image to Word Generator

Aplikasi berbasis web modern untuk mengonversi dan menggabungkan beberapa dokumen gambar (JPG, PNG) menjadi satu berkas laporan Microsoft Word (.docx) secara terstruktur. Dirancang dengan standar korporat, mendukung drag & drop, penataan ulang urutan halaman secara dinamis, serta sistem riwayat unduhan anonim berbasis cookie.

---

## 🛠️ Tech Stack

- Backend: Laravel (PHP 8.4)
- Database: MySQL
- Document Engine: PHPWord (phpoffice/phpword)
- Frontend UI: Blade Templates, Tailwind CSS (Corporate Theme)
- Interactivity: Alpine.js & Sortable.js
- Environment: Docker & Docker Compose

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

Pastikan komputer Anda sudah terinstal Docker dan Docker Compose.

1. Clone atau Letakkan Project di Direktori Kerja Anda
   Buka terminal (PowerShell / Terminal) di folder root project.

2. Jalankan Docker Compose
   Bangkitkan container aplikasi dan database MySQL:
   docker-compose up -d --build

3. Install Dependensi & Build Assets (Vite)
   Jalankan perintah berikut di dalam container untuk menyiapkan vendor PHP dan kompilasi frontend:
   docker-compose exec app composer install
   docker-compose exec app npm install
   docker-compose exec app npm run build

4. Jalankan Migrasi Database
   docker-compose exec app php artisan migrate

5. Akses Aplikasi
   Buka browser Anda dan akses: http://localhost:8000

---

## 📋 Fitur Utama

- Enterprise UI/UX: Tampilan profesional bernuansa slate/navy yang bersih dan responsif.
- Drag & Drop Upload: Unggah banyak gambar sekaligus dengan batasan format dan ukuran yang aman.
- Interactive Reorder: Geser kartu pratinjau gambar untuk mengatur urutan halaman laporan Word secara instan (2 gambar per halaman).
- Smart Aspect Ratio: Gambar diatur secara otomatis agar pas di dalam dokumen Word tanpa merusak proporsi aslinya (tidak gepeng).
- Anonymous History: Riwayat unduhan dokumen tersimpan secara privat untuk setiap perangkat tanpa memerlukan proses login.
- Interactive Guide: Panel panduan penggunaan yang dapat dibuka dan ditutup dengan mudah.
