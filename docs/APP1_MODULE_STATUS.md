# APP1 Module Status (Vision-Aligned Ground Truth)

Last updated: 2026-03-20  
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
| 13. Reminders & Alerts | `workspaces/{patientId}/reminders` (`workspaces-reminders`) | Implemented | Reminder workspace with module-date sync, facility-admin dispatch flow, Termii SMS (config-enabled), and dispatch/delivery logs. |
| 14. Family Planning | `workspaces/{patientId}/family-planning` (`workspaces-family-planning`) | Implemented | Follow-up assessment workspace for subsequent visits; one-time baseline remains in `registers/family-planning-register`. |
| 15. Health Insurance | `workspaces/{patientId}/health-insurance` (`workspaces-health-insurance`) | Implemented | Patient insurance management hub for activate/deactivate, provider/plan/expiry updates, and audit history. Initial NHIS capture is now aligned across all 3 register entry points. |
| 16. Visits | `workspaces/{patientId}/visits` (`workspaces-visits`) | Implemented | Auto-collates facility-scoped visit sessions from DIN activations + activity timeline events. |
| 17. Timeline / Activities | `workspaces/{patientId}/activities` (`workspaces-activities`) | Implemented | Enriched timeline delivered: latest-first table with export/pagination controls, module/action summaries, and KPI cards. |

## 2) Facility-Centric Modules (Vision Scope)

Note: Vision text mentions both "7 modules" and "8 management modules" in different sections; detailed list resolves to 7 core facility modules.

| Vision Facility Module | Current App1 Status | Notes |
| --- | --- | --- |
| Bed Management | Implemented | Facility-scoped bed catalog and status management delivered (`/core/bed-management`) with section linkage + occupancy state updates. |
| Pharmacy & Drug Management | Partial (Deferred To V2 - Frozen In V1) | Facility pharmacy operations now include stock-in batches, adjustments, reorder levels, and movement logs (`/core/pharmacy-operations`) with dispensing stock deduction wired. Advanced supplier/procurement + LMIS full operations are intentionally deferred to Version 2 by product decision. |
| Admitted Patients (Inpatient) | Implemented | Admission/discharge/referral workflow delivered (`/core/admitted-patients`) with bed occupancy sync + activity timeline logging. |
| Laboratory Management | Implemented | Facility operations module delivered at `/core/laboratory-operations` with pending order intake queue, sample tracking, processing batches, QC logs, reagent inventory/movements, and equipment calibration-maintenance logs. |
| Staff Management | Implemented | Unified facility-scoped staff module delivered at `/core/staff-management` (create/update profile, designation-role alignment, activate/disable, department assignment, password reset, and audit trail) with legacy Data Officer routes redirected to avoid duplicate modules. |
| Facility Administration | Implemented | Dedicated module delivered at `/core/facility-administration` with facility profile settings, service catalog CRUD, fee schedule CRUD (active schedule control), module access status view, and administration audit trail. |
| Reporting & Analytics | Implemented | Dedicated Reports Hub delivered at `/core/reports-hub` with section/report-name/date/facility filters, generation history, DataTables pagination/export, CSV export, and printable template report view; monthly NHMIS key mapping includes immunization, child-health, and pharmacy indicators, and now uses canonical matrix registry (`app/Services/Reports/NhmisFieldRegistry.php` + `docs/nhmis-field-matrix.json`) plus per-row fallback resolver hardening (`app/Services/Reports/NhmisFieldValueResolver.php`). MPDSR surveillance was hardened at `/analytics/mpdsr-report-dashboard` with real death signal extraction, cause/time/facility analytics, surveillance issue queue, CSV surveillance export, and printable review sheet route (`/analytics/mpdsr-report-dashboard/print`). |

## 3) Integration Bridges (Vision-Critical)

| Integration Bridge | Status | Current Implementation |
| --- | --- | --- |
| Doctor Assessment -> Lab Orders | Implemented | `DoctorAssessments` creates `LabTestOrder` records as `pending`. |
| Doctor Assessment -> Prescriptions | Implemented | `DoctorAssessments` creates `Prescription` records as `pending`. |
| Laboratory -> Mark Ordered Tests Completed | Implemented | Save requires pending selection when pending exists; selected orders set to `completed`. |
| Prescriptions Checkout -> Resolve Pending Prescriptions | Implemented | Selected pending prescriptions required for checkout; dispensed lines saved and linked records marked `dispensed`. |
| Child Health -> Monthly NHMIS Aggregation | Implemented | Immunization + nutrition + vaccination schedule are deduplicated and mapped into NHMIS summary keys. |
| Doctor/Lab -> Monthly NHMIS Aggregation | Implemented | Structured indicators for malaria/TB/HepB/HepC/GBV and monthly summary integration are active. |
| Inpatient -> Monthly NHMIS Aggregation | Implemented | Monthly inpatient totals now sourced from `inpatient_admissions` (admissions/discharges) instead of delivery proxy. |
| Prescriptions -> Pharmacy Inventory | Implemented | Patient dispensing checkout now deducts facility stock using FIFO batches and logs stock movement records. |

