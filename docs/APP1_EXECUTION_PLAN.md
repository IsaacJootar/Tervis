# APP1 Lean Execution Plan

Updated: 2026-03-17
Owner: App1 Delivery Team
Mode: Lean (no over-engineering)

## 1) Plan Objective
Deliver a reliable, multi-tenant monthly reporting foundation (NHMIS-focused), enforce simple but consistent access control, and keep AI advisory-only while preserving current velocity.

## 2) Delivery Principles
1. Prefer simple, clear, maintainable changes over complex architecture.
2. Reuse existing tables/services before creating new ones.
3. Focus first on data correctness and regression safety.
4. Keep workflow and UI behavior consistent with existing app1 patterns.
5. Document every aggregation change that affects monthly reporting.

## 3) Current Gaps to Close
1. Monthly NHMIS aggregation remains partially heuristic in `MonthlyReportDashboard`.
2. 187-field mapping is not yet fully represented as a single structured matrix.
3. Monthly summary regression tests are missing.
4. Facility module toggles are not consistently enforced at route/middleware level.
5. Core/legacy reporting has inconsistent query patterns in a few places.

## 4) Execution Scope (Lean)
### A. NHMIS Mapping Completion
1. Build canonical 187-field mapping matrix:
   - field number
   - label
   - source table/field
   - transform/aggregation rule
   - status (`structured`, `fallback`, `missing`)
2. Implement a single mapping registry used by monthly aggregation logic.
3. Update `docs/monthly-report-gap-check.md` after each mapping batch.

### B. Structured Source Replacement
1. Replace heuristic pulls where structured fields already exist.
2. Keep explicit fallback only for genuinely unavailable source fields.
3. Mark unresolved fields clearly (no silent assumptions).

### C. Monthly Accuracy Tests
1. Add focused feature/regression tests for:
   - single-facility monthly generation
   - multi-facility scoped aggregation
   - key totals and category consistency
2. Add regression checks for child-health, lab/doctor "other services", and inpatient totals.

### D. Lightweight Access Enforcement
1. Add `module.enabled` middleware using existing `facility_module_accesses`.
2. Apply middleware to key module routes.
3. Keep current role middleware; avoid full RBAC rewrite now.
4. Add basic access tests (`enabled` works, `disabled` blocks).

### E. Central Oversight (Minimal Upgrade)
1. Replace placeholder central dashboard blocks with real, lightweight metrics:
   - facilities summary
   - user/role counts
   - module enablement summary
   - reminders dispatch summary
   - monthly reporting readiness indicator
2. Clean dead central menu links.

### F. AI (Advisory-Only, Minimal)
1. Keep AI suggestions non-automatic (no auto-clinical actions).
2. Add a lightweight AI config and feature flag path.
3. Record AI outputs in normal audit/activity flow.
4. Defer heavy AI runtime platform work.

## 5) Out of Scope (For Now)
1. Full enterprise-style RBAC migration.
2. Major platform-domain rewrite.
3. Full analytics module rewrite.
4. External-model mandatory integration.

## 6) Execution Order
1. 187-field NHMIS matrix + mapping registry.
2. Structured replacement in monthly aggregation.
3. Monthly feature/regression tests.
4. `module.enabled` middleware and route wiring.
5. Central dashboard/menu cleanup.
6. Docs refresh (`APP1_MODULE_STATUS`, `APP1_WORKFLOW_ROADMAP`, `monthly-report-gap-check`, `handoff`).

## 7) Maternal Report Templates Intake Plan
You should share the maternal report templates now (immediately), before we lock the final 187-field matrix.

Why now:
1. It prevents rework after mapping is completed.
2. We can align report generation needs early.
3. We can tag which templates are:
   - already covered
   - partially covered
   - not yet modeled

How we will use your templates:
1. Create a “Template Intake Register” table in docs with:
   - template name
   - module/section
   - required fields
   - source table
   - output route/page
   - status
2. Map each template to existing or planned report endpoints.
3. Prioritize maternal templates that feed monthly compliance first.

## 8) Approval Gate
Execution starts once this plan is approved and maternal templates are shared for intake mapping.
