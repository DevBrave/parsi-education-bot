# 🎓 PARSI PoliTO Bot — Academic Resource Platform

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11" />
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3+" />
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8" />
  <img src="https://img.shields.io/badge/Telegram-Bot%20API-26A5E4?style=for-the-badge&logo=telegram&logoColor=white" alt="Telegram API" />
  <img src="https://img.shields.io/badge/Google%20Drive-API%20v3-4285F4?style=for-the-badge&logo=googledrive&logoColor=white" alt="Google Drive API" />
  <img src="https://img.shields.io/badge/Politecnico%20di%20Torino-PoliTO-003366?style=for-the-badge" alt="PoliTO" />
</p>

---

## 📌 Overview

**PARSI PoliTO Bot** is an end-to-end distributed academic resource platform developed for students at **Politecnico di Torino (PoliTO)** by the **PARSI Student Association**.

It centralizes thousands of past exam solutions, lecture notes, lab guides, and formula sheets scattered across disconnected chat groups and cloud links into a unified, community-moderated repository accessible directly inside Telegram or via a secure administrative dashboard.

* **Live Bot:** [`@parsi_politobot`](https://t.me/parsi_politobot)
* **Target Audience:** 38,000+ Engineering & Architecture students at Politecnico di Torino
* **Coverage:** 100% Bachelor curricula active on launch, Master's curricula ongoing

---

## 🚀 Key Features

### 1. 🔍 Instant In-Chat Search (Telegram Inline Queries)
Students can search and share study materials inside **any** group chat or direct message without switching apps:
* Type `@parsi_politobot [course name or code]` (e.g. `@parsi_politobot Mathematical Analysis I` or `@parsi_politobot 01MATPN`).
* Floating results appear with course codes, degree programs, and file sizes.
* Tapping a result instantly posts the formatted material card with download links into the conversation.

### 2. 🤖 Conversational Bot Navigation
* Interactive nested keyboards: `Degree Program (BSc / MSc) ➔ Year ➔ Course ➔ Category (Exams, Notes, Exercises) ➔ File`.
* Tri-lingual localization support:
  * 🇬🇧 English (`en`)
  * 🇮🇹 Italian (`it`)
  * 🇮🇷 Persian (`fa` - with native RTL text formatting)

### 3. ☁️ Dual-Cloud Redundancy & Streaming
* **Telegram Storage Channel CDN:** Materials are archived to a private Telegram storage channel, generating persistent `file_id` pointers for sub-second, zero-cost delivery.
* **Google Drive Hierarchical Archive:** Automated backup using a 2-level folder hierarchy (`Course Name / Resource Type / File.ext`).
* **Resumable Chunked Uploads:** Custom 5MB chunk streaming via `MediaFileUpload` and file pointers (`fopen`/`fread`), handling files up to **500MB+** with zero PHP memory exhaustion.

### 4. 🛡️ File Security & Deduplication Pipeline
* **Magic Bytes Verification:** Rejects disguised malware, scripts, or executable files even if renamed to `.pdf`.
* **MIME & Extension Allowlists:** Enforces strict validation across PDF, DOCX, PPTX, ZIP, RAR, 7Z, and images.
* **Cryptographic SHA-256 Hashing:** Automatically blocks duplicate file submissions to save storage and keep the catalog pristine.

### 5. 👥 Crowdsourcing & Community Moderation
* **Student Material Submissions:** Multi-step submission conversation in the bot for students to contribute new notes or exam solutions.
* **1-Click Issue Reporting:** Every document card has a `⚠️ Report Issue` button logging reports (broken link, wrong course, poor quality) to the moderation queue.
* **Multi-Tier Role-Based Access Control (RBAC):** Admin dashboard with 4 privilege tiers:
  * `Super Admin`: Global system control, OAuth health, batch synchronization.
  * `Editor / Moderator`: Review student submissions, resolve reports, edit metadata.
  * `Professor / Major Rep`: Program-scoped material upload and management.
  * `Viewer`: Read-only analytics and catalog inspection.

### 6. ⚡ Asynchronous Queue Engine
* Integrated with Linux `supervisor` on a dedicated worker (`numprocs: 1`).
* Unique job locking (`ShouldBeUnique`) with elevated `retry_after: 1900s` and `$tries = 1` to eliminate race conditions and double-upload bugs on long-running transfers.
* Real-time Server-Sent Events (SSE) live audit stream at `/admin/logs`.

---

## 🏗️ High-Level System Architecture

```
[ Students & Groups ]                   [ Academic Reviewers ]
 (Telegram Mobile/Desktop)                (Blade Admin Portal)
          │                                         │
          ▼ (HTTPS Webhook)                         ▼ (HTTPS Browser)
┌──────────────────────────────────────────────────────────────┐
│                    NGINX REVERSE PROXY                       │
│              (SSL Termination, Rate Limiting)                │
└──────────────────────────────┬───────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────┐
│                    LARAVEL 11 CORE                           │
│                                                              │
│  ┌────────────────────────┐      ┌────────────────────────┐  │
│  │ Telegram Dispatcher    │      │ Admin Dashboard (RBAC) │  │
│  │ • Inline Queries       │      │ • Moderation Queue     │  │
│  │ • Callback Handlers    │      │ • Batch Drive Sync     │  │
│  │ • Submission Wizard    │      │ • SSE Live Log Stream  │  │
│  └───────────┬────────────┘      └───────────┬────────────┘  │
│              │                               │               │
│              ▼                               ▼               │
│     ┌────────────────────────────────────────────────┐       │
│     │          DATABASE QUEUE (jobs table)           │       │
│     │            (retry_after: 1900s)                │       │
│     └────────────────────────┬───────────────────────┘       │
└──────────────────────────────┼───────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────┐
│             SUPERVISOR QUEUE WORKER (numprocs: 1)            │
│       UploadResourceFileJob (ShouldBeUnique, $tries = 1)     │
│                              │                               │
│             ┌────────────────┴────────────────┐              │
│             ▼                                 ▼              │
│    [ Telegram Storage CDN ]          [ Google Drive API ]    │
│    (Instant In-Chat Delivery)       (Resumable Chunk Stream) │
└──────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Tech Stack

| Domain | Technology / Component | Purpose |
| :--- | :--- | :--- |
| **Backend** | PHP 8.3+, Laravel 11 | Robust MVC architecture, Eloquent ORM, Service Layer |
| **Database** | MySQL 8.0 | Relational catalog schema, foreign keys, full-text indexes |
| **Asynchronous**| Laravel Queues, Supervisor | Background workers, idempotent jobs, retry management |
| **APIs** | Telegram Bot API | Webhooks, Inline Queries, Callback Keyboards, CDN storage |
| **Cloud Storage** | Google Drive REST API v3 | Long-term backup, OAuth 2.0 flow, chunked resumable upload |
| **Frontend** | Blade, Tailwind CSS, Alpine.js | Modern, responsive administrative dashboard & audit logs |
| **Security** | SHA-256, Magic Bytes, RBAC | Content inspection, malware prevention, permission scoping |
| **Deployment** | Ubuntu 24.04 LTS, Nginx, PHP-FPM | High-performance reverse proxy, SSL termination, systemd |

---

## 📂 Project Structure

```
parsi_polito_bot/
├── app/
│   ├── Http/Controllers/Admin/     # Resource, Report, User, Course, Program controllers
│   ├── Jobs/
│   │   └── UploadResourceFileJob.php # Asynchronous, idempotent cloud uploader
│   ├── Models/                     # Eloquent models (Course, Program, Resource, File, User)
│   ├── Services/
│   │   ├── FileSecurityService.php   # Magic bytes, MIME checks, SHA-256 deduplication
│   │   ├── GoogleDriveService.php    # OAuth 2.0 client, folder hierarchy, chunked streams
│   │   ├── ResourceModerationService.php # Review workflows, notifications
│   │   ├── ResourceUploadService.php # Telegram CDN + Google Drive sync orchestrator
│   │   └── TelegramService.php       # Low-level Telegram Bot API wrapper
│   └── Telegram/
│       ├── Handlers/               # InlineQuery, CallbackQuery, Message handlers
│       └── Conversations/          # Multi-step material submission wizards
├── config/
│   ├── google.php                  # Google Drive credentials & folder IDs
│   ├── queue.php                   # Database queue configuration (retry_after: 1900s)
│   └── telegram.php                # Bot tokens, webhook secrets, storage channel ID
├── doc/                            # Architecture blueprints, guides, LinkedIn assets
│   ├── linkedin/
│   │   ├── linkedin_carousel.html  # Interactive 3-slide visual carousel viewer
│   │   └── linkedin_carousel_architecture.md # Mermaid architecture code
│   ├── PROJECT_COMPREHENSIVE_OVERVIEW.md     # Full architectural documentation
│   └── social_media_features_brief.md        # Content creation brief
├── lang/                           # Multi-lingual translations (en, it, fa)
├── routes/
│   ├── api.php                     # Telegram webhook ingestion endpoint
│   ├── console.php                 # Artisan CLI helper commands
│   └── web.php                     # Admin authentication, dashboard routes, OAuth setup
└── resources/views/admin/          # Tailwind-styled administrative dashboard views
```

---

## ⚙️ Getting Started & Local Development

### 1. Prerequisites
* **PHP:** `^8.2` or `^8.3` (with `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `gd`)
* **Composer:** `^2.x`
* **Node.js & NPM:** `^18.x` or `^20.x`
* **Database:** MySQL 8.0+

