# APP1 Report Templates Intake Register

Updated: 2026-03-17  
Source Batch: `C:\Users\HomePC\Desktop\CUREVA`

## 1) Purpose
Track raw report templates, reporting interval, target generation point, and implementation status so reporting work stays structured and predictable.

## 2) Received Templates (Batch 1)
| # | Template File | Source Path | Interval | Target Section | Output Level | Status |
|---|---|---|---|---|---|---|
| 1 | CHILD IMMUNIZATION REGISTER.htm | `C:\Users\HomePC\Desktop\CUREVA\CHILD IMMUNIZATION REGISTER.htm` | Daily Register | Child Health / Immunization | Facility register export | Field-Validated |
| 2 | Daily anc register.htm | `C:\Users\HomePC\Desktop\CUREVA\Daily anc register.htm` | Daily Register | ANC | Facility register export | Field-Validated |
| 3 | DAILY FAMILY PLANNING REGISTER.htm | `C:\Users\HomePC\Desktop\CUREVA\DAILY FAMILY PLANNING REGISTER.htm` | Daily Register | Family Planning | Facility register export | Field-Validated |
| 4 | IMMUNIZATION SUMMARY REGISTER.htm | `C:\Users\HomePC\Desktop\CUREVA\IMMUNIZATION SUMMARY REGISTER.htm` | Monthly Summary | Immunization | Facility/LGA summary export | Field-Validated |
| 5 | IMMUNIZATION SUMMARY tally.htm | `C:\Users\HomePC\Desktop\CUREVA\IMMUNIZATION SUMMARY tally.htm` | Monthly Summary | Immunization | LGA tally summary export | Field-Validated |
| 6 | MONTHLY NHMIS REPORT.html | `C:\Users\HomePC\Desktop\CUREVA\MONTHLY NHMIS REPORT.html` | Monthly Compliance Report | NHMIS Monthly | Facility monthly compliance export | Intake Complete (Primary) |
| 7 | NUTRITIONGROWTH MONITORING REGISTER.htm | `C:\Users\HomePC\Desktop\CUREVA\NUTRITIONGROWTH MONITORING REGISTER.htm` | Daily Register | Child Health / Nutrition | Facility register export | Field-Validated |

## 3) Implementation Mapping (Lean)
1. `MONTHLY NHMIS REPORT.html`
   - Use as primary monthly compliance template for 187-field mapping.
   - Reports Hub now reads template field rows and generates output by template field number/name.
2. Daily Registers (`ANC`, `Family Planning`, `Immunization`, `Nutrition`)
   - Generate as facility-scoped daily register outputs with template-structured columns.
   - Pull from existing module records for selected date range/day.
3. Immunization Monthly Summaries (`REGISTER`, `tally`)
   - Reports Hub now includes dedicated template-style summary register and tally reports.
   - Support facility view and aggregated scope where permitted.

## 4) Data Source Targets (Current Direction)
1. Child Immunization Register:
   - `immunization_records`, `child_health_activity_records`, patient demographic linkage.
2. Daily ANC Register:
   - ANC booking/follow-up + related screening/visit fields from ANC module records.
3. Daily Family Planning Register:
   - registration/follow-up records from family planning tables.
4. Nutrition/Growth Register:
   - `nutrition_records` + linked patient fields.
5. Immunization Monthly Summary + Tally:
   - immunization module aggregates (dose totals by required bands).
6. NHMIS Monthly:
   - `MonthlyReportDashboard` aggregation registry (187-field matrix).

## 5) Rollout Order
1. Finalize NHMIS 187-field mapping + tests (already in active execution plan).
2. Add monthly immunization summary/tally exports.
3. Add daily register exports (ANC, FP, Immunization, Nutrition).
4. Add final QA pass for scope correctness (facility/LGA/state).

## 6) Notes
1. These templates are interval-based operational outputs and should be generated from module data, not manually entered as standalone forms.
2. All generated reports must respect scope rules:
   - patient identity can be recognized across scope
   - activity/report data remains facility-scoped unless explicitly aggregated.
