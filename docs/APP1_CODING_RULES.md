# APP1 Coding, UI, and Workflow Rules

Last updated: 2026-03-12  
Scope: `app1` (patient workspace + analytics + facility-facing modules)

These rules are mandatory for all new module work and refactors.

Primary UI reference for design decisions:
- `docs/APP1_UI_STYLE_GUIDE.md`

## 1) Data Model and Identity Rules

1. One patient keeps one DIN permanently across all program files and activities.
2. Every activity record must carry: `patient_id`, `facility_id`, `month_year`, activity date, and officer metadata.
3. Program registration is separate from activity recording:
   - Registers create/enroll program files.
   - Workspace modules capture clinical activities.
4. Do not duplicate base patient/facility identity fields in activity forms when already auto-resolved from context.
5. Strict scope mode:
   - Patient identity and one-time program registration checks may be global (cross-facility) to prevent duplicate registrations.
   - Activity records, activity-based summaries, reminders, and appointments must remain facility-scoped (`facility_id` + `patient_id`).

## 2) Route and Module Rules

1. Patient activity modules must live under `workspaces/{patientId}/...`.
2. Route names must follow `workspaces-*` naming convention and stay stable once used.
3. If a module is renamed (example: `activity-register` -> `vaccination-schedule`), keep a redirect alias route until all links are migrated.
4. Dashboard cards must only show routable modules; dead/nonexistent routes remain hidden.
5. Do not split one workflow into multiple legacy pages in navigation (no repeated modules):
   - Use one canonical module route/page per workflow (example: staff management).
   - Keep old legacy routes as redirects to the canonical page for backward compatibility.
6. Module access hard rule:
   - All module routes under `workspaces/{patientId}/...` must include `module.enabled:{module_key}` middleware.
   - Core routes that represent facility module operations (for example laboratory/pharmacy operations, reminders hub, appointments, reports hub) must also include `module.enabled:{module_key}` middleware where a module key exists.
   - Missing `facility_module_accesses` rows default to allowed; explicit disabled rows must return `403`.
   - Enable/disable control must be performed in Central Admin only (`/central/facility-module-management`); facility-side pages may display status but must not own toggle actions.
7. Central menu/link hygiene hard rule:
   - Central sidebar entries must point only to real, routable URLs.
   - When replacing or removing central legacy URLs, add compatibility redirects for old bookmarks under `/central-admin/*`.
   - No dead placeholder links should remain in `resources/menu/centralAdminMenu.json`.

## 3) UI Rules (Project Standard)

0. Follow `docs/APP1_UI_STYLE_GUIDE.md` for module layout, responsive behavior, tabs, charts, and loading-state details.
1. Reuse the existing module template language already used in Child Health, Laboratory, and Prescriptions.
2. For dense forms, place tabs first at the top with icon labels.
3. Child/patient context blocks inside forms should be read-only display (not editable inputs) when data is already known.
4. Use Flowdesk-style clean section cards and keep App1 default blue as the primary accent.
5. Selected option states must be visually obvious (high-contrast selected style).
6. Workspace history tables must always be paginated.
   - Default/normal-load pages may use register-style DataTable (`_partials/datatables-init-multi`) with export/search.
   - Heavy-load pages must use server-side pagination/filtering in Livewire/Eloquent (`paginate`) instead of client-side in-memory DataTable processing.
   - Do not ship plain non-paginated history tables.
7. Core/facility operational tables follow the same table standard as workspace tables:
   - Pagination + search + export controls are mandatory.
   - Use `@include('_partials.datatables-init-multi', ...)` for multiple tables in one page.
   - If server-side pagination is required for scale, add explicit export actions so users can still export filtered datasets.
   - Markup requirement (non-negotiable):
     - wrapper: `<div class="card-datatable table-responsive pt-0" wire:ignore>`
     - table: unique `id` per table and `class="table align-middle"`
     - init include:
       - single table: `@include('_partials.datatables-init')`
       - multiple tables: `@include('_partials.datatables-init-multi', ...)`
8. Card styling scope:
   - Use multi-color tinted styling for stat/metric summary cards only.
   - Do not apply tinted stat-card styling across all form/content cards.
   - Stat cards must include a visible icon for each metric.
   - Preferred implementation is inline SVG inside `.metric-icon` (avoid icon-font-only dependency for critical card icons).
9. Every workspace query must respect tenancy scope by default:
   - always filter by `facility_id`
   - for patient workspace modules, also filter by `patient_id`
   - never query cross-facility data unless the module explicitly requires aggregated scope and user role allows it.
10. Exception for one-time registration gates:
   - duplicate-prevention checks for one-time registrations (example: Family Planning baseline registration) should check by `patient_id` globally.
   - once the gate passes, all follow-up/activity writes and reads remain facility-scoped.
11. Legacy UI refactor rule (hard rule):
   - When touching any legacy module/page, do not keep legacy hero-banner layouts.
   - Refactor to the current simple template header pattern used in new modules (badge + title/time + right-aligned primary action).
   - Primary create/open actions must have loading states.
   - Every stat/metric card must include a visible icon; no icon-less placeholders or empty icon circles.
   - Use inline SVG in `.metric-icon` for stat cards where possible to avoid icon-font rendering failures.

## 4) Interaction Rules

1. Submit/commit actions must have loading states (`wire:loading`, disabled state, loading label).
2. Pure UI launcher actions (for example open modal) should avoid loading labels unless they perform long server work.
3. Modal open buttons must call dedicated open methods (for example `openCreateModal`) and must not call close/reset handlers.
4. No silent failures: success/error feedback must always appear (toast or inline error).
5. Pending task flows (Lab Orders, Prescriptions) must require explicit user selection before completion/checkout.
6. Persist cart-like workflows in session per user+patient where checkout is a single final commit.
7. Modal save behavior hard rule:
   - save actions inside modals must not auto-close the modal.
   - success and error toasts must always be shown for save attempts.
   - page refresh should occur only after the user manually closes the modal (when refresh is needed).

## 5) Validation Rules

1. Support backlog entry where requested: do not over-restrict activity dates to "today only".
2. Keep clinically valid minimum checks where required (for example, child-linked constraints such as DOB lower bounds when applicable).
3. For tabbed composite forms, validate only relevant fields for the operation being performed.

## 6) Analytics and NHMIS Mapping Rules

1. Any new clinical field that should affect monthly reporting must be documented with:
   - source model/table field,
   - target summary key,
   - NHMIS row/key impact.
2. When mapping changes are made, update both:
   - `docs/monthly-report-gap-check.md`
   - `docs/handoff.md`
3. Avoid heuristic-only mappings where structured fields can be added.

## 7) Testing and Handover Rules

1. Before handover, run at minimum:
   - `php artisan route:list`
   - `php artisan test`
2. For new workflow-heavy modules, add feature tests for critical transitions (`pending` -> `completed` / `dispensed`).
3. For new module routes, add access tests for `module.enabled` enforcement:
   - disabled module row returns `403`
   - enabled module row returns `200`
   - missing module row remains allowed unless product policy changes
4. No handover without documentation updates:
   - `docs/APP1_MODULE_STATUS.md`
   - `docs/APP1_WORKFLOW_ROADMAP.md`
   - relevant gap/handoff docs.
