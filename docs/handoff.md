# Handoff Notes (2026-02-09)

## Summary
- ANC module completed and consolidated under ANC Workspace (TT, Delivery, Postnatal, Follow-up Assessment).
- Delivery links children (LinkedChild) with per-baby fields.
- Nutrition module has now been implemented under Child workspace with full CRUD + smart derived fields.
- Monthly summary child-health mapping now reads from nutrition records for core nutrition indicators.

## Key Decisions
- Keep DataTable default markup (same as registers) in modules; avoid partials for now.
- ANC is a single workspace that hosts multiple activities (Follow-up Assessment, TT, Delivery, Postnatal).
- Follow-up Assessment is a full CRUD activity (not a static form).
- Attendance is read-only; no modal.

## Files Changed
- `app/Livewire/Workspaces/WorkspaceDashboard.php`
  - ANC card shows Total ANC Visits and includes follow-up count.
  - `backToPatientWorkspace()` route now `patient-workspace`.
- `resources/views/livewire/workspaces/workspace-dashboard.blade.php`
  - Back label now “Back to Patient Activations”.
- `resources/views/livewire/partials/workspace-card.blade.php`
  - Cards always active (no disabled blur).
- `app/Livewire/Workspaces/Modules/ANC/AncOverview.php`
  - New ANC Workspace hub (patient access + quick links).
- `resources/views/livewire/workspaces/modules/anc/index.blade.php`
  - ANC Workspace landing page.
- `app/Livewire/Workspaces/Modules/ANC/FollowUpAssessment.php`
  - Full CRUD, validation formats, officer fields stored + original officer displayed on edit.
- `app/Models/AntenatalFollowUpAssessment.php`
  - Fillable/casts for follow-up activity.
- `database/migrations/2026_02_08_000000_create_antenatal_follow_up_assessments_table.php`
  - Follow-up table with officer fields.
- `resources/views/livewire/workspaces/modules/anc/follow-up-assessment.blade.php`
  - TT-style layout, mobile fixes, unified form section, action buttons, officer block.
- `app/Livewire/Workspaces/Modules/ANC/Deliveries.php`
  - Per‑baby fields + linked child creation on delivery create/update.
- `resources/views/livewire/workspaces/modules/anc/deliveries.blade.php`
  - “Baby Details” section with Add/Remove Baby and link label.
- `database/migrations/2026_02_08_010000_create_linked_children_table.php`
  - New linked_children table (required for immunization/nutrition).
- `database/migrations/2026_02_09_000100_create_nutrition_records_table.php`
  - New nutrition records table with summary mapping payload.
- `app/Models/NutritionRecord.php`
  - Nutrition domain model with casts + relationships.
- `app/Livewire/Workspaces/Modules/Child/Nutrition.php`
  - Child Nutrition workspace module with auto-derived age group/MUAC class, officer capture, activity logs.
- `resources/views/livewire/workspaces/modules/child/nutrition.blade.php`
  - Nutrition UI aligned to workspace pattern (patient context, table, modal CRUD).
- `resources/views/livewire/workspaces/modules/anc/deliveries.blade.php`
  - Back label “Back to ANC Workspace”.
- `resources/views/livewire/workspaces/modules/anc/postnatal.blade.php`
  - Back label “Back to ANC Workspace”.
- `resources/views/livewire/workspaces/modules/anc/tetanus-vaccinations.blade.php`
  - Back label “Back to ANC Workspace”.
  - Access card link now `patient-workspace`, label “Back to Patient Activations”.
- `routes/web.php`
  - Removed deleted register routes; only 3 entry points remain.
  - ANC workspace route: `/workspaces/{patientId}/anc` named `workspaces-antenatal`.
  - Follow-up route: `/workspaces/{patientId}/anc/follow-up-assessment` named `workspaces-antenatal-followup`.
  - Nutrition route: `/workspaces/{patientId}/child-health/nutrition` named `workspaces-child-health-nutrition`.
- `app/Livewire/Login.php`
  - Data Officer redirect now `patient-workspace`.
- `app/Http/Middleware/UserRoleMiddleware.php`
  - Data Officer index now `patient-workspace`.
- `app/Livewire/Workspaces/WorkspaceDashboard.php`
  - Linked children source corrected to `linkedChildren` relation.
  - Nutrition card count now uses `NutritionRecord`.
