<?php

namespace App\Livewire\Patient;

use Exception;
use App\Models\User;
use App\Models\Antenatal;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PatientProfile extends Component
{
  public $user, $antenatal_record;
  public $first_name, $last_name, $email, $phone;
  public $current_password, $new_password, $new_password_confirmation;
  public $facility_name, $registration_facility_name;
  public $edit_mode = false;

  protected function rules()
  {
    return [
      'first_name' => 'required|string|max:255',
      'last_name' => 'required|string|max:255',
      'email' => 'nullable|email|max:255|unique:users,email,' . $this->user->id,
      'phone' => 'nullable|string|max:20',
      'current_password' => 'required_with:new_password|current_password',
      'new_password' => ['nullable', 'confirmed', Password::min(8)],
    ];
  }

  public function mount()
  {
    $this->user = Auth::user();

    if (!$this->user || $this->user->role !== 'Patient') {
      abort(403, 'Unauthorized: Only Patients can access this page.');
    }

    $this->loadPatientData();
  }

  private function loadPatientData()
  {
    // Load basic user data
    $this->first_name = $this->user->first_name;
    $this->last_name = $this->user->last_name;
    $this->email = $this->user->email;
    $this->phone = $this->user->phone;

    // Load antenatal record for additional information
    $this->antenatal_record = Antenatal::with('registrationFacility')
      ->where('user_id', $this->user->id)
      ->first();

    if ($this->antenatal_record) {
      $this->registration_facility_name = $this->antenatal_record->registrationFacility->name ?? 'N/A';
      $this->facility_name = $this->registration_facility_name;
    }
  }

  public function toggleEditMode()
  {
    $this->edit_mode = !$this->edit_mode;

    if (!$this->edit_mode) {
      // Reset form when canceling edit
      $this->loadPatientData();
      $this->resetPasswordFields();
    }
  }

  public function updateProfile()
  {
    $this->validate();

    try {
      // Update basic user information
      $this->user->update([
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'email' => $this->email,
        'phone' => $this->phone,
      ]);

      // Update password if provided
      if ($this->new_password) {
        $this->user->update([
          'password' => Hash::make($this->new_password)
        ]);
        $this->resetPasswordFields();
        toastr()->success('Profile and password updated successfully.');
      } else {
        toastr()->success('Profile updated successfully.');
      }

      $this->edit_mode = false;
      $this->loadPatientData();
    } catch (Exception $e) {
      toastr()->error('An error occurred while updating your profile.');
    }
  }

  private function resetPasswordFields()
  {
    $this->current_password = '';
    $this->new_password = '';
    $this->new_password_confirmation = '';
  }

  public function render()
  {
    return view('livewire.patient.patient-profile')
      ->layout('layouts.patientLayout');
  }
}
