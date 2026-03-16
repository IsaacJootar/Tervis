<?php

namespace App\Livewire\Core;

use App\Models\Bed;
use App\Models\BedSection;
use App\Models\Facility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class BedManagement extends Component
{
  public const BED_TYPES = [
    'general',
    'maternity',
    'postnatal',
    'pediatric',
    'icu',
    'emergency',
    'private',
  ];

  public const BED_STATUSES = [
    'available',
    'occupied',
    'maintenance',
    'inactive',
  ];

  public $facility_id;
  public $facility_name;

  public $bed_id;
  public $bed_section_id;
  public $bed_code;
  public $room_label;
  public $bed_type = 'general';
  public $status = 'available';
  public $is_active = true;
  public $notes;

  public $modal_flag = false;
  public $edit_mode = false;

  protected $messages = [
    'bed_code.unique' => 'This bed code already exists in your facility.',
    'bed_section_id.required' => 'Select ward/section.',
    'bed_type.required' => 'Bed type is required.',
    'status.required' => 'Bed status is required.',
  ];

  protected function bedRules(): array
  {
    return [
      'bed_code' => [
        'nullable',
        'string',
        'max:60',
        Rule::unique('beds')
          ->where('facility_id', $this->facility_id)
          ->ignore($this->bed_id),
      ],
      'bed_section_id' => [
        'required',
        Rule::exists('bed_sections', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'room_label' => 'nullable|string|max:120',
      'bed_type' => ['required', Rule::in(self::BED_TYPES)],
      'status' => ['required', Rule::in(self::BED_STATUSES)],
      'is_active' => 'boolean',
      'notes' => 'nullable|string|max:1000',
    ];
  }

  public function mount(): void
  {
    $admin = Auth::user();

    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }

    $this->facility_id = (int) $admin->facility_id;
    $this->facility_name = Facility::find($this->facility_id)?->name ?? 'Unknown Facility';
  }

  public function openCreateModal(): void
  {
    if (!$this->hasSections()) {
      toastr()->warning('Create at least one Facility Section before adding beds.');
      return;
    }

    $this->resetBedForm();
    $this->edit_mode = false;
    $this->modal_flag = true;
    $this->dispatch('open-bed-modal');
  }

  public function openEditModal(int $bedId): void
  {
    try {
      $bed = Bed::query()
        ->forFacility($this->facility_id)
        ->findOrFail($bedId);

      $matchedSectionId = $bed->bed_section_id;
      if (!$matchedSectionId) {
        $matchedSectionId = BedSection::query()
          ->forFacility($this->facility_id)
          ->where('name', $bed->ward_section)
          ->value('id');
      }

      $this->bed_id = $bed->id;
      $this->bed_section_id = $matchedSectionId;
      $this->bed_code = $bed->bed_code;
      $this->room_label = $bed->room_label;
      $this->bed_type = $bed->bed_type;
      $this->status = $bed->status;
      $this->is_active = (bool) $bed->is_active;
      $this->notes = $bed->notes;

      $this->edit_mode = true;
      $this->modal_flag = true;
      $this->dispatch('open-bed-modal');
    } catch (\Throwable $e) {
      toastr()->error('Unable to load bed details.');
    }
  }

  public function saveBed(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->bedRules());

      $section = BedSection::query()
        ->forFacility($this->facility_id)
        ->findOrFail((int) $this->bed_section_id);

      $normalizedStatus = $this->is_active ? $this->status : 'inactive';
      $isOccupied = $normalizedStatus === 'occupied';
      $resolvedCode = $this->resolveBedCode($this->bed_code);

      $data = [
        'facility_id' => $this->facility_id,
        'bed_section_id' => $section->id,
        'bed_code' => $resolvedCode,
        'ward_section' => $section->name,
        'room_label' => trim((string) $this->room_label) ?: null,
        'bed_type' => $this->bed_type,
        'status' => $normalizedStatus,
        'is_active' => (bool) $this->is_active,
        'notes' => trim((string) $this->notes) ?: null,
        'last_status_changed_at' => now(),
      ];

      if (!$isOccupied) {
        $data['occupied_by_patient_id'] = null;
        $data['occupied_since'] = null;
      }

      if ($this->edit_mode && $this->bed_id) {
        $bed = Bed::query()
          ->forFacility($this->facility_id)
          ->findOrFail($this->bed_id);

        if ($isOccupied && !$bed->occupied_since) {
          $data['occupied_since'] = now();
        }

        $bed->update($data);
        toastr()->success("Bed '{$data['bed_code']}' updated.");
      } else {
        if ($isOccupied) {
          $data['occupied_since'] = now();
        }

        Bed::create($data);
        toastr()->success("Bed '{$data['bed_code']}' created.");
      }

      DB::commit();
      $this->resetBedForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to save bed record.');
    }
  }

  public function setStatus(int $bedId, string $status): void
  {
    if (!in_array($status, self::BED_STATUSES, true)) {
      toastr()->error('Invalid bed status.');
      return;
    }

    try {
      $bed = Bed::query()
        ->forFacility($this->facility_id)
        ->findOrFail($bedId);

      if (!$bed->is_active && $status !== 'inactive') {
        toastr()->warning('Activate this bed first before assigning operational status.');
        return;
      }

      $isOccupied = $status === 'occupied';

      $bed->update([
        'status' => $status,
        'occupied_by_patient_id' => $isOccupied ? $bed->occupied_by_patient_id : null,
        'occupied_since' => $isOccupied ? ($bed->occupied_since ?? now()) : null,
        'last_status_changed_at' => now(),
      ]);

      toastr()->success("Bed {$bed->bed_code} marked as {$status}.");
      $this->refreshPageSoon();
    } catch (\Throwable $e) {
      toastr()->error('Unable to update bed status.');
    }
  }

  public function toggleActive(int $bedId): void
  {
    try {
      $bed = Bed::query()
        ->forFacility($this->facility_id)
        ->findOrFail($bedId);

      $newActive = !$bed->is_active;

      $bed->update([
        'is_active' => $newActive,
        'status' => $newActive ? 'available' : 'inactive',
        'occupied_by_patient_id' => $newActive ? $bed->occupied_by_patient_id : null,
        'occupied_since' => $newActive ? $bed->occupied_since : null,
        'last_status_changed_at' => now(),
      ]);

      toastr()->success("Bed {$bed->bed_code} " . ($newActive ? 'activated' : 'deactivated') . '.');
      $this->refreshPageSoon();
    } catch (\Throwable $e) {
      toastr()->error('Unable to change bed activation status.');
    }
  }

  public function deleteBed(int $bedId): void
  {
    DB::beginTransaction();
    try {
      $bed = Bed::query()
        ->forFacility($this->facility_id)
        ->findOrFail($bedId);

      if ($bed->status === 'occupied') {
        toastr()->warning('Cannot delete an occupied bed. Mark it available first.');
        DB::rollBack();
        return;
      }

      $code = $bed->bed_code;
      $bed->delete();

      DB::commit();
      toastr()->success("Bed {$code} deleted.");
      $this->refreshPageSoon();
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to delete bed.');
    }
  }

  public function exit(): void
  {
    $this->resetBedForm();
    $this->dispatch('close-bed-modal');
  }

  public function resetModalState(): void
  {
    $this->resetBedForm();
  }

  private function resetBedForm(): void
  {
    $this->reset([
      'bed_id',
      'bed_section_id',
      'bed_code',
      'room_label',
      'bed_type',
      'status',
      'is_active',
      'notes',
      'modal_flag',
      'edit_mode',
    ]);

    $this->bed_type = 'general';
    $this->status = 'available';
    $this->is_active = true;
  }

  private function resolveBedCode(?string $code): string
  {
    $manualCode = strtoupper(trim((string) $code));
    if ($manualCode !== '') {
      return $manualCode;
    }

    $counter = Bed::query()->forFacility($this->facility_id)->count() + 1;
    do {
      $generated = 'BED-' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
      $exists = Bed::query()
        ->forFacility($this->facility_id)
        ->where('bed_code', $generated)
        ->exists();
      $counter++;
    } while ($exists);

    return $generated;
  }

  private function hasSections(): bool
  {
    return BedSection::query()->forFacility($this->facility_id)->exists();
  }

  private function refreshPageSoon(): void
  {
    $this->js('setTimeout(() => window.location.reload(), 250)');
  }

  public function render()
  {
    $beds = Bed::query()
      ->with(['occupiedByPatient:id,din,first_name,last_name', 'section:id,name'])
      ->forFacility($this->facility_id)
      ->latest()
      ->get();

    $summary = [
      'total' => $beds->count(),
      'available' => $beds->where('status', 'available')->count(),
      'occupied' => $beds->where('status', 'occupied')->count(),
      'maintenance' => $beds->where('status', 'maintenance')->count(),
      'inactive' => $beds->where('is_active', false)->count(),
    ];

    $sectionOptions = BedSection::query()
      ->forFacility($this->facility_id)
      ->where('is_active', true)
      ->orderBy('name')
      ->get(['id', 'name']);

    if ($this->bed_section_id && !$sectionOptions->firstWhere('id', (int) $this->bed_section_id)) {
      $existing = BedSection::query()
        ->forFacility($this->facility_id)
        ->where('id', (int) $this->bed_section_id)
        ->first(['id', 'name']);
      if ($existing) {
        $sectionOptions->push($existing);
      }
    }

    return view('livewire.core.bed-management', [
      'beds' => $beds,
      'sectionOptions' => $sectionOptions,
      'summary' => $summary,
      'bedTypes' => self::BED_TYPES,
      'bedStatuses' => self::BED_STATUSES,
    ])->layout('layouts.facilityAdminLayout');
  }
}