- `app/Livewire/Analytics/MonthlyReportDashboard.php`
  - Child health section now aggregates nutrition-driven indicators:
    `exclusive_breastfeeding`, `muac_screened`, `sam_new_cases`, `mam_new_cases`, `sam_admissions`, `mnp_given`, `not_growing_well`.
- `resources/MONTHLY NHMIS REPORT.html`
  - Added `data-summary-key` markers for nutrition indicators to show where monthly values should populate.
- `resources/menu/dataOfficerMenu.json`, `resources/menu/avoOfficerMenu.json`
  - Menus cleaned to remove deleted register pages.
- `resources/views/layouts/sections/navbar/*OfficerNavbar-partial.blade.php`
  - Navbar shortcuts updated to only 3 entry registers + patient workspace.

## Folder Moves
- `app/Livewire/Workspaces/Antenatal/TetanusVaccinations.php`
  -> `app/Livewire/Workspaces/Modules/ANC/TetanusVaccinations.php`
- `resources/views/livewire/workspaces/antenatal/tetanus-vaccinations.blade.php`
  -> `resources/views/livewire/workspaces/modules/anc/tetanus-vaccinations.blade.php`
- Follow-up assessment moved from registers into ANC module as full CRUD.

## Open Items / Next Steps
1. Run `php artisan migrate` to create `linked_children` and `nutrition_records`.
2. Build Immunization module next using same linked child pattern as Nutrition.
3. Connect monthly dashboard to immunization tables once immunization module is live.
4. Decide if delivery should also capture birth length per baby (optional enhancement).

## Notes
- IDE tabs may still point to old paths (e.g., `workspaces/antenatal/...`). Close/reopen correct files.
- Multi-activity modules should group activities by tabs/sections later to avoid long pages.
## How To Start Tomorrow (Immunization + Nutrition)
1. Confirm `linked_children` migration applied.
2. Create Immunization module structure under `app/Livewire/Workspaces/Modules/Child` and matching view under `resources/views/livewire/workspaces/modules/child`.
3. Immunization records should reference `linked_child_id` (from `linked_children.linked_child_id` or numeric `id`—choose one and be consistent).
4. The Immunization page should list linked children for the mother/patient and allow selecting a child before recording immunization.
5. Nutrition module follows same pattern as Immunization: select linked child → record nutrition assessment → show table.

## Update (2026-03-03)
- Child Health `Activity Register` remains the route/module name internally, but the Immunizations-page CTA label is now **Vaccination Schedule**.
- Monthly report aggregation now reads from `TetanusVaccination`, `ImmunizationRecord`, `NutritionRecord`, and `ChildHealthActivityRecord`.
- Immunization totals now merge Immunization Register + Vaccination Schedule vaccine dates with deduplication by child and dose date.
- Mapped child vaccine fields: `bcg`, `opv0`, `opv1`, `opv2`, `opv3`, `penta1`, `penta2`, `penta3`, `pcv1`, `pcv2`, `pcv3`, `ipv1`, `ipv2`, `mcv1`, `mcv2`, `yf`, `hepb0`, `rota1`, `rota2`, `rota3`, `mena`, `vita1`, `vita2`, `hpv`.
- Child-health mapping now includes Vaccination Schedule contributions for `exclusive_breastfeeding` and `vitamin_a`, plus supplemental payload counters: `weight_monitoring_entries`, `aefi_reported_cases`.
- Monthly report UI notes were updated to reflect active merged immunization/child-health mapping.
- Validation run after wiring passed:
  - `php -l app/Livewire/Analytics/MonthlyReportDashboard.php`
  - `php -l resources/views/livewire/analytics/monthly-report-dashboard.blade.php`
  - `php artisan test` (`2 passed`)

## Update (2026-03-07)
- Monthly report `Other Services` mapping now includes Doctor Assessment + Laboratory data sources.
- `app/Livewire/Analytics/MonthlyReportDashboard.php` now reads:
  - `DoctorAssessment` for diagnosis/narrative signals (`malaria_cases`, `gbv_cases`)
  - `LabTest` for laboratory indicators (`malaria_tested`, `malaria_positive`, `tb_screening`, `hepb_tested`, `hepc_tested`)
- Mapping is currently heuristic for diagnosis text (keyword-based) and structured for lab JSON result fields.
- Docs synced:
  - `docs/monthly-report-gap-check.md`
  - `docs/handoff.md`
- Validation run after update passed:
  - `php -l app/Livewire/Analytics/MonthlyReportDashboard.php`
  - `php artisan test` (`2 passed`)

