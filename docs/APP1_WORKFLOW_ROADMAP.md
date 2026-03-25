# APP1 Workflow and Roadmap

Last updated: 2026-03-20  
Aligned to: HMS Vision Architecture v2.0 (Nov 2025)

## 1) Current End-to-End Workflow (Implemented)

1. Registration and activation:
   - Patient is enrolled through one of the register entry points.
   - Daily DIN activation grants workspace access.
2. Workspace clinical flow:
   - Data Officer/Doctor opens patient dashboard modules.
   - Doctor Assessment captures findings note and can issue test + drug requests.
3. Lab bridge:
   - Requested tests appear as pending in Laboratory.
   - Lab save can complete selected pending requests.
4. Pharmacy/dispensing bridge:
   - Pending prescriptions appear in Prescriptions page.
   - Dispensing uses cart checkout and marks selected pending prescriptions as dispensed.
5. Billing bridge:
   - Prescription checkout generates/updates invoice records.
   - Payments are recorded in Invoices & Payments module with history.
6. Child health chain:
   - Immunizations + Vaccination Schedule + Nutrition capture child activity data.
7. Appointment aggregation:
   - Appointments module auto-collates next-visit dates from Doctor Assessment, TT, ANC follow-up, and Family Planning.
8. Reminder orchestration:
   - Reminders module syncs due dates from Doctor Assessment, TT, ANC follow-up, and Family Planning.
   - Due reminders dispatch through configured SMS/Email channels (Termii SMS when enabled) with per-channel dispatch logs.
   - Delivery callback endpoint updates provider delivery status back into dispatch logs.
9. AI Assistant advisory layer:
   - Key modules expose `Use AI Assistant` -> side panel suggestions -> `Hide AI Assistant`.
   - Suggestions are predictive/flagging/advisory only (no auto-submit or auto-complete actions).
   - Initial coverage: Doctor Assessments, Laboratory, Prescriptions, Child Vaccination Schedule, Facility Reminders Hub.
10. Insurance management:
   - Initial NHIS capture is aligned across all register entry points (General, ANC, Family Planning).
   - Health Insurance workspace manages activation/deactivation and NHIS detail updates with audit history.
11. Visit collation:
   - Visits module auto-groups visit sessions by date from DIN activation + activity records.
   - Sync is automatic on activity/activation create-update-delete events (staff do not click manual sync).
   - Visit events remain facility-scoped and are sync/backfill-safe.
12. Monthly report:
   - Monthly dashboard aggregates mapped data from attendance, maternal/child, doctor, and lab sources.
13. Reports Hub:
   - Core Reports Hub (`/core/reports-hub`) allows section-based report selection, date/facility filters, generation history, and CSV export.

## 1b) Clinical Order Bridges (Implemented)

### Doctor -> Laboratory -> Patient Lab Record
1. Doctor saves assessment with requested tests.
2. System creates `lab_test_orders` rows with `status = pending` (patient + facility scoped).
3. Laboratory workspace for that patient loads pending test orders.
4. On laboratory save/update:
   - selected pending orders are required when pending exists,
   - system creates/updates `lab_tests` record,
   - selected orders are marked `completed` and linked using `completed_lab_test_id`.
5. Result: patient lab entry and pending queue stay synchronized.

### Doctor -> Prescriptions -> Dispensing -> Inventory -> Invoice
1. Doctor saves assessment with drug orders.
2. System creates `prescriptions` rows with `status = pending` (patient + facility scoped).
3. Prescriptions workspace loads pending prescriptions for that patient.
4. On checkout:
   - selected pending rows are required when pending exists,
   - `drug_dispense_lines` are written,
   - matched pending prescriptions are marked `dispensed`,
   - inventory is deducted FIFO from `drug_stock_batches` and `drug_stock_movements` are logged,
   - billing line is posted to invoice.
5. Result: issuance, stock ledger, and billing are tied to one checkout event.

## 2) Workflow Gaps (Not Yet Closed)

1. Facility operations:
   - Bed management + admitted-patient foundations are now delivered; advanced operations (transfers, occupancy dashboards, staffing ratios) remain.
   - Pharmacy/Lab operations are still patient-form-centric, not full facility-operations grade.
2. NHMIS completeness:
   - Monthly mapping is partial; additional row-key mappings and stronger structured sources are needed.
3. Test coverage:
   - No dedicated workflow feature tests yet for Doctor/Lab/Prescriptions/Child Health/Bed-Inpatient chains.

## 3) Execution Plan (Next Modules, In Order)

## Phase A: Close Remaining Patient Dashboard Core
1. Reminders module (Section 13) - completed with facility-owned delivery stack (Termii-ready).

Done criteria:
- Routed module pages exist from dashboard.
- CRUD and workflow transitions completed.
- Loading states and feedback implemented.
- Dispatch command available: `php artisan reminders:dispatch-due --sync`.

## Phase B: Complete Patient Support and Longitudinal Tracking
1. Family Planning workspace activity module (Section 14 alignment) - completed
2. Health Insurance module (Section 15) - completed
3. Visits session grouping module (Section 16) - completed
4. Timeline enrichment improvements (Section 17 quality upgrade) - completed

Done criteria:
- Full patient workflow chain can run without side systems.
- Each module has activity/audit logging and dashboard counts.

