# Website Monitoring System - Implementation Guide

## Overview

I've extended your Laravel + Vue.js + Tailwind CSS application with a complete **Website Monitoring System**. This feature allows authenticated users to monitor the availability of multiple websites and receive email notifications when sites go offline.

## ✅ What Was Implemented

### 1. **Database Layer**
- **Migration**: `2026_05_05_000000_create_websites_table.php`
  - `id`, `user_id`, `name`, `url`, `check_interval` (hours)
  - `status` (enum: online/offline), `status_code`, `last_checked_at`
  - Timestamps, foreign key with cascade delete

- **Migration**: `2026_05_05_000001_create_website_logs_table.php`
  - Logs each check: `status_code`, `response_time` (ms), `checked_at`
  - Foreign key to websites table

### 2. **Models**
- **`app/Models/Website`** - Main website model with:
  - User relationship
  - Website logs relationship
  - Status constants (`STATUS_ONLINE`, `STATUS_OFFLINE`)
  - `forUser()` scope for filtering by authenticated user

- **`app/Models/WebsiteLog`** - Log entries for each check

- **`app/Models/User`** - Updated with `websites()` relationship

### 3. **Controllers & Requests**
- **`app/Http/Controllers/WebsiteController`** - REST API endpoints:
  - `GET /websites` - List user's websites
  - `POST /websites` - Add new website (runs initial check)
  - `PATCH /websites/{website}` - Update website (reruns check)
  - `DELETE /websites/{website}` - Delete website

- **`app/Http/Requests/WebsiteRequest`** - Form validation:
  - Validates URL format (must be valid HTTP/HTTPS)
  - Auto-prefixes `https://` if missing
  - Validates required fields and interval

### 4. **Monitoring Service & Jobs**
- **`app/Services/WebsiteMonitorService`** - Core monitoring logic:
  - `check(Website)` - Single website check with retry logic
  - `dispatchDueWebsiteChecks()` - Queue jobs for websites due for check
  - URL normalization and validation
  - Status change detection (offline notifications only sent on change)

- **`app/Jobs/MonitorWebsiteJob`** - Queued job for background processing

### 5. **Email Notifications**
- **`app/Mail/WebsiteOfflineNotification`** - Queued email class
- **`resources/views/emails/website-offline.blade.php`** - Email template

### 6. **Scheduled Monitoring**
- **`app/Console/Kernel`** - Console kernel that schedules:
  - `->everyMinute()` check for websites due for monitoring
  - Uses Laravel Task Scheduling for cron integration

### 7. **Routes & Frontend**
- **Backend Routes** (`routes/web.php`):
  ```
  GET|HEAD   /websites ..................... websites.all
  POST       /websites .................. websites.store
  PATCH      /websites/{website} ...... websites.update
  DELETE     /websites/{website} ...... websites.destroy
  ```

- **Frontend Route Generator** (`resources/js/routes/websites/index.ts`) - Auto-generated route helpers

- **Updated Frontend UI** (`resources/js/pages/Ping.vue`):
  - Converted to full website monitoring dashboard
  - Form to add/edit/delete websites
  - Live status table with last-checked times
  - Auto-refresh every 30 seconds
  - Status badges (green=online, red=offline)
  - Edit/delete buttons with confirmation

### 8. **Tests**
- **`tests/Feature/WebsiteMonitoringTest.php`** - Feature tests for:
  - Creating websites
  - Retrieving user's websites
  - Offline notification dispatch
  - Scheduling of due checks

---

## 🚀 Getting Started

### Step 1: Run Migrations
```bash
php artisan migrate
```

This creates `websites` and `website_logs` tables.

### Step 2: Configure Queue (Optional but Recommended)
Email notifications and monitoring jobs are queued by default.

**For testing with sync driver** (edit `.env`):
```env
QUEUE_CONNECTION=sync
MAIL_DRIVER=log
```