## 3b) Module Access Governance

| Governance Rule | Status | Current Implementation |
| --- | --- | --- |
| Module enable/disable controlled from Central | Implemented | Central admin route `/central/facility-module-management` owns facility module toggles. |
| Facility-side toggle control removed | Implemented | `/core/facility-administration` now shows module status as read-only. |

## 3c) Central Admin Surface

| Central Area | Status | Current Implementation |
| --- | --- | --- |
| Central Dashboard | Implemented | `/central/central-admin-dashboard` now shows live cross-facility metrics, module distribution, dispatch logs, and quick actions. |
| Central Sidebar Links | Implemented | `resources/menu/centralAdminMenu.json` now contains only valid central route targets (no `/core/*` entries). |
| Central Route Boundary | Implemented | `UserRoleMiddleware` now restricts central roles to `/central/*`; central actions trigger facility outcomes by assignment/configuration flows. |
| Legacy Central URL Compatibility | Implemented | Deprecated `/central-admin/*` links now redirect to active central destinations to prevent broken bookmarks. |

## 3d) RBAC Governance

| RBAC Area | Status | Current Implementation |
| --- | --- | --- |
| Central Roles & Permissions Module | Implemented | `/central/roles-permissions` allows Central Admin to toggle role-permission matrix entries. |
| Permission Middleware Enforcement | Implemented | `permission.check:{permission_key}` is wired across central/core/workspace/register/analytics/avo routes. |
| Default + Override Model | Implemented | Defaults come from `RolePermissionService`; Central overrides persist in `role_permissions`. |

## 3e) AI Assistant Advisory Layer

| Area | Status | Current Implementation |
| --- | --- | --- |
| Workspace/Operations AI Assistant | Implemented (Advisory) | Flowdesk-style side panel with `Use AI Assistant` / `Hide AI Assistant`, risk flags, and suggestions (no auto-actions). |
| No AI Gate Policy (Current Rollout) | Implemented | AI assistant visibility is not controlled by separate AI entitlement flags in App1; normal module permission/middleware still applies. |
| Initial Coverage | Implemented | Doctor Assessments, Laboratory, Prescriptions, Child Vaccination Schedule, and Facility Reminders Hub. |

## 3f) Core UI Modernization

| Area | Status | Current Implementation |
| --- | --- | --- |
| Legacy Hero Header Removal (Core + Active Role Pages) | Implemented | Legacy hero/floating-shape headers were removed from core operations and active role pages, including data-officer management pages, facility patients, patient appointments, LGA/State dashboards, AVO DIN activations, and legacy patient portal pages; shared dead hero/floating CSS was also removed from role layouts and `layouts/sections/styles.blade.php`. |

## 3g) Role Sidebar and Account Settings Hardening (Phase 1)

| Area | Status | Current Implementation |
| --- | --- | --- |
| Shared Account Settings | Implemented | New route `GET /account/settings` (`account-settings`) with profile update + password change for all authenticated roles. |
| Sidebar Coverage | Implemented | Added `Account Settings` menu entry to Central Admin, State Officer, LGA Officer, Facility Admin, Data Officer, Verification Officer, and Patient sidebars. |
| Role Alias Coverage | Implemented | Added runtime support for `State Administrator`, `LGA Data Administrator`, and `LGA Administrator` in login redirect, role middleware, permissions default matrix, and analytics/report layout selection. |
| Navbar My Profile Linking | Implemented (staff roles) | Staff role navbar profile links now target `account-settings` instead of placeholder/dead links. |

## 3h) Role Access and Sidebar Hardening (Phase 2)

| Area | Status | Current Implementation |
| --- | --- | --- |
| Sidebar Active-State Consistency | Implemented | Role sidebars now use shared active resolver `RolePermissionService::isMenuNodeActive()` (route + path), removing fragile slug-only matching. |
| Account Settings Permission Enforcement | Implemented | Added `account.settings.manage` permission key and route middleware on `/account/settings` (`permission.check:account.settings.manage`) with default allow for authenticated roles. |
| Role Menu Metadata Hygiene | Implemented | Corrected role-menu slug mismatches (`risk-dashboard`, `mpdsr-report-dashboard`, `patient-dashboard`) so route-name alignment remains clean. |
| Menu Regression Guard | Implemented | Added `tests/Feature/RoleMenuConfigurationTest.php` to enforce role-menu URL/slug validity against route list. |

