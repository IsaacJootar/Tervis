<?php

namespace App\Livewire\Workspaces;

use App\Models\Patient;
use App\Models\Facility;
use App\Models\Registrations\DinActivation;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class WorkspaceDashboard extends Component
{
  // ============================================
  // PATIENT CONTEXT (from route parameter)
  // ============================================
  public $patientId;
  public $patient;

  // ============================================
  // PATIENT INFO
  // ============================================
  public $patient_din;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_phone;
  public $patient_dob;
  public $patient_age;
  public $patient_gender;
  public $patient_blood_group;
  public $patient_genotype;
  public $patient_nhis_status;

  // ============================================
  // FACILITY AND OFFICER INFO
  // ============================================
  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;

  // ============================================
  // ACCESS CONTROL
  // ============================================
  public $hasAccess = false;
  public $accessError = '';
  public $activation_time;

  // ============================================
  // REGISTRATION STATUS
  // ============================================
  public $hasAntenatalRegistration = false;
  public $hasFamilyPlanningRegistration = false;
  public $antenatalRegistrationId = null;
  public $familyPlanningRegistrationId = null;

  // ============================================
  // LINKED CHILDREN
  // ============================================
  public $linkedChildren = [];
  public $hasLinkedChildren = false;

  // ============================================
  // WORKSPACE CARD STATUS (has activities)
  // ============================================
  public $cardStatus = [];

  // ============================================
  // MOUNT
  // ============================================
  public function mount($patientId)
  {
    $this->patientId = $patientId;

    // Load officer and facility info
    $user = Auth::user();
    $this->facility_id = $user->facility_id;
    $this->officer_name = $user->full_name ?? ($user->first_name . ' ' . $user->last_name);

    if ($this->facility_id) {
      $facility = Facility::with(['stateRelation', 'lgaRelation'])->find($this->facility_id);

      $this->facility_name = $facility?->name ?? 'Unknown Facility';
      $this->facility_state = $facility?->stateRelation?->name ?? 'N/A';
      $this->facility_lga = $facility?->lgaRelation?->name ?? 'N/A';
      $this->facility_ward = $facility?->ward ?? 'N/A';
    }

    // Validate patient access
    $this->validatePatientAccess();

    // Load patient data if access granted
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->loadRegistrationStatus();
      $this->loadLinkedChildren();
      $this->loadCardStatus();
    }
  }

  // ============================================
  // VALIDATE PATIENT ACCESS
  // ============================================
  private function validatePatientAccess()
  {
    // 1. Check if patient exists (only load relationships that definitely exist)
    $patient = Patient::with(['facility'])->find($this->patientId);

    if (!$patient) {
      $this->hasAccess = false;
      $this->accessError = 'Patient not found in the system.';
      return;
    }

    // 2. Check if patient is activated for today at this facility
    $todaysActivation = DinActivation::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->first();

    if (!$todaysActivation) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has not been checked in today. Please activate DIN first.';
      return;
    }

    // All checks passed
    $this->hasAccess = true;
    $this->patient = $patient;
    $this->activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
  }

  // ============================================
  // LOAD PATIENT DATA
  // ============================================
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
    $this->patient_blood_group = $this->patient->blood_group;
    $this->patient_genotype = $this->patient->genotype;
    $this->patient_nhis_status = $this->patient->nhis_status;
  }

  // ============================================
  // LOAD REGISTRATION STATUS (Safe - handles missing tables)
  // ============================================
  private function loadRegistrationStatus()
  {
    if (!$this->patient) return;

    // Antenatal Registration - safely check
    try {
      $antenatalReg = $this->patient->activeAntenatalRegistration;
      $this->hasAntenatalRegistration = !is_null($antenatalReg);
      $this->antenatalRegistrationId = $antenatalReg?->id;
    } catch (\Exception $e) {
      $this->hasAntenatalRegistration = false;
      $this->antenatalRegistrationId = null;
    }

    // Family Planning Registration - safely check
    try {
      $fpReg = $this->patient->familyPlanningRegistration;
      $this->hasFamilyPlanningRegistration = !is_null($fpReg);
      $this->familyPlanningRegistrationId = $fpReg?->id;
    } catch (\Exception $e) {
      $this->hasFamilyPlanningRegistration = false;
      $this->familyPlanningRegistrationId = null;
    }
  }

  // ============================================
  // LOAD LINKED CHILDREN (Safe - handles missing tables)
  // ============================================
  private function loadLinkedChildren()
  {
    if (!$this->patient) return;

    try {
      $this->linkedChildren = $this->patient->children ?? collect();
      $this->hasLinkedChildren = $this->linkedChildren->count() > 0;
    } catch (\Exception $e) {
      $this->linkedChildren = collect();
      $this->hasLinkedChildren = false;
    }
  }

  // ============================================
  // LOAD CARD STATUS (Check if patient has activities)
  // ============================================
  private function loadCardStatus()
  {
    // Card 1: Attendance - Always enabled (they are here today)
    $this->cardStatus['attendance'] = [
      'enabled' => true,
      'count' => DinActivation::where('patient_id', $this->patientId)
        ->where('facility_id', $this->facility_id)
        ->count(),
      'label' => 'Total Visits'
    ];

    // Card 2: Doctor Assessments
    $assessmentCount = $this->getModelCount('App\Models\DoctorAssessment');
    $this->cardStatus['assessments'] = [
      'enabled' => true,
      'count' => $assessmentCount,
      'label' => 'Assessments'
    ];

    // Card 3: ANC Overview (Antenatal activities hub)
    $ttCount = $this->getModelCount('App\Models\TetanusVaccination');
    $deliveryCount = $this->getModelCount('App\Models\Delivery');
    $postnatalCount = $this->getModelCount('App\Models\PostnatalRecord');
    $followUpCount = $this->getModelCount('App\Models\AntenatalFollowUpAssessment');
    $this->cardStatus['anc'] = [
      'enabled' => $this->hasAntenatalRegistration,
      'count' => $ttCount + $deliveryCount + $postnatalCount + $followUpCount,
      'label' => 'Total ANC Visits',
      'requires' => 'ANC Registration'
    ];

    // Card 6: Immunizations (Child Health)
    $immunizationCount = $this->getModelCount('App\Models\Immunization');
    $this->cardStatus['immunizations'] = [
      'enabled' => $this->hasLinkedChildren,
      'count' => $immunizationCount,
      'label' => 'Immunizations',
      'requires' => 'Linked Children'
    ];

    // Card 7: Nutrition (Child Health)
    $nutritionCount = $this->getModelCount('App\Models\NutritionAssessment');
    $this->cardStatus['nutrition'] = [
      'enabled' => $this->hasLinkedChildren,
      'count' => $nutritionCount,
      'label' => 'Assessments',
      'requires' => 'Linked Children'
    ];

    // Card 8: Tests & Laboratory
    $labCount = $this->getModelCount('App\Models\LabTest');
    $this->cardStatus['laboratory'] = [
      'enabled' => true,
      'count' => $labCount,
      'label' => 'Tests'
    ];

    // Card 9: Prescriptions & Drugs
    $prescriptionCount = $this->getModelCount('App\Models\Prescription');
    $this->cardStatus['prescriptions'] = [
      'enabled' => true,
      'count' => $prescriptionCount,
      'label' => 'Prescriptions'
    ];

    // Card 10: Invoices & Payments
    $invoiceCount = $this->getModelCount('App\Models\Invoice');
    $this->cardStatus['invoices'] = [
      'enabled' => true,
      'count' => $invoiceCount,
      'label' => 'Invoices'
    ];

    // Card 11: Appointments
    $appointmentCount = $this->getModelCount('App\Models\Appointment');
    $this->cardStatus['appointments'] = [
      'enabled' => true,
      'count' => $appointmentCount,
      'label' => 'Appointments'
    ];

    // Card 12: Referrals
    $referralCount = $this->getModelCount('App\Models\Referral');
    $this->cardStatus['referrals'] = [
      'enabled' => true,
      'count' => $referralCount,
      'label' => 'Referrals'
    ];

    // Card 13: Reminders & Alerts
    $reminderCount = $this->getModelCount('App\Models\Reminder');
    $this->cardStatus['reminders'] = [
      'enabled' => true,
      'count' => $reminderCount,
      'label' => 'Reminders'
    ];

    // Card 14: Family Planning
    $fpCount = $this->getModelCount('App\Models\FamilyPlanningVisit');
    $this->cardStatus['family_planning'] = [
      'enabled' => $this->hasFamilyPlanningRegistration,
      'count' => $fpCount,
      'label' => 'FP Visits',
      'requires' => 'FP Registration'
    ];

    // Card 15: Visits
    $visitCount = $this->getModelCount('App\Models\Visit');
    $this->cardStatus['visits'] = [
      'enabled' => true,
      'count' => $visitCount,
      'label' => 'Visits'
    ];

    // Card 16: Activities
    $activityCount = $this->getModelCount('App\Models\Activity');
    $this->cardStatus['activities'] = [
      'enabled' => true,
      'count' => $activityCount,
      'label' => 'Activities'
    ];
  }

  // ============================================
  // HELPER: Get Model Count (Safe - won't crash if model/table doesn't exist)
  // ============================================
  private function getModelCount($modelClass)
  {
    if (!class_exists($modelClass)) {
      return 0;
    }

    try {
      return $modelClass::where('patient_id', $this->patientId)
        ->where('facility_id', $this->facility_id)
        ->count();
    } catch (\Exception $e) {
      return 0;
    }
  }

  // ============================================
  // NAVIGATE TO WORKSPACE
  // ============================================
  public function navigateToWorkspace($workspace)
  {
    $routes = [
      'attendance' => 'workspaces-attendance',
      'assessments' => 'workspaces-assessments',
      'anc' => 'workspaces-antenatal',
      'immunizations' => 'workspaces-child-health-immunizations',
      'nutrition' => 'workspaces-child-health-nutrition',
      'laboratory' => 'workspaces-laboratory',
      'prescriptions' => 'workspaces-prescriptions',
      'invoices' => 'workspaces-invoices',
      'appointments' => 'workspaces-appointments',
      'referrals' => 'workspaces-referrals',
      'reminders' => 'workspaces-reminders',
      'family_planning' => 'workspaces-family-planning',
      'visits' => 'workspaces-visits',
      'activities' => 'workspaces-activities',
    ];

    if (isset($routes[$workspace]) && ($this->cardStatus[$workspace]['enabled'] ?? false)) {
      return redirect()->route($routes[$workspace], ['patientId' => $this->patientId]);
    }

    toastr()->warning('This workspace is not available for this patient.');
  }

  // ============================================
  // BACK TO PATIENT WORKSPACE (Gate)
  // ============================================
  public function backToPatientWorkspace()
  {
    return redirect()->route('patient-workspace');
  }

  // ============================================
  // PLACEHOLDER FOR LAZY LOADING
  // ============================================
  public function placeholder()
  {
    return view('placeholder');
  }

  // ============================================
  // RENDER
  // ============================================
  public function render()
  {
    return view('livewire.workspaces.workspace-dashboard', [
      'facility_name' => $this->facility_name,
      'facility_state' => $this->facility_state,
      'facility_lga' => $this->facility_lga,
      'facility_ward' => $this->facility_ward,
      'hasAccess' => $this->hasAccess,
      'accessError' => $this->accessError,
      'cardStatus' => $this->cardStatus,
    ]);
  }
}
