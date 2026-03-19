# APP1 Lean Execution Plan

Updated: 2026-03-17
Owner: App1 Delivery Team
Mode: Lean (no over-engineering)

## Current Progress Snapshot

1. NHMIS matrix registry is implemented (`NhmisFieldRegistry`) and synced to `docs/nhmis-field-matrix.json` (187 rows).
2. Reports Hub monthly generation now uses:
   - key-based structured aggregations (`summary_keys`)
   - per-row resolver rules for non-key rows (`NhmisFieldValueResolver`) instead of hard-coded zero defaults.
3. Matrix hardening latest batch is completed:
   - `structured`: 187
   - `fallback`: 0
   - `missing`: 0
4. Baseline resolver + registry unit tests are active:
   - `tests/Unit/NhmisFieldRegistryTest.php`
   - `tests/Unit/NhmisFieldValueResolverTest.php`
5. Product decision recorded: advanced Pharmacy supplier/procurement + LMIS full operations are deferred to Version 2 (V1 scope frozen).
6. Access control hardening is implemented:
   - `module.enabled` middleware alias added in `bootstrap/app.php`
   - key workspace/core module routes now enforce facility module toggles
   - module enable/disable ownership is centralized at `/central/facility-module-management`
   - facility-side module table is read-only (status visibility only)
   - feature tests added in `tests/Feature/ModuleEnabledMiddlewareTest.php`
7. Central admin surface hardening is implemented:
   - central dashboard now uses live data (facilities/users/modules/reminder dispatch)
   - central sidebar now contains only valid links
   - legacy `/central-admin/*` links are redirect-compatible
   - feature tests added in `tests/Feature/CentralLegacyRoutesTest.php`
8. Lean RBAC hardening is implemented:
   - `role_permissions` table + central management page (`/central/roles-permissions`)
   - `permission.check` middleware alias + route-level enforcement across central/core/workspace/register/analytics/avo
   - default policy in `RolePermissionService` with central override persistence
   - feature tests added in `tests/Feature/RolePermissionMiddlewareTest.php`

## Proposal Scope

1. Keep architecture simple and incremental.
2. Prioritize monthly-report correctness, module safety, and maintainability.
3. Reuse existing tables/services where possible.
4. Avoid large platform rewrites.

## Section 1: Goals

1. Complete and trust the 187-field NHMIS monthly mapping.
2. Remove remaining heuristic aggregations where structured fields exist.
3. Add regression tests so monthly figures stop breaking.
4. Enforce module access consistently with minimal new code.
5. Improve central visibility for facility oversight without enterprise-heavy complexity.

## Section 2: Current Gaps (From Audit)

1. Monthly aggregation still mixes structured logic with heuristics in `MonthlyReportDashboard.php`.
2. Monthly-report tests are missing (only example tests exist) in `tests`.
3. Facility-level module access governance needs central operational dashboards and audit depth improvements.
4. Role checks are scattered and inconsistent in `UserRoleMiddleware.php` + component-level `abort(403)` checks.
5. Central admin dashboard is mostly placeholder and not operational in `central-admin-dashboard.blade.php`.

## Section 3: NHMIS Completion Plan (Core Priority)

1. Create a canonical 187-field mapping matrix file in docs with:
   - field number
   - field label
   - source table/field
   - transform/count rule
   - status: `structured` / `fallback` / `missing`
2. Build one mapping registry class/file used by monthly aggregation.
3. Replace heuristic pulls only where structured sources already exist.
4. Keep explicit fallback markers for fields that are truly unavailable now.
5. Update `monthly-report-gap-check.md` with exact completion status.

## Section 4: Monthly Accuracy Test Plan

1. Add feature tests for monthly summary generation covering:
   - single facility month
   - multi-facility scoped aggregation
   - known sample dataset expected outputs
2. Add regression tests for previously unstable areas:
   - child health merged counters
   - doctor/lab "other services" mappings
   - inpatient/mortality key totals
3. Add one "no silent heuristic override" test path.

## Section 5: Access Control Plan (Lean)

1. Add a lightweight `module.enabled` middleware using existing `facility_module_accesses`.
2. Apply middleware to key workspace/core routes.
3. Keep current `role.redirect` middleware; do not replace everything now.
4. Standardize component authorization through a small shared helper/trait.
5. Add a small test set:
   - disabled module route returns `403`
   - enabled module route works
   - tenant/facility scope still respected

## Section 6: Analytics and Legacy Cleanup (Minimal)

1. Keep existing analytics modules; do not rewrite all dashboards now.
2. Fix reliability issues in legacy report queries (especially mixed patient/user DIN assumptions).
3. Remove dead/duplicate report cards/routes that do not map to real data.
4. Segment large report methods into smaller private methods only where needed for clarity.

## Section 7: AI Plan (Simple, Advisory-Only)

1. No heavy AI platform rollout now.
2. Keep AI as optional assistant outputs only (no auto-actions).
3. Add lightweight `config/ai.php` with provider/model/guard defaults.
4. Add simple AI feature flag by facility/module toggle.
5. Log AI suggestions in normal audit/activity records for traceability.
6. External models remain optional and deferred.

## Section 8: Central Oversight Improvements

1. Replace placeholder central dashboard blocks with real metrics:
   - total facilities
   - active users by role
   - module enablement distribution
   - reminders dispatch summary
   - monthly report completion status by facility
2. Clean central menu links to only valid routes.
3. Add compact audit panel (recent critical admin actions).

## Section 9: Documentation Deliverables

1. Update `APP1_CODING_RULES.md` with:
   - module middleware hard rule
   - monthly mapping update rule
   - regression test rule for aggregation changes
2. Update `APP1_WORKFLOW_ROADMAP.md` with completed and pending items.
3. Update `APP1_MODULE_STATUS.md` after each phase.

## Section 10: Implementation Order

1. NHMIS matrix + mapping registry
2. Structured replacements in monthly aggregation
3. Monthly regression tests
4. `module.enabled` middleware + route wiring
5. Central dashboard/menu cleanup
6. Docs updates and handoff