## Update (2026-03-11)
- Vision architecture PDF reviewed and converted into app-specific execution documentation baseline.
- Added new docs in `docs/` for clear governance and delivery tracking:
  - `APP1_MODULE_STATUS.md` (patient/facility module inventory with Implemented/Partial/Not Started states)
  - `APP1_CODING_RULES.md` (mandatory coding/UI/workflow/testing rules)
  - `APP1_UI_STYLE_GUIDE.md` (mobile-first UI rules for labels, tabs, sections, loading states, charts, and responsive behavior)
  - `APP1_WORKFLOW_ROADMAP.md` (current flow + phased next-module roadmap)
- UI direction decision captured: use Flowdesk-style clean design language for forms/modals/sections, while retaining App1 default blue as primary accent (gold is not default).
- Updated `docs/monthly-report-gap-check.md`:
  - status date refreshed to 2026-03-11
  - added explicit prescriptions/dispensing aggregation gap (`Prescription` + `DrugDispenseLine`)
  - linked the new documentation baseline.
- Route inventory re-validated via `php artisan route:list`.
- No runtime/business logic code changes were made in this update; this pass focused on documentation alignment and execution clarity.

## Update (2026-03-12)
- Immunization Register modal open flow standardized:
  - Added dedicated `openCreateModal()` opener in `Immunizations` component.
  - CTA now opens modal without calling `exit` (prevents immediate close/reload behavior).
  - Removed loading state text from the Immunization Register launcher button.
- Laboratory records table pagination aligned to register pattern:
  - Use `wire:ignore` + `id="dataTable"` + `@include('_partials.datatables-init')` for the primary history table.
- Governance updates for reuse in future modules:
  - `docs/APP1_UI_STYLE_GUIDE.md` updated with launcher-button and modal-open flow rules.
  - `docs/APP1_CODING_RULES.md` updated with interaction and table standardization rules.

## Update (2026-03-14)
- Appointments workspace module implemented and routed:
  - Route: `workspaces/{patientId}/appointments` (`workspaces-appointments`)
  - New files:
    - `app/Livewire/Workspaces/Modules/Appointments.php`
    - `resources/views/livewire/workspaces/modules/appointments/index.blade.php`
- Appointment sources now aggregated (no manual duplicate entry in appointments page):
  - `doctor_assessments.next_appointment_date`
  - `tetanus_vaccinations.next_appointment_date`
  - `antenatal_follow_up_assessments.next_return_date`
  - `family_planning_registrations.next_appointment`
- Appointment status logic:
  - `Fulfilled` if `din_activations.visit_date >= appointment_date` for same patient/facility
  - Else `Upcoming` (today/future) or `Missed` (past)
- Workspace dashboard card updates:
  - `appointments` card count now derived from aggregated module source counts (not `Appointment` model).
  - File: `app/Livewire/Workspaces/WorkspaceDashboard.php`
- Facility Reports appointment tracking upgraded to same multi-source aggregation:
  - File: `app/Livewire/Core/FacilityReports.php`
  - Now reports Doctor Follow-up, TT Vaccination, ANC Follow-up, and Family Planning Follow-up with status.
- Validation run:
  - `php -l app/Livewire/Workspaces/Modules/Appointments.php`
  - `php -l app/Livewire/Workspaces/WorkspaceDashboard.php`
  - `php -l app/Livewire/Core/FacilityReports.php`
  - `php artisan route:list --name=workspaces-appointments`
  - `php artisan test` (`2 passed`)
- Referrals workspace module implemented and routed:
  - Route: `workspaces/{patientId}/referrals` (`workspaces-referrals`)
  - New files:
    - `app/Models/Referral.php`
    - `database/migrations/2026_03_14_150000_create_referrals_table.php`
    - `app/Livewire/Workspaces/Modules/Clinical/Referrals.php`
    - `resources/views/livewire/workspaces/modules/clinical/referrals.blade.php`
  - Features:
    - Auto patient/facility context
    - Referral details capture
    - 26-item services checklist + "Others"
    - Service outcome/transport/time tracking
    - Authorization fields + record history CRUD
    - Activity timeline logging (`module = referrals`)
- Referral validation run:
  - `php artisan migrate --force`
  - `php artisan route:list --name=workspaces-referrals`
  - `php artisan test` (`2 passed`)
