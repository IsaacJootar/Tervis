# APP1 Module Status (Vision-Aligned Ground Truth)

Last updated: 2026-03-17  
Reference vision: `Health Management System - Complete Architecture (Patient + Facility Modules).pdf` (v2.0, Nov 2025)

Status legend:
- `Implemented`: Routable and operational in current code.
- `Partial`: Available but not yet complete to vision scope.
- `Not Started`: No working module route/workflow yet.

## 1) Patient-Centric Modules (17 Dashboard Sections)

| Vision Section | App1 Route / Entry | Status | Notes |
| --- | --- | --- | --- |
| 1. Attendance | `workspaces/{patientId}/attendance` (`workspaces-attendance`) | Implemented | Daily attendance/check-in activity. |
| 2. Doctor Assessment | `workspaces/{patientId}/assessments` (`workspaces-assessments`) | Implemented | Includes assessment narrative, pending test orders, pending drug orders. |
| 3. TT Vaccination | `workspaces/{patientId}/anc/tetanus-vaccinations` | Implemented | Delivered inside ANC workspace. |
| 4. Deliveries | `workspaces/{patientId}/anc/deliveries` | Implemented | Includes linked child creation flow. |
| 5. Postnatal Care | `workspaces/{patientId}/anc/postnatal` | Implemented | ANC workspace section. |
| 6. Immunizations | `workspaces/{patientId}/child-health/immunizations` | Implemented | Includes Routine Schedule + AEFI updates. |
| 6b. Vaccination Schedule (project-specific split) | `workspaces/{patientId}/child-health/vaccination-schedule` | Implemented | Former `activity-register` redirected to this route. |
| 7. Nutrition | `workspaces/{patientId}/child-health/nutrition` | Implemented | Child nutrition activity module. |
| 8. Tests & Lab | `workspaces/{patientId}/laboratory` | Implemented | Laboratory record capture + pending test completion flow. |
| 9. Prescriptions | `workspaces/{patientId}/prescriptions` | Implemented | Cart-based dispensing workflow. |
| 9b. Drug Catalog (supporting page) | `workspaces/{patientId}/drug-catalog` | Implemented | Facility-level catalog CRUD for dispensing. |
| 10. Invoices & Payments | `workspaces/{patientId}/invoices` (`workspaces-invoices`) | Implemented | Invoice creation from prescriptions + payment recording workflow active. |
| 11. Appointments | `workspaces/{patientId}/appointments` (`workspaces-appointments`) | Implemented | Aggregated from Doctor Assessment, TT, ANC follow-up, and Family Planning next-visit fields. |
| 12. Referrals | `workspaces/{patientId}/referrals` (`workspaces-referrals`) | Implemented | Referral form module with services checklist, outcome tracking, and history table. |
| 13. Reminders & Alerts | `workspaces/{patientId}/reminders` (`workspaces-reminders`) | Implemented | Reminder workspace with module-date sync and placeholder SMS/Email dispatch logs. |
| 14. Family Planning | `workspaces/{patientId}/family-planning` (`workspaces-family-planning`) | Implemented | Follow-up assessment workspace for subsequent visits; one-time baseline remains in `registers/family-planning-register`. |
| 15. Health Insurance | `workspaces/{patientId}/health-insurance` (`workspaces-health-insurance`) | Implemented | Patient insurance management hub for activate/deactivate, provider/plan/expiry updates, and audit history. Initial NHIS capture is now aligned across all 3 register entry points. |
| 16. Visits | `workspaces/{patientId}/visits` (`workspaces-visits`) | Implemented | Auto-collates facility-scoped visit sessions from DIN activations + activity timeline events. |
| 17. Timeline / Activities | `workspaces/{patientId}/activities` (`workspaces-activities`) | Implemented | Enriched timeline delivered: latest-first table with export/pagination controls, module/action summaries, and KPI cards. |

## 2) Facility-Centric Modules (Vision Scope)

Note: Vision text mentions both "7 modules" and "8 management modules" in different sections; detailed list resolves to 7 core facility modules.

| Vision Facility Module | Current App1 Status | Notes |
| --- | --- | --- |
| Bed Management | Implemented | Facility-scoped bed catalog and status management delivered (`/core/bed-management`) with section linkage + occupancy state updates. |
| Pharmacy & Drug Management | Partial | Facility pharmacy operations now include stock-in batches, adjustments, reorder levels, and movement logs (`/core/pharmacy-operations`) with dispensing stock deduction wired; advanced supplier/LMIS procurement flow remains. |
| Admitted Patients (Inpatient) | Implemented | Admission/discharge/referral workflow delivered (`/core/admitted-patients`) with bed occupancy sync + activity timeline logging. |
| Laboratory Management | Implemented | Facility operations module delivered at `/core/laboratory-operations` with pending order intake queue, sample tracking, processing batches, QC logs, reagent inventory/movements, and equipment calibration-maintenance logs. |
| Staff Management | Implemented | Unified facility-scoped staff module delivered at `/core/staff-management` (create/update profile, designation-role alignment, activate/disable, department assignment, password reset, and audit trail) with legacy Data Officer routes redirected to avoid duplicate modules. |
| Facility Administration | Implemented | Dedicated module delivered at `/core/facility-administration` with facility profile settings, service catalog CRUD, fee schedule CRUD (active schedule control), module access toggles, and administration audit trail. |
| Reporting & Analytics | Partial | Monthly report dashboard exists with partial NHMIS mapping; full 187-field complete mapping still pending. |

## 3) Integration Bridges (Vision-Critical)

| Integration Bridge | Status | Current Implementation |
| --- | --- | --- |
| Doctor Assessment -> Lab Orders | Implemented | `DoctorAssessments` creates `LabTestOrder` records as `pending`. |
| Doctor Assessment -> Prescriptions | Implemented | `DoctorAssessments` creates `Prescription` records as `pending`. |
| Laboratory -> Mark Ordered Tests Completed | Implemented | Save requires pending selection when pending exists; selected orders set to `completed`. |
| Prescriptions Checkout -> Resolve Pending Prescriptions | Implemented | Selected pending prescriptions required for checkout; dispensed lines saved and linked records marked `dispensed`. |
| Child Health -> Monthly NHMIS Aggregation | Partial | Immunization/nutrition/vaccination schedule wired; some NHMIS row mappings still pending. |
| Doctor/Lab -> Monthly NHMIS Aggregation | Partial | Keyword/structured extraction present; requires stronger structured mapping later. |
| Inpatient -> Monthly NHMIS Aggregation | Implemented | Monthly inpatient totals now sourced from `inpatient_admissions` (admissions/discharges) instead of delivery proxy. |
| Prescriptions -> Pharmacy Inventory | Implemented | Patient dispensing checkout now deducts facility stock using FIFO batches and logs stock movement records. |

## 4) Testing & Quality Snapshot

- `php artisan route:list` passes (routes compile and load).
- Automated test suite currently only has example tests (`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`).
- Gap: No dedicated feature tests yet for Doctor/Lab/Prescriptions/Child Health/Health Insurance module workflows.

## 5) Immediate Priority Recommendation

1. Add dedicated feature tests for Facility Administration and Bed/Sections/Inpatient admission-discharge-referral transitions.
2. Continue NHMIS mapping hardening with structured source coverage.
3. Add dedicated feature tests for activities/reminders/appointments aggregation chains.