**For production** with background jobs:
```env
QUEUE_CONNECTION=redis
# or database, etc.
```

Then run:
```bash
php artisan queue:work
```

### Step 3: Set Up Scheduler
For automatic checks every minute, add to your crontab:
```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Step 4: Build Frontend
```bash
npm run build
# or for development:
npm run dev
```

---

## 📋 Key Features

### ✨ User Experience
- **Dashboard**: See all monitored websites in one place
- **Real-time Status**: Green (online) or red (offline) badges
- **Last Checked Time**: Relative time display (e.g., "5 minutes ago")
- **Easy Management**: Edit check intervals or delete sites

### 🔒 Multi-Tenant
- Users see only their websites
- Authorization checks on all endpoints (403 Forbidden if not owner)
- Proper scoping with `forUser()` queries

### 📧 Smart Notifications
- Email sent ONLY when status changes from online → offline
- Prevents notification spam
- Includes website name, URL, error details
- Queued for non-blocking execution

### ⚡ Efficient Monitoring
- Respects `check_interval` (in hours) - won't re-check if recently checked
- Retry logic for failed requests (2 attempts, 500ms delay)
- 10-second timeout per request
- Response time tracking (milliseconds)

### 📊 Data Logging
- Every check is logged in `website_logs`
- Status code, response time, and check timestamp recorded
- Useful for analytics and troubleshooting

---

## 🛠️ Configuration

### Check Interval
Users set `check_interval` in hours when creating/editing a website. The scheduler will only check websites when their interval has passed since `last_checked_at`.

### HTTP Timeouts
In `WebsiteMonitorService::check()`:
- Request timeout: 10 seconds
- Retry attempts: 2 with 500ms delay

Adjust these in the service if needed.

### Email Configuration
Configure in `.env`:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Ping Check"
```

---

## 📂 File Structure

```
New Files Created:
├── app/
│   ├── Console/
│   │   └── Kernel.php (Schedule defined here)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── WebsiteController.php
│   │   └── Requests/
│   │       └── WebsiteRequest.php
│   ├── Jobs/
│   │   └── MonitorWebsiteJob.php
│   ├── Mail/
│   │   └── WebsiteOfflineNotification.php
│   ├── Models/
│   │   ├── Website.php
│   │   └── WebsiteLog.php
│   └── Services/
│       └── WebsiteMonitorService.php
├── database/
│   └── migrations/
│       ├── 2026_05_05_000000_create_websites_table.php
│       └── 2026_05_05_000001_create_website_logs_table.php
├── resources/
│   ├── js/
│   │   ├── routes/
│   │   │   └── websites/
│   │   │       └── index.ts (Auto-generated, ignored by .gitignore)
│   │   └── pages/
│   │       └── Ping.vue (Updated for monitoring UI)
│   └── views/
│       └── emails/
│           └── website-offline.blade.php
└── tests/
    └── Feature/
        └── WebsiteMonitoringTest.php

Modified Files:
├── bootstrap/app.php (Added console kernel binding)
├── routes/web.php (Added website routes)
├── app/Models/User.php (Added websites() relation)
└── resources/js/pages/Ping.vue (Complete UI redesign)
```

---

## 🧪 Testing

### Run Feature Tests
```bash
php artisan test tests/Feature/WebsiteMonitoringTest.php
```

**Note**: Tests require SQLite PHP extension. If you see "could not find driver" errors, install it:
```bash
# macOS
brew install php-sqlite

# Linux (Ubuntu/Debian)
sudo apt-get install php8.3-sqlite3

# Windows
Enable php_pdo_sqlite.dll in php.ini
```

### Manual Testing

1. **Navigate to the Ping page** in your browser
2. **Fill in the form**:
   - Website name: "Google"
   - Website URL: "https://www.google.com"
   - Check interval: "1" (hour)
3. **Submit** - Website is added and immediately checked
4. **See the status** - Green badge means online, red means offline
5. **Edit or Delete** - Use action buttons in the table

