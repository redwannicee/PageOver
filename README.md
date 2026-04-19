# PageOver — Intelligent Project Analysis Platform

> Developed by **Md Redwan Rashid Nice**

PageOver is a web-based platform that analyzes any software project by uploading a ZIP file or providing a GitHub repository link. It detects programming languages, identifies frameworks, scans for bugs and security issues, estimates AI usage, checks deployment availability, and generates professional PDF reports — all without any heavy frameworks.

**Stack:** PHP 8+ · CSS3 · Vanilla JavaScript · Python 3 (PDF only)

---

## Features

| Feature | Description |
|---|---|
| Language Breakdown | Detects all languages with percentage charts |
| Platform Detection | Identifies 20+ frameworks from config files |
| Bug Detection | Static analysis for XSS, SQL injection, secrets, and more |
| AI Usage Detection | Estimates AI involvement from code patterns |
| Uniqueness Score | Originality estimate based on structure & practices |
| Availability Check | Pings GitHub Pages and live URLs |
| Side-by-Side Compare | Compare two projects across all metrics |
| PDF Export | Professional branded report with logo watermark |
| History | Searchable log of all past analyses |

---

## Requirements

- **PHP 8.0+** with extensions: `curl`, `zip`, `pdo_mysql` (optional), `json`
- **Apache** or **Nginx** web server
- **Python 3.6+** with `reportlab` for PDF export
- **MySQL** (optional — falls back to JSON file storage automatically)

---

## Installation

### 1. Clone or upload files

```bash
git clone https://github.com/YOUR_USERNAME/pageover.git
cd pageover
```

Or upload the folder to your server's web root (e.g. `/var/www/html/pageover/`).

### 2. Set directory permissions

```bash
chmod 755 uploads/ data/
# If needed:
chown -R www-data:www-data uploads/ data/
```

### 3. Install Python PDF dependency

```bash
pip3 install reportlab
# or on shared hosting:
pip install reportlab --user
```

### 4. Configure database (optional)

Edit `php/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pageover');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

Create the database:

```sql
CREATE DATABASE pageover CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The `analyses` table is created automatically on first run.

> **Without a database:** The app automatically uses `data/` folder as JSON storage — no setup needed.

### 5. Environment variables (recommended for production)

Instead of editing `db.php` directly, set environment variables on your server:

```bash
export DB_HOST=localhost
export DB_NAME=pageover
export DB_USER=your_user
export DB_PASS=your_password
export GITHUB_TOKEN=ghp_your_token_here   # optional, for higher API rate limits
```

### 6. Open in browser

```
http://your-domain.com/pageover/
```

---

## GitHub API Rate Limits

Without authentication: **60 requests/hour** per IP.  
With a token: **5,000 requests/hour**.

To add a token, set the `GITHUB_TOKEN` environment variable on your server, or edit `php/functions.php`:

```php
$token = getenv('GITHUB_TOKEN');
// Change to:
$token = 'ghp_your_personal_access_token';
```

Create a token at: https://github.com/settings/tokens (no special scopes needed for public repos)

---

## Nginx Configuration

If using Nginx instead of Apache, add to your server block:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/pageover;
    index index.php;

    client_max_body_size 55M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block direct access to data and uploads
    location ~ ^/(data|uploads)/ {
        deny all;
    }
}
```

---

## File Structure

```
pageover/
├── index.php           — Homepage: upload form + features
├── result.php          — Full analysis result page
├── compare.php         — Side-by-side project comparison
├── history.php         — Analysis history log
├── logo.png            — PageOver logo (used in nav, hero, PDF)
├── .htaccess           — Apache config (security, caching, HTTPS)
├── .gitignore          — Excludes uploads/, data/, secrets
│
├── css/
│   └── style.css       — Complete dark-theme stylesheet
│
├── js/
│   └── main.js         — Frontend: drag-drop, AJAX, loading states
│
├── php/
│   ├── layout.php      — Shared header/nav/footer helpers
│   ├── analyze.php     — POST endpoint: handles file + GitHub analysis
│   ├── functions.php   — Core engine: detection, scoring, AI, bugs
│   ├── db.php          — MySQL + JSON fallback storage layer
│   ├── export_pdf.php  — GET endpoint: triggers Python PDF generation
│   └── generate_pdf.py — Python/ReportLab PDF builder with logo watermark
│
├── uploads/            — Temporary upload storage (auto-cleaned)
│   └── .gitkeep
│
└── data/               — JSON fallback storage (when no MySQL)
    └── .gitkeep
```

---

## PDF Export

The PDF export uses **Python + ReportLab**. If Python is not available on your server, the export will show an error. In that case:

1. Check Python is installed: `python3 --version`
2. Install reportlab: `pip3 install reportlab`
3. Test manually: `python3 php/generate_pdf.py`

---

## License

MIT — Free to use, modify, and distribute.

---

*Built with PHP, CSS, and JavaScript. No frameworks. No Node. No Composer.*
