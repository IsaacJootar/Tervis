# Monthly Report Gap Check

Purpose: Map monthly report fields to current data sources and call out missing sources or logic needed later.

## Current Status (2026-03-03)

### Sources In Use
- `DailyAttendance` for attendance counts (age/sex buckets)
- `Delivery` for maternal, newborn, inpatient proxy, mortality
- `PostnatalRecord` for postnatal + FP counseling
- `TetanusVaccination` for TT/TD in immunization
- `ImmunizationRecord` for routine child vaccine dates
- `NutritionRecord` for core child nutrition indicators
- `ChildHealthActivityRecord` (Vaccination Schedule) for additional child-health/vaccine activity data
- `Antenatal` (older model) for ANC attendance in the report logic

### Coverage Summary
- Attendance: Partial, uses `DailyAttendance`
- Maternal health: Partial, uses `Delivery` + `Antenatal`
- Newborn health: Partial, uses `Delivery`
- Immunization: Mapped (TT + child vaccines), merged from `TetanusVaccination`, `ImmunizationRecord`, and `ChildHealthActivityRecord` with deduplication by child + dose date
- Child health: Partially mapped via `NutritionRecord` + `ChildHealthActivityRecord`
- Other services: Partial, postnatal FP only
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

## Remaining Gaps
1. **Maternal Health (ANC)**
   - Still using older `Antenatal` model in report logic.
   - Need migration to ANC module canonical tables.

2. **Inpatient Care**
   - `Delivery` is still a proxy for admissions/discharges.
   - Need dedicated inpatient admission/discharge records.

3. **Mortality Cause Coding**
   - Need structured maternal/neonatal/under-5 cause coding tables.

4. **Other Health Services**
   - Malaria/TB/GBV/testing still placeholder zeros pending source modules.

5. **Child Health NHMIS Row Expansion**
   - `weight_monitoring_entries` and `aefi_reported_cases` are aggregated but not yet mapped to dedicated NHMIS row keys in the current template table.

## Action Items (Later)
- Map ANC report logic to ANC module canonical tables
- Add dedicated inpatient admission/discharge tracking
- Add structured mortality cause coding
- Expand NHMIS row/key mapping for additional child-health indicators where applicable
