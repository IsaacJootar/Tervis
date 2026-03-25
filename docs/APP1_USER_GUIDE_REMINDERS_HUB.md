# APP1 User Guide: Reminders & Notifications Hub

Last updated: 2026-03-21

## 1) Purpose

The Facility Reminders Hub is the facility-admin control center for reminders.

- URL: `http://localhost:8000/core/reminders-notifications-hub`
- Route name: `facility-reminders-hub`
- Intended user: Facility Administrator

This hub is used to:
- Pull appointment/return dates from clinical modules into the reminder queue.
- Send due reminders (SMS via Termii when enabled, Email placeholder for now).
- Monitor queue status and dispatch logs.

Ownership and scope:
- Facility Admin handles reminder sync/dispatch/log monitoring for their own facility scope.
- Central dashboards can monitor summaries, but dispatch control remains facility-level.

## 2) Core Actions and Correct Order

### Action A: `Sync Facility Sources`
What it does:
- Reads date fields from source modules in the same facility.
- Creates new reminder records or updates existing ones.
- Sources currently synced:
  - Doctor Assessment: `next_appointment_date`
  - ANC Tetanus: `next_appointment_date`
  - ANC Follow-up: `next_return_date`
  - Family Planning: `next_appointment`

### Action B: `Dispatch Due`
What it does:
- Sends reminders that are due now.
- Due logic:
  - `status in (pending, queued, failed)`
  - `reminder_date <= today`
- Writes per-channel dispatch log entries.

### Required order
1. Click `Sync Facility Sources` first.
2. Click `Dispatch Due` second.

Why order matters:
- If you dispatch before syncing, newly entered or updated module dates may be missed in that run.
- Sync keeps queue data current; dispatch acts on that queue.

## 3) Daily Usage Workflow (Recommended)

1. Open the hub.
2. Set date filters if needed (`From`, `To`).
3. Click `Sync Facility Sources`.
4. Confirm toast summary (`created`, `updated`, patient count).
5. Click `Dispatch Due`.
6. Confirm toast summary (`sent`, `failed`, `skipped`).
7. Review:
   - `Facility Reminder Queue` table for pending/canceled items.
   - `Dispatch Log` table for per-channel outcomes.

## 4) Queue Actions

- `Send`: immediately dispatch one reminder.
- `Cancel`: mark reminder as canceled (excluded from dispatch).
- `Requeue`: move canceled/failed reminder back to pending for future dispatch.

## 5) Status Meaning

- `pending`: ready to be dispatched when due.
- `queued`: selected for dispatch processing.
- `sent`: at least one channel sent successfully.
- `failed`: dispatch attempted but failed.
- `canceled`: intentionally stopped by user.

## 6) SMS Provider Behavior (Termii)

SMS dispatch is provider-based:
- If `TERMII_ENABLED=true`, SMS dispatch uses Termii.
- If `TERMII_ENABLED=false`, SMS dispatch uses placeholder simulation.
- Email dispatch is still placeholder simulation.

Required env keys for live SMS:
- `TERMII_ENABLED=true`
- `TERMII_API_KEY=...`
- `TERMII_SENDER_ID=...` (approved sender id)
- Optional tuning:
  - `TERMII_CHANNEL=generic`
  - `TERMII_TIMEOUT_SECONDS=15`

Dispatch logs store provider diagnostics including:
- provider name
- provider message
- provider message id
- provider HTTP code
- delivery callback status (when provider callback is configured)

Termii delivery callback endpoint:
- `POST /webhooks/termii/delivery`
- token protection:
  - set `TERMII_WEBHOOK_TOKEN`
  - send header `X-Webhook-Token: <token>`

## 7) Troubleshooting

- "No reminders dispatched":
  - Run `Sync Facility Sources` first.
  - Check that source modules actually contain future/valid next-date fields.
  - Check reminder status/date filters.
- "Many failed":
  - Verify patient phone/email data is present.
  - Check dispatch log `Provider Message`.

## 8) Technical Reference (Code Locations)

- Livewire hub component:
  - `app/Livewire/Core/FacilityRemindersHub.php`
- Hub UI:
  - `resources/views/livewire/core/facility-reminders-hub.blade.php`
- Sync + dispatch service:
  - `app/Services/Communication/ReminderDispatchService.php`
- SMS providers:
  - `app/Services/Communication/SmsDispatchService.php`
  - `app/Services/Communication/TermiiSmsService.php`
  - `app/Services/Communication/SmsPlaceholderService.php`
- Email placeholder:
  - `app/Services/Communication/EmailPlaceholderService.php`
- Route:
  - `routes/web.php` (`/core/reminders-notifications-hub`)
- Console command/scheduler:
  - `routes/console.php` (`reminders:dispatch-due`)
