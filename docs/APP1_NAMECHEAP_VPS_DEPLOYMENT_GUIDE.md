# APP1 Namecheap VPS Deployment Guide

Last updated: 2026-03-26
System: Cureva (App1, Laravel 12)

## 1) Purpose

This guide defines the recommended production runtime setup for Cureva on a single Namecheap VPS.

This version assumes:
- one VPS,
- no Redis,
- Laravel queue jobs enabled,
- production go-live next week.

Canonical template file:
- `/.env.namecheap.production.example`

## 2) Recommended Runtime Drivers

Use these Laravel drivers in production:

```env
APP_ENV=production
APP_DEBUG=false

QUEUE_CONNECTION=database
SESSION_DRIVER=file
CACHE_STORE=file
```

## 3) What Each Setting Does

### `QUEUE_CONNECTION=database`

This tells Laravel to store queued jobs inside the database `jobs` table.

What it does in Cureva:
- reminder dispatch now uses queued jobs,
- the scheduler finds due reminders,
- Laravel places reminder jobs into the database queue,
- the queue worker processes them in the background.

Why this is good for your VPS:
- works without Redis,
- keeps reminder sending out of the main request cycle,
- reduces the chance of slow pages or scheduler timeouts during dispatch.

Important note:
- if the queue worker is not running, reminders will stay in the queue and will not send.

### `SESSION_DRIVER=file`

This tells Laravel to store user session data as files on the server.

What it does in Cureva:
- stores login/session state locally on disk,
- avoids using the database for normal session reads/writes.

Why this is good for your VPS:
- reduces database pressure,
- simple and stable for a single-server deployment,
- fits well now that large reports payloads were moved out of session storage.

Important note:
- this is ideal for one server,
- if you later move to multiple app servers, session storage strategy may need to change.

### `CACHE_STORE=file`

This tells Laravel to store cache entries as files on the server.

What it does in Cureva:
- supports short-lived cache usage like navbar alert aggregation,
- helps reduce repeated database work for small cached values.

Why this is good for your VPS:
- simple,
- does not require Redis,
- suitable for current scale on one VPS.

Important note:
- file cache is not as fast as Redis,
- but it is acceptable and production-safe for this hosting model.

## 4) Required Background Processes

Two background processes must be working in production.

### A) Laravel Scheduler

The scheduler runs timed application tasks.

What it does in Cureva:
- triggers the reminders dispatch command on schedule,
- keeps automated reminder processing active.

Recommended cron entry:

```cron
* * * * * cd /path/to/app1 && php artisan schedule:run >> /dev/null 2>&1
```

If this is not running:
- due reminder scans will not run automatically,
- scheduled application tasks will be missed.

### B) Laravel Queue Worker

The queue worker processes jobs from the queue.

What it does in Cureva:
- consumes queued reminder dispatch jobs,
- runs them in the background,
- prevents reminder sending from blocking normal app flows.

Recommended worker command:

```bash
php artisan queue:work --tries=3 --timeout=120
```

If this is not running:
- queued reminders will remain in the `jobs` table,
- reminder delivery will stop even though scheduling continues.

## 5) How The Reminder Flow Works Now

The production reminder path is now:

1. Laravel scheduler runs the reminders command.
2. The command checks for due reminders.
3. Due reminders are added to the database queue.
4. The queue worker picks up queued reminder jobs.
5. SMS/email dispatch happens in the background.
6. Dispatch results are written to reminder logs.

This design is better for production because:
- it is safer under load,
- it avoids long-running dispatch work inside request/scheduler execution,
- it is easier to monitor and retry.

## 6) Why Redis Is Not Required Here

Redis is optional, not required, for your current hosting setup.

Because you are deploying on one Namecheap VPS:
- `database` queue is enough,
- `file` session is enough,
- `file` cache is enough.

This keeps deployment simpler and avoids introducing extra infrastructure right before go-live.

## 7) Production Checklist For This VPS Setup

Before go-live, confirm all of these:

1. `.env` uses:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `QUEUE_CONNECTION=database`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`

2. Database is migrated:
- `php artisan migrate --force`

3. Caches are rebuilt:
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`

4. Scheduler is active:
- cron job is installed and running

5. Queue worker is active:
- worker process stays alive after logout/reboot

6. Reminder provider settings are correct if live:
- `TERMII_ENABLED`
- `TERMII_API_KEY`
- `TERMII_SENDER_ID`
- `TERMII_WEBHOOK_TOKEN`

## 8) Recommended `.env` Production Block

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

If you later switch email delivery to Resend, your app currently expects:

```env
MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxxx
```

## 9) Summary

For Cureva on your Namecheap VPS, the recommended production model is:
- database queue,
- file session,
- file cache,
- active scheduler,
- active queue worker,
- no Redis required.

This is the simplest deployment model that still supports the new queue-based reminder flow and avoids unnecessary database pressure.
