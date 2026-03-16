# APP1 UI Style Guide (Mobile-First, Flowdesk-Inspired)

Last updated: 2026-03-12  
Reference baseline: `FLOWDESK_UI_GUIDE.md` adapted for healthcare workflow in `app1`.

## 1) UI Principles (Non-Negotiable)

1. Mobile-first always: design for phone first, then scale up.
2. Clean and readable: clear labels, low clutter, predictable spacing.
3. Workflow-first: screen layout must match clinical flow, not raw database structure.
4. Immediate feedback: users must always know when data is saving, saved, or failed.
5. Consistency across modules: tabs, section headers, buttons, tables, and toasts should feel uniform.
6. Visual language should follow Flowdesk clean style where appropriate, with App1 default blue as the primary accent.

## 2) Responsive Breakpoint Rules

1. Phone (`<576px`): single-column layout, stacked fields, sticky primary actions where needed.
2. Tablet (`>=576px` and `<992px`): 2-column where safe, keep tab labels visible and touch-friendly.
3. Desktop (`>=992px`): multi-column forms/tables with wider context panels.
4. Any screen with dense forms/charts: prefer full page over modal.

## 3) Layout Pattern for Patient Modules

Use this order unless module-specific reasons require otherwise:
1. Page header (title + back action).
2. Read-only patient context summary (DIN/name/age/sex/guardian when relevant).
3. Tabs (with icon labels) at top.
4. Active tab content sections.
5. Activity records table/history lower on page.

Notes:
- Do not place long record tables above primary clinical entry tabs.
- Do not duplicate patient/facility fields as editable inputs when already in context.

## 4) Tabs and Navigation Standards

1. Tabs must use icon + text labels (example: Child Info, Vaccinations, Weight Monitoring, Breastfeeding, AEFI).
2. Active tab must have high-contrast state and obvious indicator.
3. Keep tab labels short and plain-language.
4. Tab interactions must not refresh the page.
5. If tabs overflow on mobile, use horizontal scroll with visible active tab.

## 5) Section Demarcation and Color System

1. Use clean Flowdesk-style section cards: neutral background, soft border, readable section title.
2. Primary accent should remain App1 default blue (gold is not the default).
3. Keep backgrounds neutral and text high-contrast.
4. Never rely on color alone to communicate selected/required/error state.
5. Summary metric cards should use distinct soft tints (for example slate/sky/emerald/rose/violet) with matching border/text contrast.
6. Scope rule: apply this tinted style to stat/metric/dashboard summary cards only (counts/KPI cards), not all content/form cards.
7. Stat cards in the same row should use a consistent minimum height for visual alignment.

## 5b) Form and Modal Composition (Flowdesk Pattern)

1. Prefer sectionized forms with clear labels and consistent spacing.
2. Long forms should be split into sections/tabs to reduce visual overload.
3. Long modals must be scrollable (`max-height` + `overflow-y-auto`) with reachable action footer.
4. If a modal becomes too dense for mobile usability, move the flow to a full page.
5. Keep modal actions consistent: cancel + primary submit with loading labels.

## 6) Forms: Labels, Inputs, and Validation

1. Labels are mandatory and explicit; placeholders are examples only.
2. Keep labels concise and clinically recognizable.
3. Group related fields into card/section blocks.
4. For non-editable context fields, render as read-only text rows, not disabled textboxes.
5. Show validation errors close to field and summarize critical errors via toast.
6. Backlog entry support: date validations should not block legitimate historical entry unless clinically unsafe.

## 7) Buttons and Loading States (Mandatory)

1. Every submit/commit action button must have `wire:loading` state with clear loading text.
2. UI launcher buttons (open modal, switch tab, expand/collapse) should not show spinner labels unless they trigger long-running server work.
3. Modal open flow must use a dedicated opener method (for example `openCreateModal`) that resets state and opens the modal only.
4. Never bind modal open buttons to close/reset handlers (for example `exit`) because this causes open-then-close flicker.
5. Disable controls while request is active to prevent double-submit.
6. Use consistent verbs:
   - `Save Record...`
   - `Update Record...`
   - `Submitting...`
   - `Processing...`
7. Pending-selection workflows (lab/prescription) must enforce user acknowledgment before completion.

## 8) Table and History Standards

1. Keep search/filter controls aligned and consistent across modules.
2. Reuse register-style DataTable behavior in workspace history tables: pagination, export controls, and responsive layout.
3. For a single primary table in a module view, prefer `id="dataTable"` + `wire:ignore` + `@include('_partials.datatables-init')`.
4. For multiple tables in one view, use `@include('_partials.datatables-init-multi', ...)` with explicit table IDs and order rules.
5. Use server-side pagination only when dataset size or performance requires it.
6. Record status must be visible with badge/chip styles.
7. Row actions must be explicit and safe (Edit, Delete, View, Print where relevant).
8. Show empty states with next-step message.
9. Export controls are mandatory on operational tables (Copy/Excel/CSV/PDF via DataTable buttons or an equivalent server-side export endpoint).

## 9) Chart and Data Visualization Standards

1. Clinical charts (for example growth chart) must always plot saved database values.
2. Chart must reload when data is added/updated without requiring manual refresh.
3. Show baseline/reference lines separately from patient plot points.
4. If no data exists, show a clear empty-state helper message.
5. Keep axis labels readable on mobile (avoid overlap).

## 10) Feedback, Toasts, and Errors

1. Success/error toasts must be compact, readable, and positioned consistently.
2. No silent button actions.
3. For blocking failures, keep user input state when possible.
4. System errors should be human-readable first, technical detail optional.

## 11) Accessibility and Touch UX

1. Minimum touch target: 40px height for primary interactive elements.
2. Visible focus styles for keyboard navigation.
3. Maintain readable contrast ratio on labels, badges, and active states.
4. Avoid tiny icon-only controls without text or tooltip.

## 12) Implementation Checklist for New Module UI

1. Mobile layout verified first.
2. Tabs have icon labels and active-state contrast.
3. Section headers use APP1 demarcation style.
4. Forms have explicit labels and inline validation.
5. Every action button has loading/disabled states.
6. Modal launcher buttons do not use loading text and do not call close/reset handlers.
7. Table/history placed below primary data-entry workflow.
8. Charts (if any) read from persisted data and render after save.
9. Toasts show success/error for all major actions.

## 13) Definition of Done (UI)

A module UI is done only when:
1. It is fully usable on mobile without horizontal form breakage.
2. Workflow is clear to first-time users.
3. Loading and feedback states cover all submit/update/delete actions.
4. Labels, sections, and tab behavior are consistent with other completed modules.
