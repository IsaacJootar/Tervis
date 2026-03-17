# APP1 Report Data Point Scan (All Modules)

Updated: 2026-03-17
Scope scanned: `app/Livewire/{Workspaces,Core,Analytics,Registers,Central}` + `app/Models`

## 1) Scan Output Artifacts

Generated machine-readable scan files:

1. `docs/report-module-field-scan.csv`
2. `docs/report-model-fillable-scan.csv`
3. `docs/report-module-datapoints.json`
4. `docs/report-module-datapoints-summary.csv`
5. `docs/report-model-datefields.csv`

## 2) Coverage Summary

1. Module files scanned: `54`
2. Models scanned (fillable schema extracted): `61`
3. Highest report-field-density module files:
   - `app/Livewire/Analytics/MonthlyReportDashboard.php` (`43`)
   - `app/Livewire/Core/LaboratoryOperations.php` (`21`)
   - `app/Livewire/Core/AdmittedPatients.php` (`17`)
   - `app/Livewire/Workspaces/Modules/Appointments.php` (`15`)
   - `app/Livewire/Core/FacilityReports.php` (`13`)

## 3) Canonical Data Sources For Reports

Primary patient/facility-scoped report sources now confirmed:

1. Attendance and encounter flow:
   - `DailyAttendance`
   - `Registrations/DinActivation`
   - `Visit`, `VisitEvent`
2. Maternal services:
   - `Registrations/AntenatalRegistration`
   - `AntenatalFollowUpAssessment`
   - `TetanusVaccination`
   - `Delivery`
   - `PostnatalRecord`
3. Child services:
   - `ImmunizationRecord`
   - `ChildHealthActivityRecord` (Vaccination Schedule tabs)
   - `NutritionRecord`
   - `LinkedChild`
4. Clinical and diagnostics:
   - `DoctorAssessment`
   - `LabTestOrder`
   - `LabTest`
   - `Referral`
5. Pharmacy and dispensing:
   - `Prescription`
   - `DrugDispenseLine`
   - `DrugCatalogItem`
   - `DrugStockBatch`, `DrugStockMovement`
6. Billing and collections:
   - `Invoice`, `InvoiceLine`
   - `PatientPayment`, `PaymentAllocation`
7. Inpatient:
   - `InpatientAdmission`
   - `Bed`, `BedSection`
8. Follow-up/reminders:
   - `Reminder`, `ReminderDispatchLog`
   - Next-appointment fields from ANC/TT/Doctor/FP modules

## 4) Date Anchors Confirmed

Major report-driving date anchors found in active models:

1. `visit_date`, `month_year`
2. `registration_date`
3. `dose_date`, `next_appointment_date`, `next_return_date`
4. `dodel`
5. `admitted_at`, `discharged_at`
6. `prescribed_date`, `dispensed_date`
7. `invoice_date`, `payment_date`
8. `referral_date`, `completed_date`
9. `reminder_date`, `queued_at`, `sent_at`

## 5) Monthly Mapper Coverage vs Available Sources

Current `MonthlyReportDashboard` directly uses:

1. `DailyAttendance`
2. `InpatientAdmission`
3. `Delivery`
4. `Antenatal` (legacy model reference)
5. `TetanusVaccination`
6. `ImmunizationRecord`
7. `ChildHealthActivityRecord`
8. `NutritionRecord`
9. `PostnatalRecord`
10. `DoctorAssessment`
11. `LabTest`

High-value sources available in modules but not yet wired into monthly aggregation:

1. `AntenatalFollowUpAssessment` (structured ANC follow-up)
2. `FamilyPlanningFollowUp`
3. `Registrations/FamilyPlanningRegistration`
4. `LabTestOrder` (ordered vs completed workload indicators)
5. `Prescription` and `DrugDispenseLine` (prescribed vs dispensed indicators)
6. `Invoice` and `PatientPayment` (service billing/collection indicators)
7. `Referral` (referral-out, completion, turnaround metrics)
8. `Visit` and `VisitEvent` (cross-module activity intensity)

## 6) Hard-Zero / Placeholder Indicators Detected

In `app/Livewire/Analytics/MonthlyReportDashboard.php`, these indicators are currently defaulted/placeholder and should move to structured source rules:

1. Mortality age buckets and some mortality-cause buckets
2. ANC milestones:
   - `anc_first_before20wks`
   - `anc_first_after20wks`
   - `4th_anc_visit`
   - `8th_anc_visit`
   - `ipt1`, `ipt2`, `ipt3`
3. Newborn:
   - `resuscitated`
4. Child health:
   - `deworming`
   - `diarrhoea_cases`
   - `pneumonia_cases`
5. Other services:
   - `modern_contraception`
   - `diabetes_cases`
   - `hypertension_cases`

## 7) Raw Template Alignment Readiness

Template batch in `docs/APP1_REPORT_TEMPLATES_INTAKE.md` can now map against confirmed sources:

1. Daily ANC register: `AntenatalRegistration` + `AntenatalFollowUpAssessment` + `TetanusVaccination` + `Delivery`
2. Daily FP register: `FamilyPlanningRegistration` + `FamilyPlanningFollowUp`
3. Child immunization register: `ImmunizationRecord` + `ChildHealthActivityRecord`
4. Nutrition/Growth register: `NutritionRecord` + `LinkedChild`
5. Immunization monthly summary/tally: `ImmunizationRecord` + `ChildHealthActivityRecord` + `TetanusVaccination`
6. NHMIS monthly report: `MonthlyReportDashboard` source set + pending structured upgrades listed above

## 8) Immediate Next Build Step

Use this scan as the source inventory for:

1. full 187-field NHMIS matrix completion (`source_table`, `source_field`, `rule`, `status`)
2. replacing placeholder zero/heuristic fields with structured pulls where source exists
3. writing feature/regression tests around monthly totals
