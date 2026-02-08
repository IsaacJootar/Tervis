<?php

namespace App\Livewire\Patient;

use Livewire\Component;
use Exception;
use App\Models\User;
use App\Models\Antenatal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PatientAntenatal extends Component
{
  public $user, $antenatal_records, $selected_record;
  public $modal_flag = false;
  public $facility_name, $registration_facility_name;

  public function mount()
  {
    $this->user = Auth::user();

    if (!$this->user || $this->user->role !== 'Patient') {
      abort(403, 'Unauthorized: Only Patients can access this page.');
    }

    $this->loadAntenatalRecords();
  }

  private function loadAntenatalRecords()
  {
    $this->antenatal_records = Antenatal::with(['user', 'registrationFacility'])
      ->where('user_id', $this->user->id)
      ->latest('date_of_booking')
      ->get();

    // Get facility information from first record
    $firstRecord = $this->antenatal_records->first();
    if ($firstRecord) {
      $this->registration_facility_name = $firstRecord->registrationFacility->name ?? 'N/A';
      $this->facility_name = $this->registration_facility_name;
    }
  }

  public function viewRecord($id)
  {
    try {
      $this->selected_record = Antenatal::with(['user', 'registrationFacility'])
        ->where('id', $id)
        ->where('user_id', $this->user->id) // Security check
        ->firstOrFail();

      $this->modal_flag = true;
      $this->dispatch('open-view-modal');
    } catch (Exception $e) {
      toastr()->error('Record not found or access denied.');
    }
  }

  public function closeModal()
  {
    $this->modal_flag = false;
    $this->selected_record = null;
  }

  public function render()
  {
    return view('livewire.patient.patient-antenatal')
      ->layout('layouts.patientLayout');
  }
}
