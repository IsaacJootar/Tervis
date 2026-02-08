<?php

namespace App\Livewire\Workspaces\Modules\ANC;

use Exception;
use Carbon\Carbon;
use App\Models\Lga;
use App\Models\Ward;
use App\Models\State;
use App\Models\Facility;
use App\Models\Patient;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Registrations\DinActivation;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class AncOverview extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $hasAccess = false, $accessError = '', $activation_time;
  public $lmp, $edd, $pregnancy_number;

  public function mount($patientId)
  {
    $this->patientId = $patientId;
    $user = Auth::user();
    if (!$user || $user->role !== 'Data Officer') {
      abort(403, 'Unauthorized: Only Data Officers can access this page.');
    }

    $facility = Facility::find($user->facility_id);
    if (!$facility) {
      abort(403, 'Invalid facility assignment.');
    }

    $this->facility_id = $facility->id;
    $this->facility_name = $facility->name;
    $this->state_id = State::where('name', $facility->state)->first()->id ?? null;
    $this->state_name = $facility->state;
    $this->lga_id = Lga::where('name', $facility->lga)->where('state_id', $this->state_id)->first()->id ?? null;
    $this->lga_name = $facility->lga;
    $this->ward_id = Ward::where('name', $facility->ward)->where('lga_id', $this->lga_id)->first()->id ?? null;
    $this->ward_name = $facility->ward;

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
    }
  }

  private function validatePatientAccess()
  {
    $patient = Patient::with(['facility', 'activeAntenatalRegistration'])->find($this->patientId);

    if (!$patient) {
      $this->hasAccess = false;
      $this->accessError = 'Patient not found in the system.';
      return;
    }

    $todaysActivation = DinActivation::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->first();

    if (!$todaysActivation) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has not been checked in today. Please activate DIN first.';
      return;
    }

    if (!$patient->activeAntenatalRegistration) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has no active pregnancy. Please register for ANC first.';
      return;
    }

    $this->hasAccess = true;
    $this->patient = $patient;
    $this->activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
  }

  private function loadPatientData()
  {
    if (!$this->patient) return;

    $this->patient_din = $this->patient->din;
    $this->first_name = $this->patient->first_name;
    $this->last_name = $this->patient->last_name;
    $this->middle_name = $this->patient->middle_name;
    $this->patient_phone = $this->patient->phone;
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;

    $activeAnc = $this->patient->activeAntenatalRegistration;
    if ($activeAnc) {
      $this->lmp = $activeAnc->lmp?->format('Y-m-d');
      $this->edd = $activeAnc->edd?->format('Y-m-d');
      $this->pregnancy_number = $activeAnc->pregnancy_number;
    }
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    return view('livewire.workspaces.modules.anc.index')->layout('layouts.dataOfficerLayout');
  }
}