- Heavy-load readiness updates (server-side pagination + tenancy scoping) applied to clinical workspace tables:
  - Components updated to DB pagination (`paginate`) and bootstrap links:
    - `app/Livewire/Workspaces/Modules/Clinical/DoctorAssessments.php`
    - `app/Livewire/Workspaces/Modules/Clinical/Referrals.php`
    - `app/Livewire/Workspaces/Modules/Clinical/Laboratory.php`
    - `app/Livewire/Workspaces/Modules/Clinical/Prescriptions.php`
    - `app/Livewire/Workspaces/Modules/Clinical/Invoices.php`
  - Views updated to render server-side pagination links:
    - `resources/views/livewire/workspaces/modules/clinical/doctor-assessments.blade.php`
    - `resources/views/livewire/workspaces/modules/clinical/referrals.blade.php`
    - `resources/views/livewire/workspaces/modules/clinical/laboratory.blade.php`
    - `resources/views/livewire/workspaces/modules/clinical/prescriptions.blade.php`
    - `resources/views/livewire/workspaces/modules/clinical/invoices.blade.php`
  - Tenancy/facility scope preserved in all updated queries (`facility_id` and `patient_id` filters).

## Update (2026-03-15)
- Reminders workspace module implemented and routed:
  - Route: `workspaces/{patientId}/reminders` (`workspaces-reminders`)
  - New files:
    - `app/Livewire/Workspaces/Modules/Reminders.php`
    - `resources/views/livewire/workspaces/modules/reminders/index.blade.php`
    - `app/Models/Reminder.php`
    - `app/Models/ReminderDispatchLog.php`
    - `database/migrations/2026_03_15_090000_create_reminders_table.php`
    - `database/migrations/2026_03_15_090100_create_reminder_dispatch_logs_table.php`
- Placeholder communication stack added (Flowdesk-style queue/log separation):
  - `app/Services/Communication/SmsPlaceholderService.php`
  - `app/Services/Communication/EmailPlaceholderService.php`
  - `app/Services/Communication/ReminderDispatchService.php`
- Reminder orchestration behavior:
  - Sync source dates from:
    - `doctor_assessments.next_appointment_date`
    - `tetanus_vaccinations.next_appointment_date`
    - `antenatal_follow_up_assessments.next_return_date`
    - `family_planning_registrations.next_appointment`
  - Upsert central reminder records per source module/source record.
  - Dispatch due reminders via placeholder SMS/Email and write per-channel delivery logs.
  - Reminder actions are activity-logged under `module = reminders`.
- Console command added:
  - `php artisan reminders:dispatch-due --sync`
  - Optional scope filters: `--facilityId=` and `--patientId=`.
- Facility admin reminders hub UI hardening:
  - `resources/views/livewire/core/facility-reminders-hub.blade.php`
    - Both tables now use DataTable controls with pagination/search/export buttons.
    - Removed duplicate Laravel paginator blocks from those tables.
    - Summary cards switched to distinct Flowdesk-style tinted metric cards.
  - `app/Livewire/Core/FacilityRemindersHub.php`
    - Reminders and dispatch logs now return filtered collections for DataTable rendering.
    - Dispatch log query now respects date/channel/search filters from hub controls.
- Stat-card scope alignment update:
  - Tinted metric-card style applied to stat/summary cards only in:
    - `resources/views/livewire/workspaces/modules/appointments/index.blade.php`
    - `resources/views/livewire/workspaces/modules/reminders/index.blade.php`
    - `resources/views/livewire/workspaces/patient-workspace.blade.php` (dashboard step cards)
  - Explicitly documented as scoped behavior (not global/all cards) in:
    - `docs/APP1_UI_STYLE_GUIDE.md`
    - `docs/APP1_CODING_RULES.md`

## Update (2026-03-16)
- Family Planning follow-up workspace module implemented and routed:
  - Route: `workspaces/{patientId}/family-planning` (`workspaces-family-planning`)
  - New files:
    - `database/migrations/2026_03_16_120000_create_family_planning_follow_ups_table.php`
    - `app/Models/FamilyPlanningFollowUp.php`
    - `app/Livewire/Workspaces/Modules/FamilyPlanning/ClientFollowUp.php`
    - `resources/views/livewire/workspaces/modules/family-planning/client-follow-up.blade.php`
- Workflow aligned to one-time registration rule:
  - Baseline Family Planning registration stays one-time in `registers/family-planning-register`.
  - Subsequent visits are captured as many `family_planning_follow_ups` records.
  - Follow-up save/update syncs `family_planning_registrations.next_appointment` for Appointments/Reminders aggregation continuity.