### 2. Installation

```bash
# 1. Clone repository
git clone https://github.com/parsi-polito/parsi_polito_bot.git
cd parsi_polito_bot

# 2. Install backend dependencies
composer install

# 3. Install and build frontend assets
npm install
npm run build

# 4. Copy environment configuration
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup & Seeding

Configure your MySQL credentials in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=parsi_polito_bot
DB_USERNAME=root
DB_PASSWORD=your_password
```

Run database migrations:
```bash
php artisan migrate --seed
```

### 4. Telegram & Google Drive Credentials

Add your credentials to `.env`:
```env
# Telegram Configuration
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_BOT_USERNAME=parsi_politobot
TELEGRAM_WEBHOOK_SECRET=your_random_secret_string
TELEGRAM_STORAGE_CHANNEL_ID=-100xxxxxxxxxx
TELEGRAM_ADMIN_CHAT_ID=-100xxxxxxxxxx

# Google Drive OAuth 2.0
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your_client_secret
GOOGLE_DRIVE_FOLDER_ID=your_root_folder_id
```

#### One-Click Google OAuth Token Generation:
1. Start local server: `php artisan serve`
2. Open in browser: `http://localhost:8000/google/auth`
3. Sign in and authorize your Google account.
4. The system automatically saves the fresh `GOOGLE_REFRESH_TOKEN` to your `.env`!

### 5. Running the Application & Workers

```bash
# Start local web server
php artisan serve

# Run the background queue worker
php artisan queue:work --sleep=3 --tries=1
```

---

## 🚀 Production Deployment (Ubuntu VPS & Supervisor)

To run the background upload pipeline with zero race conditions in production, configure **Supervisor**:

Create `/etc/supervisor/conf.d/parsi-worker.conf`:
```ini
[program:parsi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/parsi_polito_bot/artisan queue:work --sleep=3 --tries=1 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/parsi_polito_bot/storage/logs/worker.log
stopwaitsecs=1900
```

Apply Supervisor settings:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

Set Telegram Webhook:
```bash
curl -F "url=https://yourdomain.com/api/telegram/webhook" \
     -F "secret_token=your_secret_token" \
     https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook
```

---

## 📄 License & Acknowledgements

* **Developed by:** PARSI Student Association — Educational & Technical Committee
* **Institution:** Politecnico di Torino (PoliTO), Turin, Italy
* **License:** [MIT License](LICENSE)
* **Telegram Bot:** [@parsi_politobot](https://t.me/parsi_politobot)

---

<p align="center">
  Built with ❤️ for Politecnico di Torino students.
</p>

