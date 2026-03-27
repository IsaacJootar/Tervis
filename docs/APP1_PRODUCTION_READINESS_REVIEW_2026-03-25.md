# APP1 / Cureva Production Readiness Review

Date: 2026-03-25
System: Cureva (`app1`)
Framework baseline observed in code: Laravel 12 + Livewire 3
Review scope: application code, routes, config, tests, and project Markdown documentation
Implementation status for this document: planning only, no code changes applied

## 1. Purpose

This document captures the current production readiness findings for Cureva and defines the complete fix plan required to:

- remove release blockers before go-live,
- reduce operational risk for next-week production launch,
- improve scalability and performance for current and near-term growth,
- clean up legacy and inconsistent platform behavior,
- create a safe implementation sequence for approval before code changes begin.

## 2. Executive Summary

Cureva is close to production readiness, but it is not fully ready for a safe go-live on 2026-03-25 without additional hardening.

The strongest parts of the system are:

- broad functional coverage across clinical, admin, reports, reminders, and workflow modules,
- meaningful automated test coverage across permissions, modules, reports, reminders, and workspace chains,
- clear multi-role architecture with middleware-based access control,
- recent hardening work already present in reports, reminders, and documentation.

The main reasons the system is not yet fully go-live ready are:

- the automated test suite is not fully green,
- a legacy public registration path still exists and should be removed,
- some report flows store heavy payloads in database-backed sessions,
- timezone and scheduler behavior are not yet aligned for a Nigeria production rollout,
- reminder dispatch is still designed in a way that will become expensive under growth,
- navbar/global layout context currently performs extra database work on nearly every authenticated request,
- production-facing documentation still contains stale or risky content.

## 3. Validation Snapshot

The following checks were run during this review:

- `php artisan route:list` succeeded.
- `php artisan view:cache` succeeded.
- `php artisan config:cache` succeeded.
- `php artisan route:cache` succeeded.
- `php artisan migrate:status` succeeded and showed no pending migrations.
- `php artisan test` did not fully pass.

Observed automated test status:

- 63 tests passed.
- 1 feature test failed.
- failing test: `Tests\\Feature\\MpdsrSurveillanceDashboardTest`

Observed build status:

- frontend production build could not be conclusively verified in this sandbox because `npm.cmd run build` failed with an `esbuild` child-process `EPERM` sandbox restriction.
- this does not prove the build is broken in a real environment, but it means build success still needs to be confirmed in your normal deployment or CI environment.

## 4. What Is Already Good

### 4.1 Architecture

- The system has a clear role-oriented structure.
- Access control is layered through route middleware, permission checks, and facility module gating.
- The workspace-driven patient flow is coherent and production-oriented.
- The app already separates several business concerns into services such as reports, reminders, visits, AI helpers, and security.

### 4.2 Test Coverage

- There is good real-world coverage for permissions, reminders, reports, workflow aggregation, module enablement, and facility operations.
- Reports and NHMIS logic already have regression tests.
- Reminder provider integration and webhook routing are already tested.

### 4.3 Operational Maturity

- Route, config, and Blade caches compile successfully.
- Migration history is organized and applied.
- There is already a production-hardening document and a substantial project handoff log.

### 4.4 Product and Workflow Maturity

- The application covers a large amount of functional scope for a health platform.
- Many modules already reflect real operational workflows rather than demo-only CRUD.
- Reports, reminders, visits, patient workspaces, and facility dashboards show clear product direction.

## 5. Findings and Risk Rating

## 5.1 Release Blocker: Test Suite Is Not Fully Green

Severity: Critical
Status: Must fix before production go-live

Finding:

- `php artisan test` currently fails on `MpdsrSurveillanceDashboardTest`.
- The current hardening document states that passing tests are an entry criterion for production.

Why this matters:

- A non-green suite removes confidence from release validation.
- The failing area is a reporting and surveillance workflow, which is business-sensitive.

Likely root cause:

- The test uses `DatabaseTransactions` and reuses the first available facility row in the database.
- That makes the test vulnerable to pre-existing data and environmental drift.
- The failure pattern indicates state leakage or non-deterministic dataset assumptions rather than a fully isolated test.

Fix intent:

- make the MPDSR test deterministic,
- isolate its dataset completely,
- confirm whether the production logic is wrong or the test setup is wrong,
- keep the suite green in any environment.

## 5.2 Release Blocker: Legacy Public Registration Path Still Exists

Severity: Critical
Status: Must remove before production go-live

Product decision confirmed for this fix plan:

- the register page is not needed,
- it is a legacy page,
- it should be removed.

Finding:

- legacy auth registration routes and UI still exist,
- the registration page creates users with fields that do not match the actual user model and table requirements,
- it redirects to a `dashboard` route that is not part of the current route list.

Why this matters:

- it exposes a risky public account creation path in a controlled healthcare system,
- it creates confusion and dead behavior in auth flows,
- it may produce invalid or broken user creation attempts if exposed.

Fix intent:

- fully disable registration at the framework level,
- remove the legacy registration page and related UI links,
- keep only the auth paths that are truly required for production.

## 5.3 Scalability Risk: Heavy Report Print Payloads Stored In Session

Severity: High
Status: Should be fixed before or immediately after go-live

Finding:

- the Reports Hub stores normalized report rows in session,
- report history is also stored in session,
- sessions are configured to use the database driver,
- cache is also configured to use the database store.

Why this matters:

- large report windows will write large serialized payloads into the sessions table,
- this increases database load, session contention, and page latency,
- it becomes more painful as report complexity and concurrent usage rise.

Fix intent:

- remove heavy report row payloads from session storage,
- replace them with lightweight snapshot references or generated export records,
- keep session storage only for small user-state values.

## 5.4 Timezone Risk: Application Uses UTC Instead Of Nigeria Rollout Timezone

Severity: High
Status: Should be fixed before go-live

Finding:

- the application timezone is configured as `UTC`,
- reminders and scheduler logic use date-sensitive helpers such as `today()` and `now()`,
- production users are expected to operate in Nigeria time.

Why this matters:

- reminder due windows can shift,
- daily boundaries can move across midnight,
- monthly and date-window reporting can become inconsistent near edges,
- scheduler behavior may not match user expectations.

Fix intent:

- make production timezone explicit and environment-driven,
- use the intended local timezone consistently for business-facing daily logic,
- preserve correct persistence semantics for timestamps.

## 5.5 Scale Risk: Reminder Dispatch Still Uses Sync-Oriented Patterns

Severity: High
Status: Strongly recommended before go-live if reminder volume is expected

Finding:

- the scheduler can run reminder sync and dispatch in a synchronous path,
- current defaults favor sync assistance,
- the design works functionally, but not optimally for higher throughput.

Why this matters:

- scheduled runs may become slow,
- one slow provider request can delay the entire run,
- retries, idempotency, observability, and throughput control are harder in sync execution,
- the system will feel fine at low volume and then degrade sharply as volume rises.

Fix intent:

- separate reminder syncing from reminder dispatch,
- move dispatch work into queued jobs,
- support provider-safe retries and failure recovery,
- make reminder processing horizontally scalable.

## 5.6 Performance Risk: Navbar Context Performs Global Reminder Queries On Authenticated Requests

Severity: Medium-High
Status: Should be fixed soon

Finding:

- navbar composition runs on shared authenticated layouts,
- it builds quick links and reminder alert counts,
- it issues reminder and dispatch-log queries during page rendering.

Why this matters:

- every authenticated page pays extra query cost,
- request latency increases across the board,
- the problem compounds as reminder tables grow.

Fix intent:

- reduce synchronous navbar query cost,
- move alerts to cached, lazy-loaded, or separately refreshed data,
- keep the shared layout lightweight.

## 5.7 Documentation and Release Hygiene Risk

Severity: Medium
Status: Must clean before production handoff

Finding:

- `README.md` still brands the platform as `Tervis`,
- the hardening doc says Laravel 11 while the codebase is Laravel 12,
- a Markdown file contains shared test account usernames and a shared password,
- the docs are useful but not fully production-synced.

Why this matters:

- documentation drift causes deployment mistakes,
- stale branding creates confusion,
- shared credentials in release artifacts are a security hygiene issue,
- production operations need one truthful source of record.

Fix intent:

- update docs to match the real product, framework, and release state,
- remove or segregate test credentials from production-facing documentation,
- create one approved release checklist set for operations.

## 5.8 Maintainability Risk: Very Large Livewire Components And Route Closures

Severity: Medium
Status: Important for scale and change safety

Finding:

- some Livewire components are very large, especially `FacilityReports`,
- there are route closures that contain significant logic,
- some role checks are repeated inline across multiple modules.

Why this matters:

- regression risk is higher,
- onboarding and future debugging are slower,
- unit-level isolation becomes more difficult,
- production incidents are harder to diagnose under pressure.

Fix intent:

