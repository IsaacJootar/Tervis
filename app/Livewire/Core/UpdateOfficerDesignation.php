<?php

namespace App\Livewire\Core;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOfficerDesignation extends Component
{

  public $officer_id, $first_name, $last_name, $current_designation;
  public $new_designation, $facility_id;
  public $modal_flag = false;

  public $available_designations = ['Nurse', 'Doctor', 'Midwife', 'Lab Attendant', 'Volunteer'];

  protected $rules = [
    'new_designation' => 'required|in:Nurse,Doctor,Midwife,Lab Attendant,Volunteer',
  ];

  protected $messages = [
    'new_designation.required' => 'Please select a designation.',
    'new_designation.in' => 'Please select a valid designation.',
  ];

  public function mount()
  {
    $admin = Auth::user();

    $this->facility_id = $admin->facility_id;
  }

  public function openModal($officerId)
  {
    try {
      $officer = User::select('id', 'first_name', 'last_name', 'role', 'designation', 'facility_id')
        ->where('role', 'Data Officer') // Only allow Data Officers
        ->findOrFail($officerId);

      // Security check - ensure officer belongs to admin's facility
      if ($officer->facility_id !== $this->facility_id) {
        abort(403, 'Unauthorized: This officer does not belong to your facility.');
      }

      $this->officer_id = $officer->id;
      $this->first_name = $officer->first_name;
      $this->last_name = $officer->last_name;
      $this->current_designation = $officer->designation;
      $this->new_designation = $officer->designation;
      $this->modal_flag = true;

      $this->dispatch('open-designation-modal');
    } catch (\Exception $e) {
      toastr()->error('Error loading officer details.');
    }
  }

  public function updateDesignation()
  {
    DB::beginTransaction();
    try {
      $this->validate();

      $officer = User::findOrFail($this->officer_id);

      // Double-check security
      if ($officer->facility_id !== $this->facility_id || $officer->role !== 'Data Officer') {
        DB::rollBack();
        abort(403, 'Unauthorized: Invalid officer access.');
      }

      // Check if there are actual changes
      if ($this->new_designation === $this->current_designation) {
        DB::rollBack();
        toastr()->info('No changes detected.');
        $this->resetForm();
        return;
      }

      // Update the officer designation only
      $officer->update([
        'designation' => $this->new_designation,
        'updated_at' => now(),
      ]);

      // Clear relevant caches
      $this->clearOfficerCaches();

      DB::commit();

      $changeMessage = "Designation updated: {$this->first_name} {$this->last_name} is now {$this->new_designation}";
      toastr()->success($changeMessage);

      // Only reset form, do NOT close modal - stays open for more updates
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the officer designation.');
      throw $e;
    }
  }

  public function exit()
  {
    $this->resetForm();
    $this->closeModalAndRefresh();
  }

  public function closeModalAndRefresh()
  {
    $this->dispatch('close-designation-modal');
    $this->js('window.location.reload()');
  }

  private function resetForm()
  {
    $this->reset([
      'officer_id',
      'first_name',
      'last_name',
      'current_designation',
      'new_designation',
      'modal_flag',
    ]);
  }
  private function clearOfficerCaches()
  {
    // Clear officer-related caches
    Cache::forget("data_officers_facility_{$this->facility_id}");
    Cache::forget("facility_data_officers_{$this->facility_id}");
    Cache::forget("facility_{$this->facility_id}_data_officers_count");

    // Clear designation-specific caches
    foreach ($this->available_designations as $designation) {
      Cache::forget("facility_{$this->facility_id}_{$designation}_count");
    }
  }

  public function render()
  {

    // Get only Data Officers in the facility
    $dataOfficers = Cache::remember("facility_data_officers_{$this->facility_id}", 300, function () {
      return User::select('id', 'first_name', 'last_name', 'designation', 'facility_id', 'created_at')
        ->with(['facility:id,name'])
        ->where('facility_id', $this->facility_id)
        ->where('role', 'Data Officer')
        ->latest()
        ->get();
    });

    return view('livewire.core.update-officer-designation',  [
      'dataOfficers' => $dataOfficers,
      'available_designations' => $this->available_designations,
    ])->layout('layouts.facilityAdminLayout');
  }
}
