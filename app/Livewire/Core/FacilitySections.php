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

class FacilitySections extends Component
{
  public $facility_id;
  public $facility_name;

  public $section_id;
  public $name;
  public $details;
  public $is_active = true;
  public $modal_flag = false;
  public $edit_mode = false;

  protected function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:120',
        Rule::unique('bed_sections', 'name')
          ->where('facility_id', $this->facility_id)
          ->ignore($this->section_id),
      ],
      'details' => 'nullable|string|max:1000',
      'is_active' => 'boolean',
    ];
  }

  protected $messages = [
    'name.required' => 'Section name is required.',
    'name.unique' => 'This section already exists in your facility.',
  ];

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
    $this->resetForm();
    $this->edit_mode = false;
    $this->modal_flag = true;
    $this->dispatch('open-facility-section-modal');
  }

  public function openEditModal(int $sectionId): void
  {
    try {
      $section = BedSection::query()
        ->forFacility($this->facility_id)
        ->findOrFail($sectionId);

      $this->section_id = $section->id;
      $this->name = $section->name;
      $this->details = $section->details;
      $this->is_active = (bool) $section->is_active;
      $this->edit_mode = true;
      $this->modal_flag = true;

      $this->dispatch('open-facility-section-modal');
    } catch (\Throwable $e) {
      toastr()->error('Unable to load section details.');
    }
  }

  public function saveSection(): void
  {
    DB::beginTransaction();
    try {
      $this->validate();

      $payload = [
        'facility_id' => $this->facility_id,
        'name' => trim((string) $this->name),
        'details' => trim((string) $this->details) ?: null,
        'is_active' => (bool) $this->is_active,
      ];

      if ($this->edit_mode && $this->section_id) {
        $section = BedSection::query()
          ->forFacility($this->facility_id)
          ->findOrFail($this->section_id);
        $section->update($payload);
        toastr()->success("Section '{$payload['name']}' updated.");
      } else {
        BedSection::create($payload);
        toastr()->success("Section '{$payload['name']}' created.");
      }

      DB::commit();
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to save section.');
    }
  }

  public function toggleStatus(int $sectionId): void
  {
    try {
      $section = BedSection::query()
        ->forFacility($this->facility_id)
        ->findOrFail($sectionId);

      $newActive = !$section->is_active;

      if (!$newActive) {
        $occupiedCount = Bed::query()
          ->forFacility($this->facility_id)
          ->where('bed_section_id', $section->id)
          ->where('status', 'occupied')
          ->count();

        if ($occupiedCount > 0) {
          toastr()->warning('Cannot deactivate this section while occupied beds exist.');
          return;
        }
      }

      $section->update(['is_active' => $newActive]);
      toastr()->success("Section {$section->name} " . ($newActive ? 'activated' : 'deactivated') . '.');
      $this->refreshPageSoon();
    } catch (\Throwable $e) {
      toastr()->error('Unable to update section status.');
    }
  }

  public function deleteSection(int $sectionId): void
  {
    DB::beginTransaction();
    try {
      $section = BedSection::query()
        ->forFacility($this->facility_id)
        ->findOrFail($sectionId);

      $bedCount = Bed::query()
        ->forFacility($this->facility_id)
        ->where('bed_section_id', $section->id)
        ->count();

      if ($bedCount > 0) {
        toastr()->warning('Cannot delete this section because beds are linked to it.');
        DB::rollBack();
        return;
      }

      $name = $section->name;
      $section->delete();
      DB::commit();
      toastr()->success("Section {$name} deleted.");
      $this->refreshPageSoon();
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to delete section.');
    }
  }

  public function exit(): void
  {
    $this->resetForm();
    $this->dispatch('close-facility-section-modal');
  }

  public function resetModalState(): void
  {
    $this->resetForm();
  }

  private function resetForm(): void
  {
    $this->reset([
      'section_id',
      'name',
      'details',
      'is_active',
      'modal_flag',
      'edit_mode',
    ]);
    $this->is_active = true;
  }

  private function refreshPageSoon(): void
  {
    $this->js('setTimeout(() => window.location.reload(), 250)');
  }

  public function render()
  {
    $sections = BedSection::query()
      ->forFacility($this->facility_id)
      ->withCount('beds')
      ->latest()
      ->get();

    $summary = [
      'total' => $sections->count(),
      'active' => $sections->where('is_active', true)->count(),
      'inactive' => $sections->where('is_active', false)->count(),
      'with_beds' => $sections->where('beds_count', '>', 0)->count(),
    ];

    return view('livewire.core.facility-sections', [
      'sections' => $sections,
      'summary' => $summary,
    ])->layout('layouts.facilityAdminLayout');
  }
}
