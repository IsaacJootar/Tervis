<?php

namespace App\Livewire\Core;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisableDataOfficerAccount extends Component
{
  public $officer_id, $first_name, $last_name, $current_account_status;
  public $new_account_status, $facility_id;
  public $modal_flag = false;

  public $available_statuses = ['active', 'disabled'];

  protected $rules = [
    'new_account_status' => 'required|in:active,disabled',
  ];

  protected $messages = [
    'new_account_status.required' => 'Please select an account status.',
    'new_account_status.in' => 'Please select a valid account status.',
  ];

  public function mount()
  {
    $admin = Auth::user();

    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }

    $this->facility_id = $admin->facility_id;
  }

  public function openModal($officerId)
  {
    try {
      $officer = User::select('id', 'first_name', 'last_name', 'role', 'account_status', 'facility_id')
        ->where('role', 'Data Officer')
        ->findOrFail($officerId);

      // Security check - ensure officer belongs to admin's facility
      if ($officer->facility_id !== $this->facility_id) {
        abort(403, 'Unauthorized: This officer does not belong to your facility.');
      }

      // Prevent admin from changing their own account status
      if ($officer->id === Auth::id()) {
        toastr()->error('You cannot change your own account status.');
        return;
      }

      $this->officer_id = $officer->id;
      $this->first_name = $officer->first_name;
      $this->last_name = $officer->last_name;
      $this->current_account_status = $officer->account_status ?? 'active';
      $this->new_account_status = $this->current_account_status;
      $this->modal_flag = true;

      $this->dispatch('open-account-modal');
    } catch (\Exception $e) {
      toastr()->error('Error loading officer details.');
    }
  }

  public function updateAccountStatus()
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

      // Prevent changing own account status
      if ($officer->id === Auth::id()) {
        DB::rollBack();
        toastr()->error('You cannot change your own account status.');
        return;
      }

      // Check if there are actual changes
      if ($this->new_account_status === $this->current_account_status) {
        DB::rollBack();
        toastr()->info('No changes detected.');
        $this->resetForm();
        return;
      }

      //dd($this->new_account_status);
      // Update the officer account status
      $officer::where('id', $this->officer_id)
        ->update([
          'account_status' => $this->new_account_status,
          'updated_at'     => now(),
        ]);


      // Clear relevant caches
      $this->clearOfficerCaches();

      DB::commit();

      $statusText = ucfirst($this->new_account_status);
      $changeMessage = "Account status updated: {$this->first_name} {$this->last_name} is now {$statusText}";
      toastr()->info($changeMessage);

      // Only reset form, do NOT close modal - stays open for more updates
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the account status.');
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
    $this->dispatch('close-account-modal');
    $this->js('window.location.reload()');
  }

  private function clearOfficerCaches()
  {
    // Clear officer-related caches
    Cache::forget("data_officers_facility_{$this->facility_id}");
    Cache::forget("facility_data_officers_{$this->facility_id}");
    Cache::forget("facility_{$this->facility_id}_data_officers_count");

    // Clear status-specific caches
    foreach ($this->available_statuses as $status) {
      Cache::forget("facility_{$this->facility_id}_{$status}_officers_count");
    }
  }

  private function resetForm()
  {
    $this->reset([
      'officer_id',
      'first_name',
      'last_name',
      'current_account_status',
      'new_account_status',
      'modal_flag',
    ]);
  }

  public function render()
  {
    // Get only Data Officers in the facility
    $dataOfficers = Cache::remember("facility_data_officers_status_{$this->facility_id}", 300, function () {
      return User::select('id', 'first_name', 'last_name', 'account_status', 'facility_id', 'created_at')
        ->with(['facility:id,name'])
        ->where('facility_id', $this->facility_id)
        ->where('role', 'Data Officer')
        ->latest()
        ->get();
    });

    return view('livewire.core.disable-data-officer-account', [
      'dataOfficers' => $dataOfficers,
      'available_statuses' => $this->available_statuses,
    ])->layout('layouts.facilityAdminLayout');
  }
}
