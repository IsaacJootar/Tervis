<?php

namespace App\Livewire\Core;

use App\Models\FacilityDepartment;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class FacilityDepartments extends Component
{
  public $department_id, $name, $details, $is_active = true;
  public $facility_id;
  public $modal_flag = false;
  public $edit_mode = false;

  protected function rules()
  {
    return [
      'name' => [
        'required',
        'string',
        'max:100',
        Rule::unique('facility_departments')
          ->where('facility_id', $this->facility_id)
          ->ignore($this->department_id)
      ],
      'details' => 'nullable|string|max:1000',
      'is_active' => 'boolean',
    ];
  }

  protected $messages = [
    'name.required' => 'Department name is required.',
    'name.max' => 'Department name cannot exceed 100 characters.',
    'name.unique' => 'A department with this name already exists in your facility.',
    'details.max' => 'Details cannot exceed 1000 characters.',
  ];

  public function mount()
  {
    $admin = Auth::user();

    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }

    $this->facility_id = $admin->facility_id;
  }

  public function openCreateModal()
  {
    $this->resetForm();
    $this->edit_mode = false;
    $this->modal_flag = true;
    $this->dispatch('open-department-modal');
  }

  public function openEditModal($departmentId)
  {
    try {
      $department = FacilityDepartment::where('facility_id', $this->facility_id)
        ->findOrFail($departmentId);

      $this->department_id = $department->id;
      $this->name = $department->name;
      $this->details = $department->details;
      $this->is_active = $department->is_active;
      $this->edit_mode = true;
      $this->modal_flag = true;

      $this->dispatch('open-department-modal');
    } catch (\Exception $e) {
      toastr()->error('Error loading department details.');
    }
  }

  public function saveDepartment()
  {
    DB::beginTransaction();
    try {
      $this->validate();

      $data = [
        'facility_id' => $this->facility_id,
        'name' => trim($this->name),
        'details' => trim($this->details) ?: null,
        'is_active' => $this->is_active,
      ];

      if ($this->edit_mode && $this->department_id) {
        // Update existing department
        $department = FacilityDepartment::where('facility_id', $this->facility_id)
          ->findOrFail($this->department_id);

        $department->update($data);
        $message = "Department '{$this->name}' updated successfully";
      } else {
        // Create new department
        FacilityDepartment::create($data);
        $message = "Department '{$this->name}' created successfully";
      }

      DB::commit();
      toastr()->info($message);

      // Only reset form, do NOT close modal - stays open for more operations
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving the department.');
      throw $e;
    }
  }

  public function toggleStatus($departmentId)
  {
    try {
      $department = FacilityDepartment::where('facility_id', $this->facility_id)
        ->findOrFail($departmentId);

      $newStatus = !$department->is_active;
      $department->update(['is_active' => $newStatus]);

      $statusText = $newStatus ? 'activated' : 'deactivated';
      toastr()->info("Department '{$department->name}' has been {$statusText}");

      // Delay reload to allow toastr to show
      $this->js('setTimeout(() => window.location.reload(), 1500)');
    } catch (\Exception $e) {
      toastr()->error('Error updating department status.');
    }
  }

  public function deleteDepartment($departmentId)
  {
    DB::beginTransaction();
    try {
      $department = FacilityDepartment::where('facility_id', $this->facility_id)
        ->findOrFail($departmentId);

      $departmentName = $department->name;
      $department->delete();

      DB::commit();
      toastr()->info("Department '{$departmentName}' deleted successfully");

      // Delay reload to allow toastr to show
      $this->js('setTimeout(() => window.location.reload(), 1500)');
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('Error deleting department. It may be associated with other records.');
    }
  }

  public function exit()
  {
    $this->resetForm();
    $this->closeModalAndRefresh();
  }

  public function closeModalAndRefresh()
  {
    $this->dispatch('close-department-modal');
    $this->js('window.location.reload()');
  }

  private function clearDepartmentCaches()
  {
    Cache::forget("facility_departments_{$this->facility_id}");
    Cache::forget("active_departments_facility_{$this->facility_id}");
    Cache::forget("facility_{$this->facility_id}_departments_count");
  }

  private function resetForm()
  {
    $this->reset([
      'department_id',
      'name',
      'details',
      'is_active',
      'modal_flag',
      'edit_mode',
    ]);
    $this->is_active = true; // Reset to default
  }

  public function render()
  {
    // Get departments for the facility - no caching needed for small datasets
    $departments = FacilityDepartment::with(['facility:id,name'])
      ->where('facility_id', $this->facility_id)
      ->latest()
      ->get();

    return view('livewire.core.facility-departments', [
      'departments' => $departments,
    ])->layout('layouts.facilityAdminLayout');
  }
}
