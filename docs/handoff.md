# Handoff Notes (2026-02-07)

## Summary
- Attendance module moved into module folder and restyled to a Vuexy-like profile layout.
- ANC Tetanus Vaccinations module moved into ANC module folder, route updated, and view restyled to match Attendance while keeping CRUD + modal.
- NHIS labels updated to "NHIS Subscriber" / "NHIS Non-Subscriber" in Attendance and Workspace Dashboard.

## Key Decisions
- Keep DataTable default markup (same as registers) in modules; avoid partials for now.
- Modules follow the 6-pattern plan (module-per-card, route per module, single-module workflow, consistent patient context, module activity logging later, card->module mapping).
- Attendance is read-only; no modal.
- Tetanus Vaccinations uses modal create/edit + delete.

## Files Changed
- `app/Livewire/Workspaces/Modules/Attendance.php` (view path to `attendance/index`)
- `resources/views/livewire/workspaces/modules/attendance/index.blade.php`
  - Vuexy-style header, patient overview panel, DataTable layout, NHIS label change.
- `resources/views/livewire/workspaces/workspace-dashboard.blade.php`
  - NHIS label change.
- `app/Livewire/Workspaces/Modules/ANC/TetanusVaccinations.php`
  - Namespace + view path updated.
- `resources/views/livewire/workspaces/modules/anc/tetanus-vaccinations.blade.php`
  - Vuexy-style layout, DataTable + CRUD + modal retained.
- `app/Livewire/Registers/TetanusRegister.php`
  - Renamed/moved from `TetanusVaccinations.php`, namespace/class/view updated.
- `routes/web.php`
  - Import updated to `App\Livewire\Workspaces\Modules\ANC\TetanusVaccinations`
  - ANC TT route now: `/workspaces/{patientId}/anc/tetanus-vaccinations`

## Folder Moves
- `app/Livewire/Workspaces/Antenatal/TetanusVaccinations.php`
  -> `app/Livewire/Workspaces/Modules/ANC/TetanusVaccinations.php`
- `resources/views/livewire/workspaces/antenatal/tetanus-vaccinations.blade.php`
  -> `resources/views/livewire/workspaces/modules/anc/tetanus-vaccinations.blade.php`
- Deleted empty Antenatal folders.

## Open Items / Next Steps
1. Verify `backToDashboard()` in `app/Livewire/Workspaces/Modules/ANC/TetanusVaccinations.php` points to correct route (currently `patient.dashboard`).
2. Decide if ANC should have a landing page listing internal activities (Tetanus, Postnatal, Delivery, Follow-up Assessment).
3. Confirm UI in browser (Attendance + Tetanus) and adjust layout if needed.

## Notes
- IDE tabs may still point to old paths (e.g., `workspaces/antenatal/...`). Close/reopen correct files.
## Next Step (Suggested)
1. Verify ANC Tetanus `backToDashboard()` route in `app/Livewire/Workspaces/Modules/ANC/TetanusVaccinations.php` (currently `patient.dashboard`).
2. Open Attendance and ANC Tetanus in browser to confirm layout + CRUD works.
3. Decide if ANC landing page is needed for Follow-up Assessments and other ANC activities.
## Multi-Activity Module Note
- Some modules will contain multiple CRUD activities (multiple tables + modals) in one module.
- Plan to group by sections or tabs/accordion to keep the page clean.
