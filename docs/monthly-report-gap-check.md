# Monthly Report Gap Check

Purpose: Map monthly report fields to current data sources and call out missing sources or logic needed later.

## Sources In Use
- `DailyAttendance` for attendance counts (age/sex buckets)
- `Delivery` for maternal, newborn, inpatient proxy, mortality
- `PostnatalRecord` for postnatal + FP counseling
- `TetanusVaccination` for TT/TD in immunization
- `Antenatal` (older model) for ANC attendance in the report logic

## Coverage Summary
- Attendance: Partial, uses `DailyAttendance`
- Maternal health: Partial, uses `Delivery` + `Antenatal`
- Newborn health: Partial, uses `Delivery`
- Immunization: Partial, TT only
- Child health: Missing (placeholders)
- Other services: Partial, postnatal FP only
- Mortality: Partial, inferred from `Delivery` complications

## Gaps By Section
1. **Health Facility Attendance**
   - Source: `DailyAttendance`
   - Gap: None for general/outpatient counts

2. **Inpatient Care**
   - Source: `Delivery` used as proxy for admissions/discharges
   - Gap: Real inpatient admissions/discharges table needed

3. **Mortality**
   - Source: `Delivery.dead`, `Delivery.baby_dead`, `Delivery.complications`
   - Gap: Proper mortality coding table for causes (maternal/neonatal/under-5)

4. **Maternal Health (ANC)**
   - Source: `Antenatal` (old model)
   - Gap: Switch to `AntenatalRegistration` or new ANC module data
   - Gap: First-visit gestational age, 4th/8th visit, IPT doses not tracked

5. **Newborn Health**
   - Source: `Delivery` (weights, stillbirths, newborn care fields)
   - Gap: Resuscitation, detailed newborn outcomes not captured

6. **Immunization**
   - Source: `TetanusVaccination`
   - Gap: Full child immunization table (BCG, OPV, Penta, PCV, Measles)

7. **Child Health & Nutrition**
   - Source: None (placeholders)
   - Gap: Child health module or clinical notes mapping

8. **Other Health Services**
   - Source: `PostnatalRecord` for FP counseling/acceptance
   - Gap: Malaria/TB/GBV/testing requires clinical notes or disease registry

## Action Items (Later)
- Map ANC report logic to `AntenatalRegistration` and ANC module tables
- Add immunization, child health, and disease registry modules
- Add inpatient admission/discharge tracking

