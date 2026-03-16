# APP1 Module Status (Vision-Aligned Ground Truth)

Last updated: 2026-03-15  
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
| 14. Family Planning | No active workspace route | Partial | Register exists (`registers/family-planning-register`) but workspace activity module not active. |
| 15. Health Insurance | No active workspace route | Not Started | No active module route/workflow. |
| 16. Visits | No active workspace route | Not Started | Visit grouping module not yet delivered. |
| 17. Timeline / Activities | `workspaces/{patientId}/activities` (`workspaces-activities`) | Implemented | Activity timeline page active (latest-first behavior requested earlier). |

## 2) Facility-Centric Modules (Vision Scope)

Note: Vision text mentions both "7 modules" and "8 management modules" in different sections; detailed list resolves to 7 core facility modules.

| Vision Facility Module | Current App1 Status | Notes |
| --- | --- | --- |
| Bed Management | Not Started | No beds/wards/room allocation module delivered. |
| Pharmacy & Drug Management | Partial | Drug catalog + dispensing implemented, but no stock-in/expiry/reorder/supplier/LMIS workflow yet. |
| Admitted Patients (Inpatient) | Not Started | No admission, bed assignment, discharge workflow module yet. |
| Laboratory Management | Partial | Patient lab form and results exist; facility-level sample batching, QC, reagents, equipment calibration not yet delivered. |
| Staff Management | Partial | User/officer management exists in core admin pages; full staff lifecycle module not yet implemented. |
| Facility Administration | Partial | Facility creation/config pages exist; full service catalog/fee schedule/permissions/audit administration remains incomplete. |
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

## 4) Testing & Quality Snapshot

- `php artisan route:list` passes (routes compile and load).
- Automated test suite currently only has example tests (`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`).
- Gap: No dedicated feature tests yet for Doctor/Lab/Prescriptions/Child Health module workflows.

## 5) Immediate Priority Recommendation

1. Complete Section 14-16 (Family Planning workspace module, Insurance, Visits grouping).
2. Then execute facility modules in order: Bed -> Inpatient -> full Pharmacy Ops -> full Lab Ops.
3. Add dedicated workflow tests for reminder sync/dispatch and cross-module transitions.
