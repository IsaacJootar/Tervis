# Monthly Report Gap Check

Purpose: Map monthly report fields to current data sources and call out missing sources or logic needed later.

## Current Status (2026-03-11)

### Sources In Use
- `DailyAttendance` for attendance counts (age/sex buckets)
- `Delivery` for maternal, newborn, inpatient proxy, mortality
- `PostnatalRecord` for postnatal + FP counseling
- `TetanusVaccination` for TT/TD in immunization
- `ImmunizationRecord` for routine child vaccine dates
- `NutritionRecord` for core child nutrition indicators
- `ChildHealthActivityRecord` (Vaccination Schedule) for additional child-health/vaccine activity data
- `DoctorAssessment` for clinical diagnosis/findings context in monthly other-services aggregation
- `LabTest` for laboratory-derived monthly testing indicators
- `Antenatal` (older model) for ANC attendance in the report logic

### Coverage Summary
- Attendance: Partial, uses `DailyAttendance`
- Maternal health: Partial, uses `Delivery` + `Antenatal`
- Newborn health: Partial, uses `Delivery`
- Immunization: Mapped (TT + child vaccines), merged from `TetanusVaccination`, `ImmunizationRecord`, and `ChildHealthActivityRecord` with deduplication by child + dose date
- Child health: Partially mapped via `NutritionRecord` + `ChildHealthActivityRecord`
- Other services: Partially mapped from `PostnatalRecord`, `DoctorAssessment`, and `LabTest`
- Prescriptions/Dispensing: Not mapped yet in monthly aggregation (`Prescription` + `DrugDispenseLine` currently excluded)
- Mortality: Partial, inferred from `Delivery` complications

## Monthly Mapping Implemented

### Immunization
- `td_pregnant`, `tt1`-`tt5` from `TetanusVaccination`
- Child vaccine indicators from merged records (`ImmunizationRecord` + `ChildHealthActivityRecord`):
  - `bcg`, `opv0`, `opv1`, `opv2`, `opv3`
  - `penta1`, `penta2`, `penta3`
  - `pcv1`, `pcv2`, `pcv3`
  - `ipv1`, `ipv2`
  - `mcv1`, `mcv2`
  - `yf`, `hepb0`, `rota1`, `rota2`, `rota3`, `mena`, `vita1`, `vita2`, `hpv`

### Child Health & Nutrition
- `exclusive_breastfeeding` from union of:
  - `NutritionRecord` (`age_group=0-5 months` + `infant_feeding=Exclusive BF`)
  - `ChildHealthActivityRecord` breastfeeding tab (`E` entries)
- `vitamin_a` from unique VitA dose dates across `ImmunizationRecord` + `ChildHealthActivityRecord`
- `muac_screened`, `sam_new_cases`, `mam_new_cases`, `sam_admissions`, `mnp_given`, `not_growing_well` from `NutritionRecord`
- Supplemental counters now available in report payload from `ChildHealthActivityRecord`:
  - `child_health.weight_monitoring_entries`
  - `child_health.aefi_reported_cases`

### Other Services (Doctor + Laboratory)
- `malaria_tested` from `LabTest.report_values.mp` where result is `Positive` or `Negative`
- `malaria_positive` from `LabTest.report_values.mp = Positive`
- `malaria_cases` from diagnosis mentions in `DoctorAssessment.final_diagnosis` + `LabTest.clinician_diagnosis`
- `tb_screening` from `LabTest.mcs_results.tb` where result is `Positive` or `Negative`
- `hepb_tested` from `LabTest.mcs_results.hbsag` where result is `Positive` or `Negative`
- `hepc_tested` from `LabTest.mcs_results.hcv` where result is `Positive` or `Negative`
- `gbv_cases` from keyword match (`gbv`, `gender based violence`) in doctor assessment narrative fields

## Remaining Gaps
1. **Maternal Health (ANC)**
   - Still using older `Antenatal` model in report logic.
   - Need migration to ANC module canonical tables.

2. **Inpatient Care**
   - `Delivery` is still a proxy for admissions/discharges.
   - Need dedicated inpatient admission/discharge records.

3. **Pharmacy / Dispensing Monthly Mapping**
   - No monthly-report mapping yet for `Prescription` / `DrugDispenseLine` outputs.
   - Need structured indicators for prescribed vs dispensed vs pending.

4. **Mortality Cause Coding**
   - Need structured maternal/neonatal/under-5 cause coding tables.

5. **Other Health Services (Quality Refinement)**
   - Current mapping uses keyword/heuristic extraction from diagnosis and lab JSON payloads.
   - Next step: map from structured disease/diagnostic domain tables when available.

6. **Child Health NHMIS Row Expansion**
   - `weight_monitoring_entries` and `aefi_reported_cases` are aggregated but not yet mapped to dedicated NHMIS row keys in the current template table.

## Action Items (Later)
- Map ANC report logic to ANC module canonical tables
- Add dedicated inpatient admission/discharge tracking
- Map prescriptions/dispensing indicators into monthly summary rows
- Add structured mortality cause coding
- Expand NHMIS row/key mapping for additional child-health indicators where applicable

## Documentation Baseline (Vision Alignment)
- `docs/APP1_MODULE_STATUS.md`
- `docs/APP1_CODING_RULES.md`
- `docs/APP1_WORKFLOW_ROADMAP.md`