## 3i) Role and Central Enhancements (Phase 3)

| Area | Status | Current Implementation |
| --- | --- | --- |
| Central Governance Pages | Implemented | Added `Central Audit Trail` (`/central/audit-trail`) and `Platform Notifications` (`/central/platform-notifications`) with central permission middleware and menu wiring. |
| State Officer Monitoring Additions | Implemented | State dashboard now includes facility performance table + activity-based report submission completeness monitor. |
| LGA Officer Monitoring Additions | Implemented | LGA dashboard now includes facility comparison table + overdue report tracker by facility. |
| Facility Admin Quick Workspace Access | Implemented | Facility admin role now has `Open Patient Workspace` shortcut and `workspace.dashboard.view` permission with guarded route access. |
| Data Officer Pending Queue | Implemented | Added `Pending Queues` page (`/workspaces/pending-queues`) showing pending lab orders, pending prescriptions, and due reminders with direct module links. |
| Verification Officer History + Export | Implemented | DIN Activations now includes CSV export for today and a 30-day activation history table; malformed action markup was removed. |
| Patient Password/Account Path | Implemented | Patient portal now includes explicit CTA to `Account Settings` for profile/password updates. |
| Navbar Core Template Preservation | Implemented | Role navbar core template structure was restored after enhancement pass; feature links are delivered through route/menu/module pages, not navbar framework replacement. |

## 4) Testing & Quality Snapshot

- `php artisan route:list` passes (routes compile and load).
- `module.enabled` route-level enforcement is active for key workspace/core module routes.
- Automated tests now include:
  - NHMIS registry/value resolver unit tests
  - module access middleware feature tests (`tests/Feature/ModuleEnabledMiddlewareTest.php`)
  - role permission middleware feature tests (`tests/Feature/RolePermissionMiddlewareTest.php`)
  - role menu configuration regression checks (`tests/Feature/RoleMenuConfigurationTest.php`)
  - facility administration workflow transitions (`tests/Feature/FacilityAdministrationWorkflowTest.php`)
  - bed/sections/inpatient workflow transitions (`tests/Feature/CoreFacilityWorkflowTransitionsTest.php`)
  - workspace aggregation + clinical order bridge checks (`tests/Feature/WorkspaceAggregationChainTest.php`), including Doctor -> pending orders -> dispensing -> invoice creation path.
  - strict-scope cross-facility checks in workspace flows (`tests/Feature/WorkspaceAggregationChainTest.php`):
    - one-time Family Planning registration gate recognized across facilities during patient verification
    - appointments and activity totals remain facility-scoped for the same patient
  - strict-scope registration gate checks (`tests/Feature/RegistrationStrictScopeGateTest.php`):
    - General Patients register blocks duplicate foundational registration across facilities at DIN verification
    - ANC register blocks duplicate ANC baseline registration across facilities at DIN verification
  - monthly NHMIS drift regression checks (`tests/Feature/MonthlyNhmisSummaryDriftTest.php`):
    - Jan-only, Feb-only, and combined Jan-Feb windows validated against generated `summary_key_values`
    - multi-quarter stability checks (Q1, Q2, and half-year windows) with repeat-run assertions for drift safety
    - high-volume monthly dataset check (120 prescriptions + 120 dispense lines) with deterministic totals validation
    - full-year high-volume mixed-module check (96 child-health + 96 pharmacy bundles) with deterministic year/Q4 totals and no carry-over drift
    - de-duplication of same-subject/same-date dose entries across Immunization + Vaccination Schedule sources
    - facility-scope isolation validated against cross-facility noise records
    - repeat generation of a narrower window after wider window run verifies no carry-over drift
  - Reports Hub performance hardening validation:
    - query tuning in `FacilityReports` (select-only queries + grouped facility reductions)
    - NHMIS resolver preload support to avoid duplicate context reloads
    - composite index migration for report date filters (`2026_03_19_120000_add_reports_hub_performance_indexes.php`)
  - Reports Hub smoke coverage (`tests/Feature/ReportsHubSmokeTest.php`):
    - all configured report keys generate successfully without exceptions
  - module access middleware isolation now runs without skip for second-facility setup (`tests/Feature/ModuleEnabledMiddlewareTest.php`).
  - child health + health insurance workflow transitions and validations (`tests/Feature/ChildHealthInsuranceWorkflowTest.php`).
- Remaining gap: optional periodic mega-scale benchmark reruns in production-like datasets for capacity planning.

## 5) Immediate Priority Recommendation

1. Run periodic mega-scale (>10k rows/window) benchmark reruns in staging/production-like data windows.
2. Maintain mapping coverage as new template keys or module fields are introduced.
3. Keep strict-scope gate tests updated when new one-time registers are introduced.
