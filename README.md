# Image to Word Generator

A modern web application for converting and merging multiple image files (JPG, PNG) into a single, structured Microsoft Word (.docx) report. Built with corporate-grade design standards, supporting drag & drop uploads, dynamic page reordering, background document generation, and an anonymous cookie-based download history.

---

## 🛠️ Tech Stack

- **Backend**: Laravel (PHP 8.4)
- **Database**: MySQL 8.0
- **Queue**: Laravel Queue (database driver)
- **Document Engine**: PHPWord (phpoffice/phpword)
- **Frontend UI**: Blade Templates, Tailwind CSS (Corporate Theme)
- **Interactivity**: Alpine.js & Sortable.js
- **Environment**: Docker & Docker Compose
- **DB Admin (dev only)**: Adminer

---

## 🏗️ Architecture Note

Document generation runs as a **background queue job**, not synchronously within the HTTP request. This is required to reliably support large batches (up to 200 images per document) without hitting web server timeouts.

Flow: `Upload → dispatch job → queue-worker processes it → poll status → download when ready`

This means the `queue-worker` container **must be running** for document generation to work — see step 5 below.

---

## 🚀 Installation & Setup Guide

Make sure Docker and Docker Compose are installed on your machine.

1. **Clone or place the project** in your working directory.
   Open a terminal in the project root folder.

2. **Build and start the containers**
   This spins up the app, queue worker, MySQL, and Adminer:
```bash
   docker-compose up -d --build
```

3. **Install dependencies & build frontend assets**
   Run inside the `app` container:
```bash
   docker-compose exec app composer install
   docker-compose exec app npm install
   docker-compose exec app npm run build
```

4. **Run database migrations**
   This creates both the application tables and Laravel's internal `jobs` queue table:
```bash
   docker-compose exec app php artisan queue:table
   docker-compose exec app php artisan migrate
```

5. **Verify the queue worker is running**
   Document generation depends on this container. Check its logs:
```bash
   docker-compose logs -f queue-worker
```
   If it's not running, restart it with `docker-compose up -d queue-worker`.

6. **Access the application**
   - App: [http://localhost:8000](http://localhost:8000)
   - Adminer (database viewer): [http://localhost:8080](http://localhost:8080)
     - System: `MySQL`
     - Server: `mysql`
     - Username: `root`
     - Password: `root`
     - Database: `image_to_word`

---

## 📋 Key Features

- **Enterprise UI/UX** — a clean, responsive slate/navy corporate interface.
- **Drag & Drop Upload** — upload multiple images at once, with safe format and size limits.
- **Interactive Reorder** — drag preview cards to instantly reorder document pages (2 images per page).
- **Auto-Numbered Captions** — captions are automatically numbered (1, 2, 3...) and stay in sync with drag-and-drop order.
- **Smart Aspect Ratio** — images are automatically fitted into the document without distortion.
- **Background Processing** — supports up to 200 images per document without blocking the browser or timing out.
- **Anonymous History** — download history is stored privately per device, with no login required.
- **Interactive Guide** — a collapsible usage guide panel.

---

## ⚙️ Configuration

Image limits are set in `config/image_to_word.php`:

```php
return [
    'max_images'    => 200,
    'max_file_size' => 20480, // KB per file
];
```

If you raise `max_images` beyond current values, also check `docker/php/local.ini` — `post_max_size`, `upload_max_filesize`, and `max_file_uploads` need to accommodate the total upload size.

---

## 🐳 Useful Docker Commands

```bash
# View queue worker logs (check if jobs are being processed)
docker-compose logs -f queue-worker

# Restart the worker after code changes to Job/Service classes
docker-compose restart queue-worker

# Access app container shell
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan <command>
```