- reduce hotspot class size,
- move route closure logic into controllers or services,
- centralize repeated role and scope behavior.

## 6. Full Fix Plan

This section describes the intended complete fix strategy for all currently raised issues.

## 6.1 Fix Plan A: Remove Legacy Registration Completely

Goal:

- eliminate public registration from the platform.

Scope:

- registration route definitions,
- registration page and related view files,
- any guest auth links pointing to registration,
- any Fortify or Jetstream registration enablement,
- tests and docs that still assume public registration exists.

Implementation approach:

- disable registration at the auth/provider level,
- remove `GET /register` and `POST /register` from the app’s supported flow,
- remove legacy registration view files if they are no longer needed,
- remove any register link from login or guest layouts,
- confirm password reset flows remain intact if still required,
- add a regression test proving registration is unavailable in production.

Expected outcome:

- no public account creation path remains,
- auth surface becomes cleaner and safer,
- user creation stays controlled through central and facility admin workflows only.

## 6.2 Fix Plan B: Make MPDSR Logic And Tests Deterministic

Goal:

- get the suite fully green and make MPDSR behavior reliable.

Scope:

- MPDSR dashboard logic,
- MPDSR test setup,
- fixture isolation,
- relevant date and facility filtering behavior.

Implementation approach:

- stop relying on pre-existing facility rows in the test,
- create isolated test-owned facility, user, and delivery data,
- use database reset semantics appropriate for the suite,
- confirm exact counting rules for maternal, stillbirth, neonatal, and total deaths,
- add at least one regression test for overlapping or edge-case delivery classifications,
- verify CSV export and printable view still match the corrected logic.

Expected outcome:

- `php artisan test` becomes fully green,
- MPDSR metrics become stable and deterministic,
- the release hardening entry criterion is satisfied.

## 6.3 Fix Plan C: Redesign Report Print And Export State Handling

Goal:

- prevent session bloat and make reporting safer at larger volumes.

Scope:

- Reports Hub printable flow,
- report history storage,
- heavy export/print state persistence.

Implementation approach:

- stop writing full row payloads into session,
- introduce a lightweight generated-report reference pattern,
- store generated report payloads in a dedicated persistence layer better suited for large objects,
- prefer database table or cache/Redis-backed snapshot storage with explicit TTL and cleanup,
- keep only a small token or snapshot id in session,
- cap history size and move history from session into lightweight persistent audit records if needed.

Preferred design direction:

- add a `generated_reports` or `report_snapshots` table,
- persist report metadata, filters, creator, scope, and serialized payload outside session,
- print and export routes load by snapshot id,
- large reports can later be moved fully to queued export generation.

Expected outcome:

- smaller session rows,
- less database pressure from session storage,
- scalable export and print architecture.

## 6.4 Fix Plan D: Align Timezone And Business-Date Logic

Goal:

- make reminders, reports, and scheduler behavior match real local operations.

Scope:

- app timezone configuration,
- reminder due logic,
- scheduler tasks,
- date-window report boundaries,
- related tests and docs.

Implementation approach:

- make timezone environment-configurable and set production to the approved local timezone,
- review every use of `today()`, `now()`, and date-only comparisons in business workflows,
- ensure daily business rules use local timezone intentionally,
- preserve database timestamp consistency,
- add tests for end-of-day, month-boundary, and scheduler-edge cases.

Expected outcome:

- reminder dispatch happens on the correct day,
- dashboards and reports align with user expectation,
- fewer date-boundary defects post-launch.

## 6.5 Fix Plan E: Move Reminder Processing To Queue-First Design

Goal:

- make reminders reliable and scalable under growth.

Scope:

- scheduler command design,
- reminder sync and dispatch orchestration,
- queue workers,
- retries, failures, idempotency, observability.

Implementation approach:

- separate reminder source synchronization from provider dispatch,
- create queue jobs for reminder send attempts,
- keep scheduler focused on selecting due work and dispatching jobs,
- add idempotency protection so the same reminder is not sent twice,
- add per-channel/provider retry policy,
- improve failed-job handling and monitoring,
- prefer Redis-backed queue and cache in production if infrastructure permits.

Expected outcome:

- faster scheduler runs,
- better resilience to provider slowness,
- safer retries,
- easier horizontal scaling.

## 6.6 Fix Plan F: Reduce Global Layout Query Load

Goal:

- keep authenticated page renders fast as data volume grows.

Scope:

- navbar composer,
- reminder alert counts,
- quick links and failed-dispatch counts.

Implementation approach:

- cache navbar alert counts per user or per scope for short TTL windows,
- lazily load alert details after first paint when possible,
- avoid expensive counts on every page request,
- consider splitting “count” from “detail rows” so count is cheap and detail fetch is optional,
- make the navbar service select-only and scope-only with minimal payloads.

Expected outcome:

- lower average query count per authenticated request,
- faster page response time,
- better headroom under concurrent traffic.

## 6.7 Fix Plan G: Clean Documentation And Release Artifacts

Goal:

- make the project documentation truthful, safe, and production-usable.

Scope:

- root README,
- hardening docs,
- login credential docs,
- deployment notes,
- release checklists.

Implementation approach:

- rename and update branding from legacy `Tervis` references to `Cureva`,
- update framework/version references to match Laravel 12,
- remove shared test credential docs from the standard production doc set,
- keep test-account references only in a non-production-safe internal staging/UAT note if still needed,
- add one authoritative go-live checklist and one authoritative rollback checklist.

Expected outcome:

- less operational confusion,
- safer documentation hygiene,
- cleaner stakeholder handoff.

## 6.8 Fix Plan H: Refactor Hotspots For Long-Term Scale

Goal:

- reduce complexity concentration before it becomes a maintenance bottleneck.

Scope:

- `FacilityReports`,
- MPDSR dashboard,
- route closures with business logic,
- repeated role checks across modules.

Implementation approach:

- move large route closures into dedicated controllers or service actions,
- extract report builders into focused classes by report family,
- centralize repeated role assertions into policy/service methods,
- keep Livewire components focused on orchestration and state, not full data pipelines.

Expected outcome:

- clearer code structure,
- easier future fixes,
- safer scaling and testing.

## 7. Scale-Ready End-State Plan

The following is the intended medium-term scale posture for Cureva, even if some parts land immediately after go-live:

- production sessions on Redis rather than database,
- production cache on Redis rather than database,
- queue-backed reminders and heavy background work,
- report snapshots stored outside session,
- lightweight authenticated layouts,
- deterministic test suite in CI,
- time-sensitive workflows aligned to production timezone,
- explicit observability for slow reports, failed jobs, and delivery-provider failures,
- architecture split so very large Livewire classes no longer carry most business logic.

## 8. Recommended Implementation Sequence

This is the proposed order of work once implementation is approved.

### Phase 1: Go-Live Blockers

- remove legacy registration completely,
- fix MPDSR failing test and validate suite green,
- align timezone and re-check reminder/report date logic,
- clean production docs and remove risky credential references.

### Phase 2: Immediate Scale Hardening

- redesign report print/export state away from session payloads,
- move reminder dispatch to queue-first processing,
- reduce navbar query load.

### Phase 3: Structural Cleanup

- refactor large Livewire/report hotspots,
- move route-closure business logic into controllers/services,
- centralize repeated role logic.

## 9. Go / No-Go Recommendation As Of 2026-03-25

Recommendation: No-Go until Phase 1 is complete.

Reason:

- the suite is not fully green,
- legacy registration still exists and should be removed,
- timezone-sensitive production behavior is not yet aligned,
- documentation still contains stale and risky release content.

Conditional Go after Phase 1:

- if all Phase 1 items are completed and validated,
- and frontend build is confirmed successfully in the real deployment environment,
- and smoke tests pass in staging,
- then go-live can be reconsidered with Phase 2 scheduled immediately after release.

## 10. Approval Boundary For Next Step

If this plan is approved, implementation should proceed with the following rule:

- start with Phase 1 only,
- keep changes narrow and verifiable,
- run validation after each fix area,
- then continue to Phase 2 scale hardening once the release blockers are closed.

## 11. Proposed Validation Checklist After Implementation

After implementation, the following must be re-run:

- `php artisan test`
- `php artisan route:list`
- `php artisan route:cache`
- `php artisan config:cache`
- `php artisan view:cache`
- `php artisan migrate:status`
- frontend production build in your real environment or CI
- manual auth smoke for login, logout, reset-password flow if retained
- manual reminder smoke for sync, queue, dispatch, failure, and webhook updates
- manual report smoke for generate, print, and export across small and large windows
- role smoke for Central Admin, Facility Admin, Data Officer, Verification Officer, and Patient

## 12. Approval Notes

This document is intentionally implementation-ready but code-free.

Approved business decisions already reflected here:

- public registration is legacy and should be removed,
- the next step after approval is implementation,
- production readiness should be validated for both launch safety and early scale behavior.