---

## 🔍 Debugging & Monitoring

### Check Scheduled Tasks
```bash
php artisan schedule:list
```

### View Queued Jobs
If using database queue:
```bash
php artisan queue:failed
```

### Test the Scheduler Manually
```bash
php artisan schedule:run
```

### View Monitoring Logs
Website check logs are stored in `website_logs` table. Query them:
```sql
SELECT * FROM website_logs 
WHERE website_id = 1 
ORDER BY checked_at DESC 
LIMIT 10;
```

### Email Testing
Configure mail driver to `log` in `.env` to see emails in storage/logs:
```env
MAIL_DRIVER=log
```

---

## 📝 API Endpoints

### List User's Websites
```http
GET /websites
Authorization: Bearer {token}
```

### Add Website
```http
POST /websites
Content-Type: application/json
Authorization: Bearer {token}

{
  "name": "Google",
  "url": "https://www.google.com",
  "check_interval": 1
}
```

### Update Website
```http
PATCH /websites/{website_id}
Content-Type: application/json
Authorization: Bearer {token}

{
  "name": "Updated Name",
  "url": "https://www.google.com",
  "check_interval": 2
}
```

### Delete Website
```http
DELETE /websites/{website_id}
Authorization: Bearer {token}
```

---

## 🎯 Next Steps & Enhancements

### Optional Future Improvements

1. **Retry Logic Enhancement**
   - Add exponential backoff for consecutive failures
   - Track failure count to avoid hammering dead sites

2. **Status Page**
   - Public status page showing all monitored sites
   - Uptime percentage calculations

3. **Alerts & Webhooks**
   - Send to Slack, Discord, or custom webhooks
   - SMS notifications via Twilio

4. **Analytics**
   - Uptime charts and reports
   - Response time graphs over time

5. **Multiple Check Methods**
   - TCP/port checks
   - DNS resolution validation
   - Certificate expiration alerts

6. **Team Management**
   - Share website monitoring with team members
   - Role-based permissions

---

## 🚨 Troubleshooting

### Websites Not Being Checked
**Issue**: Scheduler not running or jobs not processing

**Solution**:
1. Ensure cron is set up (or manually run `php artisan schedule:run`)
2. Check queue connection (if using queued jobs)
3. Run `php artisan queue:work` in another terminal

### Emails Not Sending
**Issue**: Offline notifications not received

**Solution**:
1. Check `.env` mail configuration
2. For sync driver, verify MAIL_DRIVER=log is set for testing
3. Run `php artisan tinker` and test: `Mail::to('test@example.com')->send(new \App\Mail\WebsiteOfflineNotification(...))`

### Authorization Errors
**Issue**: 403 Forbidden when accessing websites

**Solution**:
- Ensure you're authenticated: `auth()->check()`
- Verify you own the website (check `user_id` in database)

---

## 📚 References

- [Laravel HTTP Client](https://laravel.com/docs/11.x/http-client)
- [Laravel Queues](https://laravel.com/docs/11.x/queues)
- [Laravel Scheduling](https://laravel.com/docs/11.x/scheduling)
- [Laravel Mail](https://laravel.com/docs/11.x/mail)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)

---

## ✅ Implementation Checklist

- [x] Database migrations (websites & website_logs tables)
- [x] Eloquent models (Website, WebsiteLog, User relation)
- [x] Form validation (WebsiteRequest)
- [x] REST API controller (WebsiteController)
- [x] Service layer (WebsiteMonitorService)
- [x] Background job (MonitorWebsiteJob)
- [x] Email notification (WebsiteOfflineNotification)
- [x] Laravel scheduler (Console/Kernel)
- [x] Frontend Vue component (Ping.vue redesigned)
- [x] Frontend route helpers (websites/index.ts)
- [x] Feature tests (WebsiteMonitoringTest)
- [x] Documentation (this file!)

**All done! Your website monitoring system is ready to deploy.** 🎉