- Dashboard wiring updates:
  - Family Planning workspace card now counts `App\Models\FamilyPlanningFollowUp` records.
  - Family Planning registration check in dashboard is now facility-scoped.
  - File: `app/Livewire/Workspaces/WorkspaceDashboard.php`
- Model relationship updates:
  - `Patient` now has `familyPlanningFollowUps()` relation.
  - `FamilyPlanningRegistration` now has `followUps()` relation.
- Validation run:
  - `php -l` passed for all changed PHP files.
  - `php artisan route:list` passed and shows `workspaces-family-planning`.
  - `php artisan test` passed (`2 passed`).

## Update (2026-03-16, Health Insurance + Register Alignment)
- Register route fix:
  - Fixed broken register links that referenced undefined `patient-dashboard` route.
  - Updated to `workspace-dashboard` with `patientId` param in:
    - `resources/views/livewire/registers/general-patients-register.blade.php`
    - `resources/views/livewire/registers/antenatal-register.blade.php`
- NHIS capture alignment across all 3 register entry points:
  - General register already had full NHIS fields.
  - ANC register now includes full NHIS fields and validations:
    - `is_nhis_subscriber`, `nhis_number`, `nhis_provider`, `nhis_expiry_date`, `nhis_plan_type`, `nhis_principal_name`, `nhis_principal_number`.
  - Family Planning register now includes the same full NHIS fields and validations.
  - Updated files:
    - `app/Livewire/Registers/AntenatalRegister.php`
    - `resources/views/livewire/registers/antenatal-register.blade.php`
    - `app/Livewire/Registers/FamilyPlanningRegister.php`
    - `resources/views/livewire/registers/family-planning-register.blade.php`
- Health Insurance workspace module implemented (Section 15):
  - Route: `workspaces/{patientId}/health-insurance` (`workspaces-health-insurance`)
  - New files:
    - `app/Livewire/Workspaces/Modules/HealthInsurance.php`
    - `resources/views/livewire/workspaces/modules/health-insurance/index.blade.php`
  - Features:
    - Activate/deactivate NHIS coverage
    - Update provider, plan type, expiry, and principal fields
    - Patient-scoped insurance audit/history via `Activity` log (`module = health_insurance`)
- Workspace dashboard wiring:
  - Added Health Insurance card and route mapping in:
    - `app/Livewire/Workspaces/WorkspaceDashboard.php`
    - `resources/views/livewire/workspaces/workspace-dashboard.blade.php`
    - `resources/views/livewire/partials/workspace-card.blade.php`
- Validation run:
  - `php -l app/Livewire/Registers/AntenatalRegister.php`
  - `php -l app/Livewire/Registers/FamilyPlanningRegister.php`
  - `php -l app/Livewire/Workspaces/Modules/HealthInsurance.php`
  - `php -l app/Livewire/Workspaces/WorkspaceDashboard.php`
  - `php -l routes/web.php`
  - `php artisan route:list --name=workspaces-health-insurance`
  - `php artisan route:list --name=workspace-dashboard`
  - `php artisan test` (`2 passed`)

## Update (2026-03-16, Visits Module Section 16)
- Visits workspace module implemented and routed:
  - Route: `workspaces/{patientId}/visits` (`workspaces-visits`)
  - New files:
    - `database/migrations/2026_03_16_230000_create_visits_table.php`
    - `database/migrations/2026_03_16_230100_create_visit_events_table.php`
    - `app/Models/Visit.php`
    - `app/Models/VisitEvent.php`
    - `app/Services/Visits/VisitCollationService.php`
    - `app/Livewire/Workspaces/Modules/Visits.php`
    - `resources/views/livewire/workspaces/modules/visits/index.blade.php`
- Collation workflow:
  - One visit session per `patient_id + facility_id + visit_date`.
  - Visit source dates come from union of DIN activations and activity timeline records.
  - Visit events are synced from `activities` (`module/action/description/performed_by/meta`) and deduplicated by `activity_id`.
  - Session summary fields update on each sync (`total_events`, `modules_summary`, open/closed status).
- Console backfill command added:
  - `php artisan visits:backfill`
  - Optional filters: `--facilityId=`, `--patientId=`, `--from=YYYY-MM-DD`, `--to=YYYY-MM-DD`
- Route and documentation wiring:
  - `routes/web.php` imports and serves `Visits` Livewire page.
  - `routes/console.php` registers `visits:backfill`.
  - `docs/APP1_MODULE_STATUS.md` now marks Section 16 as Implemented.
  - `docs/APP1_WORKFLOW_ROADMAP.md` now reflects visit collation as implemented flow.
