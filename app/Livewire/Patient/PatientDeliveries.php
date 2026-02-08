<?php

namespace App\Livewire\Patient;

use Exception;
use App\Models\Delivery;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class PatientDeliveries extends Component
{
  use WithPagination;

  protected $paginationTheme = 'bootstrap';

  public $user;
  public $selected_record;
  public $modal_flag = false;
  public $facility_name;
  public $registration_facility_name;

  public function mount()
  {
    $this->user = Auth::user();

    if (!$this->user || $this->user->role !== 'Patient') {
      toastr()->error('Unauthorized: Only Patients can access this page.');
      return redirect()->back();
    }

    $this->loadFacilityInfo();
  }

  private function loadFacilityInfo()
  {
    $firstRecord = Delivery::with('facility')
      ->where('patient_id', $this->user->id)
      ->latest('dodel')
      ->first();

    if ($firstRecord) {
      $this->facility_name = $firstRecord->facility->name ?? 'N/A';
      $this->registration_facility_name = $this->facility_name;
    }
  }

  public function viewRecord($id)
  {
    try {
      $this->selected_record = Delivery::with(['patient', 'facility', 'state', 'lga', 'ward'])
        ->where('id', $id)
        ->where('patient_id', $this->user->id)
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
    $deliveries = Delivery::with(['patient', 'facility', 'state', 'lga', 'ward'])
      ->where('patient_id', $this->user->id)
      ->latest('dodel')
      ->paginate(10);



    return view('livewire.patient.patient-deliveries', [
      'deliveries' => $deliveries
    ])->layout('layouts.patientLayout');
  }
}
