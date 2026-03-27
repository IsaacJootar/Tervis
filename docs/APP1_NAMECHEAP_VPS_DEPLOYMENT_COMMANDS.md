# APP1 Namecheap VPS Deployment Commands

Last updated: 2026-03-26
System: Cureva (App1, Laravel 12)

## 1) Purpose

This is the practical deployment runbook for Cureva on a single Namecheap VPS.

It assumes:
- one VPS,
- no Redis,
- Laravel queue uses database,
- scheduler and queue worker must run continuously.

Canonical template file:
- `/.env.namecheap.production.example`

## 2) Expected Production `.env`

Set these values in production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Africa/Lagos

QUEUE_CONNECTION=database
SESSION_DRIVER=file
CACHE_STORE=file

REMINDER_AUTO_DISPATCH=true
REMINDER_AUTO_DISPATCH_WITH_SYNC=true
```

Also make sure your database, mail, and Termii values are correct.

If you later switch to Resend for production email, your app currently expects:

```env
MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxxx
```

## 3) First-Time Server Preparation

Run these once after your VPS is ready and the project is uploaded.

### A) Go to the project folder

```bash
cd /path/to/app1
```

### B) Install PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### C) Create or update the production environment file

```bash
cp .env.example .env
```

Then edit `.env` with your real production values.

### D) Generate the application key if not already set

```bash
php artisan key:generate
```

### E) Run database migrations

```bash
php artisan migrate --force
```

### F) Create the storage symlink

```bash
php artisan storage:link
```

### G) Clear old cached state

```bash
php artisan optimize:clear
```

### H) Build production caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4) Deploy Update Commands

Use this order whenever you are pushing a new release to the VPS.

### A) Enter the project

```bash
cd /path/to/app1
```

### B) Pull or upload the latest code

If you deploy with git:

```bash
git pull origin main
```

If you deploy manually:
- upload the changed files carefully,
- do not overwrite your production `.env`.

### C) Install/update dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### D) Run migrations

```bash
php artisan migrate --force
```

### E) Clear and rebuild caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### F) Restart queue workers after deployment

```bash
php artisan queue:restart
```

## 5) Scheduler Setup

The Laravel scheduler must run every minute.

Open crontab:

```bash
crontab -e
```

Add this line:

```cron
* * * * * cd /path/to/app1 && php artisan schedule:run >> /dev/null 2>&1
```

What this does:
- runs Laravel scheduled tasks every minute,
- lets Cureva queue due reminders automatically.

## 6) Queue Worker Setup

The queue worker must stay alive in the background.

### Simple manual worker command

```bash
cd /path/to/app1
php artisan queue:work --tries=3 --timeout=120
```

This is useful for testing, but not enough for real production because it stops when the shell closes.

### Recommended Supervisor setup

Install Supervisor if not already installed:

```bash
sudo apt update
sudo apt install supervisor -y
```

Create a Supervisor config file:

```bash
sudo nano /etc/supervisor/conf.d/cureva-worker.conf
```

Use this sample config:

```ini
[program:cureva-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app1/artisan queue:work --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/app1/storage/logs/worker.log
stopwaitsecs=360
```

Then reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cureva-worker:*
```

Check worker status:

```bash
sudo supervisorctl status
```

## 7) Permissions

Make sure Laravel can write to the required folders:

```bash
sudo chown -R www-data:www-data /path/to/app1
sudo chmod -R 775 /path/to/app1/storage
sudo chmod -R 775 /path/to/app1/bootstrap/cache
```

If your web server runs as a different user, replace `www-data` with the correct user.

## 8) Health Checks After Deployment

Run these checks after each deployment:

```bash
php artisan about
php artisan migrate:status
php artisan queue:restart
php artisan route:list
```

Then manually confirm:
- login works,
- dashboard loads,
- reports hub generates,
- print view opens,
- reminders queue successfully,
- queue worker is active,
- scheduler is active.

## 9) Reminder-Specific Production Check

To confirm reminders are working:

### A) Trigger due reminder scan

```bash
php artisan reminders:dispatch-due --sync
```

What this does:
- refreshes reminder data from module dates,
- queues due reminder jobs,
- does not rely on Redis.

### B) Confirm queue jobs are being consumed

Check if queued jobs keep growing:
- if jobs remain in the `jobs` table and are not reducing,
- the worker is not processing them correctly.

### C) Check logs

```bash
tail -f storage/logs/laravel.log
```

If you used Supervisor:

```bash
tail -f storage/logs/worker.log
```

## 10) Rollback Basics

Before each deployment:
- back up the database,
- keep the previous release copy available.

If something goes wrong:

1. restore the previous code release,
2. restore database backup if the release changed data in a harmful way,
3. rebuild caches,
4. restart queue workers.

Useful commands:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

## 11) Go-Live Command Order

For your production launch, this is the recommended order:

1. Back up database.
2. Upload or pull latest code.
3. Run `composer install --no-dev --optimize-autoloader`.
4. Confirm `.env` production settings.
5. Run `php artisan migrate --force`.
6. Run `php artisan optimize:clear`.
7. Run `php artisan config:cache`.
8. Run `php artisan route:cache`.
9. Run `php artisan view:cache`.
10. Confirm cron scheduler exists.
11. Confirm Supervisor worker is running.
12. Run `php artisan reminders:dispatch-due --sync`.
13. Perform smoke test.

## 12) Summary

This deployment model is built for your current hosting reality:
- one Namecheap VPS,
- no Redis,
- database queue,
- file session,
- file cache,
- cron scheduler,
- Supervisor-managed queue worker.

It is the simplest production setup that still supports Cureva’s queue-based reminder flow safely.
