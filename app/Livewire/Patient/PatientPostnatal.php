<?php

namespace App\Livewire\Patient;

use Exception;
use App\Models\PostnatalRecord;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class PatientPostnatal extends Component
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
    $firstRecord = PostnatalRecord::with('facility')
      ->where('patient_id', $this->user->id)
      ->latest('visit_date')
      ->first();

    if ($firstRecord) {
      $this->facility_name = $firstRecord->facility ? $firstRecord->facility->name : 'N/A';
      $this->registration_facility_name = $this->facility_name;
    }
  }

  public function viewRecord($id)
  {
    try {
      $this->selected_record = PostnatalRecord::with(['patient', 'facility', 'state', 'lga', 'ward'])
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
    $postnatal_records = PostnatalRecord::with(['patient', 'facility', 'state', 'lga', 'ward'])
      ->where('patient_id', $this->user->id)
      ->latest('visit_date')
      ->paginate(10);


    return view('livewire.patient.patient-postnatal', [
      'postnatal_records' => $postnatal_records
    ])->layout('layouts.patientLayout');
  }
}