- Validation run:
  - `php -l app/Models/Visit.php`
  - `php -l app/Models/VisitEvent.php`
  - `php -l app/Services/Visits/VisitCollationService.php`
  - `php -l app/Livewire/Workspaces/Modules/Visits.php`
  - `php -l routes/web.php`
  - `php -l routes/console.php`
  - `php artisan migrate --force`
  - `php artisan route:list --name=workspaces-visits`
  - `php artisan visits:backfill`
  - `php artisan test` (`2 passed`)

## Update (2026-03-16, Visits Auto-Sync)
- Removed manual day-to-day sync dependency from Visits workspace UI:
  - Sync button removed from `resources/views/livewire/workspaces/modules/visits/index.blade.php`.
  - Page now states visits are auto-updated from attendance/activity entries.
- Auto-sync wiring added with model observers:
  - `app/Observers/ActivityObserver.php`
  - `app/Observers/DinActivationObserver.php`
  - Observers registered in `app/Providers/AppServiceProvider.php`.
- Trigger behavior:
  - On `Activity` create/update/delete -> sync only affected patient/facility date(s).
  - On `DinActivation` create/update/delete/restore -> sync only affected patient/facility date(s).
- Collation service hardening:
  - `app/Services/Visits/VisitCollationService.php` now also reads existing visits in scope and deletes stale visit sessions when no activation/activity remains for a date.
- Validation run:
  - `php -l app/Observers/ActivityObserver.php`
  - `php -l app/Observers/DinActivationObserver.php`
  - `php -l app/Providers/AppServiceProvider.php`
  - `php -l app/Services/Visits/VisitCollationService.php`
  - `php artisan route:list --name=workspaces-visits`
  - `php artisan visits:backfill`
  - `php artisan test` (`2 passed`)

## Update (2026-03-16, Bed Management Module - Phase C Start)
- Facility bed management module added (facility-scoped):
  - Route: `/core/bed-management` (`bed-management`)
  - New files:
    - `database/migrations/2026_03_16_231000_create_beds_table.php`
    - `app/Models/Bed.php`
    - `app/Livewire/Core/BedManagement.php`
    - `resources/views/livewire/core/bed-management.blade.php`
- Core features delivered:
  - Bed CRUD per facility (`bed_code`, ward/section, room, type, status, notes, active/inactive).
  - Status operations: available, occupied, maintenance, inactive.
  - Occupancy placeholder fields prepared for next module linkage:
    - `occupied_by_patient_id`, `occupied_since`.
  - Ward/Section is now facility-managed via dedicated section CRUD and selected in bed form (no free-text ward entry in bed form).
  - Bed code entry is now optional in bed form; if blank, system auto-generates unique code (`BED-0001` style) within facility.

## Update (2026-03-16, Facility Sections separated from Bed Management)
- Ward/Section CRUD moved out of Bed Management into a dedicated core module:
  - Route: `/core/facility-sections` (`facility-sections`)
  - New files:
    - `app/Livewire/Core/FacilitySections.php`
    - `resources/views/livewire/core/facility-sections.blade.php`
    - `app/Models/BedSection.php`
    - `database/migrations/2026_03_17_000000_create_bed_sections_table.php`
    - `database/migrations/2026_03_17_000100_add_bed_section_id_to_beds_table.php`
- Bed Management remains standalone and now only handles bed records:
  - Route: `/core/bed-management` (`bed-management`)
  - Bed form uses section selection from Facility Sections.
  - Bed code is optional; auto-generated if blank.
- Sidebar change:
  - Removed nested `Facility Resources` group entry for these items.
  - Added standalone links: `Facility Sections`, `Bed Management`.
- Validation run:
  - `php -l app/Livewire/Core/FacilitySections.php`
  - `php -l app/Livewire/Core/BedManagement.php`
  - `php -l app/Models/BedSection.php`
  - `php -l routes/web.php`
  - `php artisan route:list --name=facility-sections`
  - `php artisan route:list --name=bed-management`
  - `php artisan test` (`2 passed`)
  - Dashboard-style stat cards + DataTable export/search/pagination for bed records.
- Routing/menu wiring:
  - Added route in `routes/web.php`.
  - Added Facility Admin sidebar link in `resources/menu/facilityAdminMenu.json` under Facility Resources.
