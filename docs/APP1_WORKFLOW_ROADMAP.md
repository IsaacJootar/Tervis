# APP1 Workflow and Roadmap

Last updated: 2026-03-17  
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
   - Due reminders dispatch through placeholder SMS/Email services with per-channel dispatch logs.
9. Insurance management:
   - Initial NHIS capture is aligned across all register entry points (General, ANC, Family Planning).
   - Health Insurance workspace manages activation/deactivation and NHIS detail updates with audit history.
10. Visit collation:
   - Visits module auto-groups visit sessions by date from DIN activation + activity records.
   - Sync is automatic on activity/activation create-update-delete events (staff do not click manual sync).
   - Visit events remain facility-scoped and are sync/backfill-safe.
11. Monthly report:
   - Monthly dashboard aggregates mapped data from attendance, maternal/child, doctor, and lab sources.

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
1. Reminders module (Section 13) - completed with placeholder delivery stack.

Done criteria:
- Routed module pages exist from dashboard.
- CRUD and workflow transitions completed.
- Loading states and feedback implemented.
- Placeholder dispatch command available: `php artisan reminders:dispatch-due --sync`.

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
3. Pharmacy & Drug full ops (inventory foundation delivered: stock-in, adjustments, reorder levels, movement logs, dispense deduction; advanced supplier/LMIS remains)
4. Laboratory full ops (completed: facility queue intake, sample tracking, processing batches, QC, reagents/movements, equipment logs)
5. Staff Management (completed: unified facility staff module, legacy Data Officer pages redirected)
6. Facility Administration hardening (completed: profile settings, service catalog, fee schedules, module access control, audit trail)

Done criteria:
- Facility modules can operate independently of single-patient pages.
- Integration bridges with patient modules are complete and auditable.

## Phase D: Reporting and NHMIS Completion
1. Complete 187-field NHMIS mapping matrix.
2. Replace heuristic mappings with structured source fields where possible.
3. Add mapping verification checklist and regression tests.

Done criteria:
- Monthly report has clear source traceability for each mapped field.
- Gap document shows no critical unmapped production metrics.

## 4) Required Update Checklist Per Module Delivery

1. Implement route/component/view/model/migration changes.
2. Verify route visibility and dashboard behavior.
3. Run `php artisan route:list` and `php artisan test`.
4. Update:
   - `docs/APP1_MODULE_STATUS.md`
   - `docs/monthly-report-gap-check.md` (if aggregation affected)
   - `docs/handoff.md`
5. Table/UI enforcement:
   - Every operational table must ship with pagination + export controls.
   - Multi-metric summaries should use distinct, readable tinted cards (Flowdesk-inspired palette, no gold default).
6. Workflow documentation enforcement:
   - For every cross-module bridge, update workflow docs with:
     - source module action,
     - queue/pending state table,
     - completion/issue action,
     - downstream side effects (inventory, billing, timeline).
