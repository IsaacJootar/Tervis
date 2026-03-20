# APP1 Production Hardening Pass (Final Pre-Go-Live)

Last updated: 2026-03-20
System: Cureva (App1, Laravel 11)

## 1) Purpose

This document is the final-stage hardening checklist to run before production go-live.

It focuses on:
- stability at high record volume,
- performance and query safety,
- security and access control enforcement,
- operational readiness (backup, rollback, monitoring),
- controlled release with clear go/no-go criteria.

## 2) When To Run

Run this pass after:
- all V1 module development is complete,
- all UAT-critical bugs are fixed,
- no schema-breaking changes are still in progress.

Run it in this order:
1. staging hardening pass
2. staging sign-off
3. production rollout

## 3) Entry Criteria (Must Be True)

1. `php artisan test` passes.
2. `php artisan route:list` loads without errors.
3. All active role sidebars resolve to valid routes.
4. Monthly NHMIS mapping and report generation are green.
5. No open P0/P1 defects.

## 4) Hardening Workstreams

### A) Data and DB Hardening

1. Confirm required indexes exist on high-traffic filters:
- `facility_id`
- `patient_id`
- `visit_date`
- `created_at`
- date fields used in reports windows

2. Validate table growth safety:
- verify row counts for key tables (`daily_attendances`, `activities`, `lab_tests`, `prescriptions`, `invoice_lines`, `reminders`, `visit_events`).

3. Validate migrations state:
- no pending migrations in staging and production target.

4. Backup and restore rehearsal:
- take full backup,
- restore to a fresh environment,
- run smoke checks.

### B) Query and Performance Hardening

1. Enforce server-side pagination on operational tables.
2. Replace in-memory heavy loops with SQL aggregates where possible.
3. Ensure analytics payloads do not cache huge serialized model graphs.
4. Move expensive report generation to queued jobs where needed.
5. Add/confirm query timing logs for heavy reports and dashboards.

High-priority pages to benchmark:
- `/analytics/real-time-dashboard`
- `/analytics/diagnostic-assistant`
- `/core/reports-hub`
- `/core/laboratory-operations`
- `/workspaces/{id}/laboratory`
- `/workspaces/{id}/prescriptions`
- `/workspaces/{id}/invoices`

### C) Security and Access Hardening

1. Verify `permission.check` middleware on all protected routes.
2. Verify module enable/disable gating from central module management.
3. Verify facility scope isolation (no cross-facility leakage in activities, visits, reminders, appointments).
4. Verify central-only routes are inaccessible to non-central roles.
5. Verify disabled modules return readable 403 page with back navigation.

### D) Reliability Hardening

1. Queue worker health:
- supervisor/service configured,
- retries and failed job handling verified.

2. Scheduler health:
- `schedule:run` tasks validated (reports/reminders jobs as applicable).

3. Cache/session store validation:
- confirm store config for production,
- avoid cache patterns that can exceed DB packet limits.

4. Error handling:
- user-safe toasts/messages for failed submissions,
- no raw SQL errors shown to users.

### E) UX and Workflow Hardening

1. Confirm all long forms/modals follow template behavior:
- loading states on submit/actions,
- toast success/error,
- modal lifecycle behavior per hard rules.

2. Confirm all table screens follow standard:
- pagination,
- export buttons,
- aligned `th`/`td`,
- no duplicate DataTables init.

3. Confirm role-specific sidebars:
- no dead links,
- no duplicate module entries.

## 5) Scale Readiness Validation (Staging)

Use high-volume seed and run benchmark smoke:

1. Seed realistic high-volume mixed data.
2. Run critical pages with:
- 50k+ operational records across modules,
- multi-month report windows,
- concurrent user checks (basic parallel access).

Pass criteria:
- no fatal/SQL exceptions,
- no blank critical widgets caused by timeouts,
- response times acceptable for top workflows.

## 6) Test and QA Gates

### Automated

Run:
- `php artisan test`
- targeted regression suites for:
  - reports/NHMIS,
  - workspace aggregation chain,
  - module middleware,
  - role permission middleware,
  - core facility workflow transitions.

### Manual Smoke (Per Role)

1. Central Admin
- module management
- roles/permissions
- central dashboards/routes

2. Facility Admin
- staff management
- facility administration
- reports hub
- operations modules

3. Data Officer / Clinical flow
- patient verify -> assessment -> lab -> prescriptions -> invoice/payment
- child health chain
- reminders/appointments aggregation

## 7) Go/No-Go Checklist

Go only if all are true:
1. No P0/P1 defects open.
2. No route/view fatal errors.
3. No critical query/path over-timeout behavior.
4. Backup/restore test passed.
5. Security scope checks passed.
6. Stakeholder sign-off complete.

No-Go triggers:
- repeated 500 errors on core workflows,
- data leakage across facility scope,
- monthly reports inconsistency on validation windows,
- failing rollback rehearsal.

## 8) Rollout Plan (High Level)

1. Freeze window starts.
2. Final DB backup.
3. Deploy release.
4. Run post-deploy smoke scripts.
5. Monitor logs and response latency for hypercare window.

## 9) Rollback Plan

1. Keep prior release artifact ready.
2. Restore DB from pre-release backup if data integrity risk appears.
3. Repoint app to prior stable release.
4. Publish incident summary and corrective action list.

## 10) Ownership Matrix

1. Engineering Lead: hardening execution and technical sign-off.
2. QA Lead: test gate sign-off.
3. Product/Operations: workflow acceptance sign-off.
4. Infrastructure/DevOps: backup, deploy, rollback readiness sign-off.

## 11) Required Outputs (Archive For Go-Live)

Store under `docs/`:
1. hardening checklist run result
2. benchmark summary
3. test run summary
4. known issues accepted for post-go-live
5. final sign-off record
