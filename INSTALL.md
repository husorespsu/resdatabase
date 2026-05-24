# PSU Research Management System — Setup Guide

## 1. Database Setup

Open **phpMyAdmin** at `http://localhost/phpmyadmin` and run:

```sql
-- Step 1: Create database and tables
SOURCE /Applications/XAMPP/xamppfiles/htdocs/research/database/schema.sql;

-- Step 2: Load sample data
SOURCE /Applications/XAMPP/xamppfiles/htdocs/research/database/sample_data.sql;
```

Or from terminal:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "source /Applications/XAMPP/xamppfiles/htdocs/research/database/schema.sql"
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "source /Applications/XAMPP/xamppfiles/htdocs/research/database/sample_data.sql"
```

## 2. Google OAuth2 Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Go to **APIs & Services → Credentials**
4. Click **Create Credentials → OAuth 2.0 Client ID**
5. Application type: **Web application**
6. Add Authorized redirect URI: `http://localhost/research/auth/google/callback`
7. Copy **Client ID** and **Client Secret**
8. Edit `.env` file:

```env
GOOGLE_CLIENT_ID=your_actual_client_id
GOOGLE_CLIENT_SECRET=your_actual_client_secret
ALLOWED_DOMAINS=gmail.com,psu.ac.th
```

> For testing, add `gmail.com` to `ALLOWED_DOMAINS` to allow any Gmail account.
> The **first user** to login automatically gets `superadmin` role.

## 3. Email (PHPMailer) Setup (Optional)

Edit `.env`:
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=your_app_password   # Gmail App Password (not your login password)
```

To create a Gmail App Password:
- Go to Google Account → Security → 2-Step Verification → App passwords

## 4. File Permissions (macOS)

```bash
chmod -R 755 /Applications/XAMPP/xamppfiles/htdocs/research/public/uploads
chmod -R 755 /Applications/XAMPP/xamppfiles/htdocs/research/storage
chmod -R 755 /Applications/XAMPP/xamppfiles/htdocs/research/cron
```

## 5. Cron Job (Notification Reminders)

### macOS — add to crontab:
```bash
crontab -e
```
Add line (runs daily at 8 AM):
```
0 8 * * * /Applications/XAMPP/xamppfiles/bin/php /Applications/XAMPP/xamppfiles/htdocs/research/cron/check_due_dates.php >> /Applications/XAMPP/xamppfiles/htdocs/research/cron/logs/cron.log 2>&1
```

## 6. Access the Application

Start XAMPP (Apache + MySQL), then open:

**`http://localhost/research/`**

- If not logged in → redirected to login page
- Click **"เข้าสู่ระบบด้วย Google"**
- First login = superadmin

## 7. Quick Verification Checklist

- [ ] XAMPP Apache running on port 80
- [ ] XAMPP MySQL running on port 3306
- [ ] Database `research_management` created with tables
- [ ] `.env` has correct `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`
- [ ] `http://localhost/research/` loads login page
- [ ] Google OAuth login works

## File Structure Summary

```
research/
├── index.php              ← Front controller (entry point)
├── .env                   ← Environment config (edit this!)
├── composer.json          ← PHP dependencies
├── vendor/                ← Composer packages (auto-generated)
├── app/
│   ├── core/              ← Router, Controller, Model, Middleware
│   ├── controllers/       ← Auth, Dashboard, Proposal, Project, Reviewer...
│   ├── models/            ← User, Proposal, Project, ExpertReviewer...
│   ├── views/             ← PHP HTML templates
│   └── helpers/           ← functions.php (h(), formatBudget(), etc.)
├── config/                ← database.php, app.php, google_oauth.php
├── public/
│   ├── css/style.css      ← PSU Blue theme
│   ├── js/app.js          ← Sidebar, DataTables, AJAX, Charts
│   └── uploads/           ← User-uploaded files
├── database/
│   ├── schema.sql         ← Create tables
│   └── sample_data.sql    ← Sample data
└── cron/
    └── check_due_dates.php ← Daily reminder script
```
