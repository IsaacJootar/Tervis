# APP1 Report Template Field Validation

Updated: 2026-03-17

## Purpose
Deep field-by-field validation pass for all non-monthly operational report templates against `FacilityReports` output columns.

## Validation Scope
- Template source folder: `C:\Users\HomePC\Desktop\CUREVA`
- Engine validated: `app/Livewire/Core/FacilityReports.php`
- Reports covered:
  - `child_immunization_register`
  - `daily_anc_register`
  - `daily_family_planning_register`
  - `immunization_summary_register`
  - `immunization_summary_tally`
  - `nutrition_growth_register`

## Results Summary
1. Child Immunization Register:
   - Status: aligned
   - Notes: all core vaccine/date columns and comments match template register layout.
2. Daily ANC Register:
   - Status: aligned
   - Fix applied: added `S/N` column to match template table structure.
3. Daily Family Planning Register:
   - Status: aligned
   - Fix applied: added `S/N` column to match template table structure.
4. Immunization Summary Register:
   - Status: aligned
   - Notes: age-band subcolumns are expanded explicitly in output for easier filtering/export.
5. Immunization Summary Tally:
   - Status: aligned
   - Notes: tally dose bands and TD pregnant split columns are expanded explicitly in output.
6. Nutrition/Growth Monitoring Register:
   - Status: aligned
   - Fixes applied:
     - added `S/N` column
     - added numeric `Age (Months)` column
   - Notes: multi-level template groups are flattened to explicit output columns for exportability.

## Runtime Verification (Facility Scope)
Executed using `scripts/verify_reports.php` against active facility scope in app context.

- `child_immunization_register`: PASS (`29` columns)
- `daily_anc_register`: PASS (`52` columns)
- `daily_family_planning_register`: PASS (`56` columns)
- `immunization_summary_register`: PASS (`36` columns)
- `immunization_summary_tally`: PASS (`42` columns)
- `nutrition_growth_register`: PASS (`48` columns)

## Applied Code Changes
- `reportDailyAncRegister()`:
  - Added sequential `sn` in row payload.
  - Added `S/N` output column.
- `reportDailyFamilyPlanningRegister()`:
  - Added sequential `sn` in row payload.
  - Added `S/N` output column.
- `reportNutritionGrowthRegister()`:
  - Added sequential `sn` in row payload.
  - Added computed `age_months` from DOB and visit date.
  - Added `S/N` and `Age (Months)` output columns.

## Validation Rule (Going Forward)
- Any new template ingestion must pass this check before release:
  1. template headers parsed
  2. report output keys mapped 1:1 (or expanded where template uses grouped headers)
  3. missing fields patched in code
  4. doc updated in this file and `APP1_REPORT_TEMPLATES_INTAKE.md`
