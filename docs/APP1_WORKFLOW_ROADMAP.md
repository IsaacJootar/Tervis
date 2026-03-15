# APP1 Workflow and Roadmap

Last updated: 2026-03-14  
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
8. Monthly report:
   - Monthly dashboard aggregates mapped data from attendance, maternal/child, doctor, and lab sources.

## 2) Workflow Gaps (Not Yet Closed)

1. Patient Section 13-16 gaps:
   - Reminders, Health Insurance, Visits grouping.
2. Family Planning workspace module:
   - Register exists but dedicated workspace activity module not yet active.
3. Facility operations:
   - Bed management and admitted-patient workflows are missing.
   - Pharmacy/Lab operations are still patient-form-centric, not full facility-operations grade.
4. NHMIS completeness:
   - Monthly mapping is partial; additional row-key mappings and stronger structured sources are needed.
5. Test coverage:
   - No dedicated workflow feature tests yet for Doctor/Lab/Prescriptions/Child Health chains.

## 3) Execution Plan (Next Modules, In Order)

## Phase A: Close Remaining Patient Dashboard Core
1. Reminders module (Section 13)

Done criteria:
- Routed module pages exist from dashboard.
- CRUD and workflow transitions completed.
- Loading states and feedback implemented.
- Monthly mapping impact documented.

## Phase B: Complete Patient Support and Longitudinal Tracking
1. Family Planning workspace activity module (Section 14 alignment)
2. Health Insurance module (Section 15)
3. Visits session grouping module (Section 16)
4. Timeline enrichment improvements (Section 17 quality upgrade)

Done criteria:
- Full patient workflow chain can run without side systems.
- Each module has activity/audit logging and dashboard counts.

## Phase C: Facility Operations Buildout
1. Bed Management
2. Admitted Patients (inpatient)
3. Pharmacy & Drug full ops (inventory/stock/expiry/supplier)
4. Laboratory full ops (catalog/sample/batch/QC/reagent/equipment)
5. Staff Management
6. Facility Administration hardening

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