- Validation run:
  - `php -l app/Livewire/Core/BedManagement.php`
  - `php -l app/Models/Bed.php`
  - `php -l routes/web.php`
  - `php -l database/migrations/2026_03_16_231000_create_beds_table.php`
  - `php artisan route:list --name=bed-management`
  - `php artisan migrate --force`
  - `php artisan test` (`2 passed`)

## Update (2026-03-17, Admitted Patients / Inpatient Module)
- Admitted Patients core module implemented and routed:
  - Route: `/core/admitted-patients` (`admitted-patients`)
  - New files:
    - `app/Models/InpatientAdmission.php`
    - `database/migrations/2026_03_17_001000_create_inpatient_admissions_table.php`
    - `app/Livewire/Core/AdmittedPatients.php`
    - `resources/views/livewire/core/admitted-patients.blade.php`
- Workflow delivered:
  - Admit patient to a selected available bed (facility-scoped).
  - Enforce one active admission per patient per facility.
  - Enforce one active occupant per bed.
  - Discharge or refer-out closes active admission and frees bed occupancy.
  - Activity timeline logging added for admit/discharge/refer events (`module = inpatient`).
- Bed/section integration:
  - Bed occupancy fields are updated on admit and cleared on close.
  - Works with facility sections and bed management foundation delivered earlier.
- Monthly summary alignment:
  - `app/Livewire/Analytics/MonthlyReportDashboard.php` inpatient section now reads from `inpatient_admissions` for admission/discharge totals (instead of delivery proxy).
- Sidebar routing:
  - Added standalone Facility Admin menu link for Admitted Patients in:
    - `resources/menu/facilityAdminMenu.json`
- Validation run:
  - `php -l app/Livewire/Core/AdmittedPatients.php`
  - `php -l app/Livewire/Analytics/MonthlyReportDashboard.php`
  - `php -l routes/web.php`
  - `php artisan migrate --force`
  - `php artisan route:list --name=admitted-patients`
  - `php artisan route:list --name=bed-management`
  - `php artisan test` (`2 passed`)

## Update (2026-03-17, Pharmacy Operations Module)
- New facility core module delivered:
  - Route: `/core/pharmacy-operations` (`pharmacy-operations`)
  - New files:
    - `app/Livewire/Core/PharmacyOperations.php`
    - `resources/views/livewire/core/pharmacy-operations.blade.php`
    - `app/Services/Pharmacy/DrugInventoryService.php`
    - `app/Models/DrugStockBatch.php`
    - `app/Models/DrugStockMovement.php`
    - `database/migrations/2026_03_17_010000_add_reorder_level_to_drug_catalog_items_table.php`
    - `database/migrations/2026_03_17_010100_create_drug_stock_batches_table.php`
    - `database/migrations/2026_03_17_010200_create_drug_stock_movements_table.php`
- Workflow implemented (clear labels, facility-scoped):
  - Stock In (new batch)
  - Stock Adjustment (add/deduct with reason)
  - Inventory Overview (available/expired/reorder level/status)
  - Stock Batches table
  - Stock Movement Log table
- Integration added to patient dispensing:
  - `app/Livewire/Workspaces/Modules/Clinical/Prescriptions.php` now deducts inventory on checkout via FIFO batches.
  - Checkout now blocks with clear error when stock is insufficient.
  - Movement logs are created for each issued line with checkout reference code.
- Route/menu wiring:
  - Added route in `routes/web.php`
  - Added Facility Admin sidebar link in `resources/menu/facilityAdminMenu.json`
- Validation run:
  - `php -l` for all new/changed pharmacy files passed
  - `php artisan migrate --force` (new stock tables + reorder level)
  - `php artisan route:list --name=pharmacy-operations`
  - `php artisan test` (`2 passed`)
- UI hardening note:
  - Pharmacy stat cards switched to inline SVG icons to ensure icons always render.
  - Added project rule: stat cards must carry visible icons; inline SVG is the standard.

## Update (2026-03-17, Workflow Documentation Hardening)
- Added explicit cross-module order bridge workflow mapping in:
  - `docs/APP1_WORKFLOW_ROADMAP.md`
    - new section: `Clinical Order Bridges (Implemented)`
    - includes Doctor->Lab->Lab Result completion chain and Doctor->Prescriptions->Dispensing->Inventory->Invoice chain.
- Added operational usage guide for staff in:
  - `docs/APP1_USER_GUIDE_CLINICAL_ORDER_BRIDGES.md`
    - practical step-by-step usage order and troubleshooting.