## Phase C: Facility Operations Buildout
1. Bed Management (completed foundation)
2. Admitted Patients (completed foundation)
3. Pharmacy & Drug full ops (inventory foundation delivered: stock-in, adjustments, reorder levels, movement logs, dispense deduction; advanced supplier/LMIS full operations deferred to Version 2)
4. Laboratory full ops (completed: facility queue intake, sample tracking, processing batches, QC, reagents/movements, equipment logs)
5. Staff Management (completed: unified facility staff module, legacy Data Officer pages redirected)
6. Facility Administration hardening (completed: profile settings, service catalog, fee schedules, module access status view, audit trail; module toggle control centralized in Central Admin)
7. Central Admin hardening (completed: live central dashboard metrics, route-valid sidebar cleanup, legacy `/central-admin/*` compatibility redirects)
8. RBAC hardening (completed: central roles-permissions matrix, route-level `permission.check` enforcement, central-to-facility access propagation model)
9. Legacy core UI modernization (completed: old hero/floating headers removed from remaining core operations pages and replaced with simple section-card headers)
10. Role-menu route hygiene (completed: role sidebar JSON targets validated against route list; duplicate facility admin menu entry removed)
11. Shared layout dead-style cleanup (completed: obsolete hero/floating CSS removed from role layouts and shared styles partial)

Done criteria:
- Facility modules can operate independently of single-patient pages.
- Integration bridges with patient modules are complete and auditable.

## Phase D: Reporting and NHMIS Completion
1. Complete 187-field NHMIS mapping matrix.
2. Replace heuristic mappings with structured source fields where possible.
3. Add mapping verification checklist and regression tests.
4. Reports Hub performance hardening (completed: preloaded NHMIS context reuse, select-only report queries, grouped facility aggregation for immunization summaries, additional facility/date composite indexes, heavy-window timing logs).
5. MPDSR surveillance hardening (completed: death surveillance analytics rebuilt from delivery records, response issue queue added, facility/date/death-type filtering aligned, CSV surveillance export enabled, and printable MPDSR review sheet enabled at `/analytics/mpdsr-report-dashboard/print`).

Done criteria:
- Monthly report has clear source traceability for each mapped field.
- Gap document shows no critical unmapped production metrics.

## Deferred To Version 2
1. Pharmacy & Drug advanced supplier/procurement + LMIS full operations.
2. V2 LMIS scope (not in current V1 sprint):
   - Supplier master management
   - Purchase order workflow
   - Goods received notes (GRN)
   - Stock valuation and procurement analytics
   - Multi-store transfer controls

## 4) Required Update Checklist Per Module Delivery

1. Implement route/component/view/model/migration changes.
2. Verify route visibility and dashboard behavior.
3. Run `php artisan route:list` and `php artisan test`.
4. Update:
   - `docs/APP1_MODULE_STATUS.md`
   - `docs/monthly-report-gap-check.md` (if aggregation affected)
   - `docs/handoff.md`
   - `docs/APP1_PRODUCTION_HARDENING_PASS.md` (final pre-go-live stage)
5. Table/UI enforcement:
   - Every operational table must ship with pagination + export controls.
   - Multi-metric summaries should use distinct, readable tinted cards (Flowdesk-inspired palette, no gold default).
6. Workflow documentation enforcement:
   - For every cross-module bridge, update workflow docs with:
     - source module action,
     - queue/pending state table,
     - completion/issue action,
     - downstream side effects (inventory, billing, timeline).

## 5) Phase 1 Completed: Role Sidebar and Account Settings

1. Added shared account workflow route:
   - `/account/settings` (`account-settings`)
2. Added account settings menu action on all role sidebars.
3. Added role alias runtime support:
   - `State Administrator`
   - `LGA Data Administrator`
   - `LGA Administrator`
4. Updated staff role navbar profile links to route to account settings.

## 6) Phase 2 Completed: Role Access + Sidebar Hardening

1. Sidebar active-state hardening:
   - All role menu blades now use a shared resolver (`RolePermissionService::isMenuNodeActive`) based on route name + URL path.
   - This prevents broken active/open menu highlighting when a slug drifts.
2. Account settings permission hardening:
   - Added explicit permission key `account.settings.manage`.
   - `/account/settings` is now protected by `permission.check:account.settings.manage`.
   - Default matrix allows this permission for all authenticated roles.
3. Role-menu metadata cleanup:
   - Corrected menu slug mismatches (`risk-dashboard`, `mpdsr-report-dashboard`, `patient-dashboard`) in role JSON menus.
4. Regression safety:
   - Added automated role-menu configuration tests:
     - URL path must map to registered route URI.
     - Leaf `slug` must map to registered route name.

## 7) Phase 3 Completed: Central Governance + Role Workflow Additions

1. Central governance pages:
   - Added `/central/audit-trail` and `/central/platform-notifications`.
   - Wired permission checks:
     - `central.audit_trail.view`
     - `central.notifications.view`
   - Added Central sidebar governance links.
2. State/LGA monitoring upgrades:
   - State dashboard: facility performance table + report submission completeness monitor (activity-based).
   - LGA dashboard: facility comparison + overdue report tracker by facility.
3. Facility Admin oversight shortcut:
   - Facility admin can open patient workspace via guarded `workspace.dashboard.view`.
   - Role middleware updated to allow `workspaces/*` for Facility Administrator.
4. Data Officer quick queue workflow:
   - Added `/workspaces/pending-queues` with pending lab, prescription, and reminder lists.
   - Added sidebar link in data officer menu.
5. Verification Officer workflow polish:
   - DIN activations now include `Export Today's Check-Ins` CSV.
   - Added activation history table (last 30 days).
   - Removed malformed/placeholder row action.
6. Patient account workflow clarity:
   - Patient portal now includes explicit `Open Account Settings` CTA.
7. Core navbar template rule respected:
   - Navbar framework/structure was restored unchanged after user directive.
   - No core navbar template replacement retained in this phase.
