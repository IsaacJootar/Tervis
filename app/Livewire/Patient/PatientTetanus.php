<?php

namespace App\Livewire\Patient;

use Exception;
use App\Models\TetanusVaccination;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class PatientTetanus extends Component
{
  use WithPagination;

  protected $paginationTheme = 'bootstrap';

  public $user;
  public $selected_record;
  public $modal_flag = false;
  public $facility_name;
  public $registration_facility_name;
  public $protection_status;
  public $doses_completed;
  public $next_due_dose;

  public function mount()
  {
    $this->user = Auth::user();


    if (!$this->user || $this->user->role !== 'Patient') {
      toastr()->error('Unauthorized: Only Patients can access this page.');
      return redirect()->back();
    }

    $this->loadFacilityInfo();
    $this->calculateProtectionStatus();
  }

  private function loadFacilityInfo()
  {
    $firstRecord = TetanusVaccination::with('facility')
      ->where('patient_id', $this->user->id)
      ->latest('visit_date')
      ->first();

    if ($firstRecord) {
      $this->facility_name = $firstRecord->facility ? $firstRecord->facility->name : 'N/A';
      $this->registration_facility_name = $this->facility_name;
    }
  }

  private function calculateProtectionStatus()
  {
    $doses = TetanusVaccination::where('patient_id', $this->user->id)->count();
    $this->doses_completed = $doses;

    switch ($this->doses_completed) {
      case 0:
        $this->protection_status = [
          'status' => 'Not Protected',
          'percentage' => 0,
          'color' => 'danger',
          'description' => 'No tetanus vaccination received'
        ];
        $this->next_due_dose = 'TT1';
        break;
      case 1:
        $this->protection_status = [
          'status' => 'Not Protected',
          'percentage' => 20,
          'color' => 'danger',
          'description' => 'First dose completed, minimal protection'
        ];
        $this->next_due_dose = 'TT2';
        break;
      case 2:
        $this->protection_status = [
          'status' => 'Partially Protected',
          'percentage' => 40,
          'color' => 'warning',
          'description' => 'Partial protection against tetanus'
        ];
        $this->next_due_dose = 'TT3';
        break;
      case 3:
        $this->protection_status = [
          'status' => 'Protected',
          'percentage' => 60,
          'color' => 'info',
          'description' => 'Good protection for 5 years'
        ];
        $this->next_due_dose = 'TT4';
        break;
      case 4:
        $this->protection_status = [
          'status' => 'Protected',
          'percentage' => 80,
          'color' => 'info',
          'description' => 'Good protection for 10 years'
        ];
        $this->next_due_dose = 'TT5';
        break;
      case 5:
      default:
        $this->protection_status = [
          'status' => 'Fully Protected',
          'percentage' => 100,
          'color' => 'success',
          'description' => 'Complete protection for life'
        ];
        $this->next_due_dose = 'Complete';
        break;
    }
  }

  public function viewRecord($id)
  {
    try {
      $this->selected_record = TetanusVaccination::with(['patient', 'facility'])
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
    $tetanus_records = TetanusVaccination::with(['patient', 'facility'])
      ->where('patient_id', $this->user->id)
      ->latest('visit_date')
      ->paginate(10);


    return view('livewire.patient.patient-tetanus', [
      'tetanus_records' => $tetanus_records
    ])->layout('layouts.patientLayout');
  }
}