- Added workflow documentation enforcement rule in roadmap checklist:
  - every bridge update must document source action, pending table, completion action, and downstream effects.

## Update (2026-03-17, Admitted Patients Mobile Flow)
- Replaced inline discharge/refer close-admission section with modal workflow in:
  - `resources/views/livewire/core/admitted-patients.blade.php`
- Action behavior:
  - Clicking `Discharge` or `Refer Out` now opens a clear modal with selected patient/admission context.
  - Close/hidden modal resets close-form state to avoid stale values.
- Livewire event wiring added:
  - `open-close-admission-modal`
  - `close-close-admission-modal`

## Update (2026-03-17, Pharmacy Ops UX Clarification)
- Clarified `Reorder Level` behavior in UI and docs:
  - Reorder level is a low-stock threshold only; it does not add quantity.
  - Added explicit in-page guidance and status helper text in:
    - `resources/views/livewire/core/pharmacy-operations.blade.php`
- Simplified pharmacy page workflow:
  - `Step 1: Stock In` is now primary and obvious.
  - `Step 2 (Optional): Manual Stock Adjustment` is now opened by page button and handled in a modal (collapse toggle removed).
  - Inventory action button now focuses and scrolls to Stock In form.
- User guide updated:
  - `docs/APP1_USER_GUIDE_CLINICAL_ORDER_BRIDGES.md`

## Update (2026-03-17, Workspace Prescriptions Modal Flow)
- Workspace Prescriptions receipt interaction was converted from inline card rendering to modal behavior in:
  - `resources/views/livewire/workspaces/modules/clinical/prescriptions.blade.php`
  - `app/Livewire/Workspaces/Modules/Clinical/Prescriptions.php`
- Behavior change:
  - `Submit Checkout` and `View Receipt` now open a receipt modal (no page jump/shift).
  - Closing the receipt modal resets receipt state cleanly.
- Livewire event wiring added:
  - `open-drug-receipt-modal`
  - `close-drug-receipt-modal`
- Validation run:
  - `php -l app/Livewire/Workspaces/Modules/Clinical/Prescriptions.php`
  - `php artisan test` (`2 passed`)

## Update (2026-03-17, Laboratory Full Operations Module)
- New facility core module delivered:
  - Route: `/core/laboratory-operations` (`laboratory-operations`)
  - Sidebar link added in `resources/menu/facilityAdminMenu.json`
- New core component/view:
  - `app/Livewire/Core/LaboratoryOperations.php`
  - `resources/views/livewire/core/laboratory-operations.blade.php`
- New laboratory operations tables/migration:
  - `database/migrations/2026_03_17_020000_create_laboratory_operations_tables.php`
  - Creates: `lab_processing_batches`, `lab_samples`, `lab_qc_logs`, `lab_reagent_stocks`, `lab_reagent_movements`, `lab_equipment_logs`
- New models:
  - `app/Models/LabProcessingBatch.php`
  - `app/Models/LabSample.php`
  - `app/Models/LabQcLog.php`
  - `app/Models/LabReagentStock.php`
  - `app/Models/LabReagentMovement.php`
  - `app/Models/LabEquipmentLog.php`
- Workflow implemented (facility scoped):
  - Pending lab order queue -> sample intake prefill
  - Sample tracking lifecycle (received/processing/ready/reported/rejected)
  - Processing batch creation + assignment + completion
  - QC log capture and history
  - Reagent stock-in, adjustment, reorder threshold, movement logs
  - Equipment calibration/maintenance logging with due-date tracking
- UI and table standards applied:
  - Clear section labels and loading states on actions
  - Stat cards with icons
  - DataTables pagination + export controls on all operational tables
- Validation run:
  - `php -l app/Livewire/Core/LaboratoryOperations.php`
  - `php -l app/Models/LabProcessingBatch.php`
  - `php -l app/Models/LabSample.php`
  - `php -l app/Models/LabQcLog.php`
  - `php -l app/Models/LabReagentStock.php`
  - `php -l app/Models/LabReagentMovement.php`
  - `php -l app/Models/LabEquipmentLog.php`
  - `php -l database/migrations/2026_03_17_020000_create_laboratory_operations_tables.php`
  - `php -l routes/web.php`
  - `php artisan migrate --force`
  - `php artisan route:list --name=laboratory-operations`
  - `php artisan view:cache`
  - `php artisan test` (`2 passed`)
