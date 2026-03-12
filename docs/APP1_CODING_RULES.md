# APP1 Coding, UI, and Workflow Rules

Last updated: 2026-03-11  
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

## 2) Route and Module Rules

1. Patient activity modules must live under `workspaces/{patientId}/...`.
2. Route names must follow `workspaces-*` naming convention and stay stable once used.
3. If a module is renamed (example: `activity-register` -> `vaccination-schedule`), keep a redirect alias route until all links are migrated.
4. Dashboard cards must only show routable modules; dead/nonexistent routes remain hidden.

## 3) UI Rules (Project Standard)

0. Follow `docs/APP1_UI_STYLE_GUIDE.md` for module layout, responsive behavior, tabs, charts, and loading-state details.
1. Reuse the existing module template language already used in Child Health, Laboratory, and Prescriptions.
2. For dense forms, place tabs first at the top with icon labels.
3. Child/patient context blocks inside forms should be read-only display (not editable inputs) when data is already known.
4. Use Flowdesk-style clean section cards and keep App1 default blue as the primary accent.
5. Selected option states must be visually obvious (high-contrast selected style).

## 4) Interaction Rules

1. Every submit/action button must have loading states (`wire:loading`, disabled state, and loading label).
2. No silent failures: success/error feedback must always appear (toast or inline error).
3. Pending task flows (Lab Orders, Prescriptions) must require explicit user selection before completion/checkout.
4. Persist cart-like workflows in session per user+patient where checkout is a single final commit.

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
3. No handover without documentation updates:
   - `docs/APP1_MODULE_STATUS.md`
   - `docs/APP1_WORKFLOW_ROADMAP.md`
   - relevant gap/handoff docs.
