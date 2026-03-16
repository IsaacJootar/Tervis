<?php

namespace App\Livewire\Workspaces\Modules;

use App\Models\AntenatalFollowUpAssessment;
use App\Models\DoctorAssessment;
use App\Models\Facility;
use App\Models\FamilyPlanningFollowUp;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\TetanusVaccination;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class Appointments extends Component
{
  public $patientId;
  public $patient;

  public $patient_din;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_phone;
  public $patient_dob;
  public $patient_age;
  public $patient_gender;

  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;

  public $hasAccess = false;
  public $accessError = '';
  public $activation_time;

  public function mount($patientId)
  {
    $this->patientId = $patientId;

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

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
    }
  }

  private function validatePatientAccess(): void
  {
    $patient = Patient::with(['facility'])->find($this->patientId);

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

    $this->hasAccess = true;
    $this->patient = $patient;
    $this->activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
  }

  private function loadPatientData(): void
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
  }

  private function getActivationDates(): Collection
  {
    return DinActivation::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->pluck('visit_date')
      ->filter()
      ->map(fn($date) => Carbon::parse($date)->startOfDay())
      ->sort()
      ->values();
  }

  private function resolveStatus(Collection $activationDates, Carbon $appointmentDate): string
  {
    $fulfilled = $activationDates->contains(
      fn(Carbon $visitDate) => $visitDate->greaterThanOrEqualTo($appointmentDate)
    );

    if ($fulfilled) {
      return 'Fulfilled';
    }

    return $appointmentDate->isFuture() || $appointmentDate->isToday() ? 'Upcoming' : 'Missed';
  }

  private function buildAppointments(): Collection
  {
    $activationDates = $this->getActivationDates();
    $today = today();
    $rows = collect();

    $doctorAppointments = DoctorAssessment::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->get(['id', 'visit_date', 'next_appointment_date', 'final_diagnosis']);

    foreach ($doctorAppointments as $record) {
      $appointmentDate = Carbon::parse($record->next_appointment_date)->startOfDay();
      $rows->push([
        'appointment_date' => $appointmentDate,
        'appointment_type' => 'Doctor Follow-up',
        'source' => 'Doctor Assessment',
        'source_date' => $record->visit_date,
        'status' => $this->resolveStatus($activationDates, $appointmentDate),
        'days_from_today' => $today->diffInDays($appointmentDate, false),
        'details' => $record->final_diagnosis ?: 'N/A',
      ]);
    }

    $ttAppointments = TetanusVaccination::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->get(['id', 'dose_date', 'next_appointment_date', 'current_tt_dose']);

    foreach ($ttAppointments as $record) {
      $appointmentDate = Carbon::parse($record->next_appointment_date)->startOfDay();
      $rows->push([
        'appointment_date' => $appointmentDate,
        'appointment_type' => 'TT Vaccination',
        'source' => 'ANC Tetanus',
        'source_date' => $record->dose_date,
        'status' => $this->resolveStatus($activationDates, $appointmentDate),
        'days_from_today' => $today->diffInDays($appointmentDate, false),
        'details' => $record->current_tt_dose ?: 'N/A',
      ]);
    }

    $followUpAppointments = AntenatalFollowUpAssessment::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_return_date')
      ->get(['id', 'visit_date', 'next_return_date', 'clinical_remarks']);

    foreach ($followUpAppointments as $record) {
      $appointmentDate = Carbon::parse($record->next_return_date)->startOfDay();
      $rows->push([
        'appointment_date' => $appointmentDate,
        'appointment_type' => 'ANC Follow-up',
        'source' => 'ANC Follow-up Assessment',
        'source_date' => $record->visit_date,
        'status' => $this->resolveStatus($activationDates, $appointmentDate),
        'days_from_today' => $today->diffInDays($appointmentDate, false),
        'details' => $record->clinical_remarks ?: 'N/A',
      ]);
    }

    $familyPlanningAppointments = FamilyPlanningFollowUp::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->get(['id', 'visit_date', 'next_appointment_date', 'method_supplied']);

    foreach ($familyPlanningAppointments as $record) {
      $appointmentDate = Carbon::parse($record->next_appointment_date)->startOfDay();
      $rows->push([
        'appointment_date' => $appointmentDate,
        'appointment_type' => 'Family Planning Follow-up',
        'source' => 'Family Planning Follow-up',
        'source_date' => $record->visit_date,
        'status' => $this->resolveStatus($activationDates, $appointmentDate),
        'days_from_today' => $today->diffInDays($appointmentDate, false),
        'details' => $record->method_supplied ?: 'N/A',
      ]);
    }

    $familyPlanningRegistrationAppointments = FamilyPlanningRegistration::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment')
      ->get(['id', 'registration_date', 'next_appointment', 'contraceptive_selected']);

    foreach ($familyPlanningRegistrationAppointments as $record) {
      $appointmentDate = Carbon::parse($record->next_appointment)->startOfDay();
      $rows->push([
        'appointment_date' => $appointmentDate,
        'appointment_type' => 'Family Planning Registration Follow-up',
        'source' => 'Family Planning Registration',
        'source_date' => $record->registration_date,
        'status' => $this->resolveStatus($activationDates, $appointmentDate),
        'days_from_today' => $today->diffInDays($appointmentDate, false),
        'details' => $record->contraceptive_selected ?: 'N/A',
      ]);
    }

    return $rows
      ->sortBy('appointment_date')
      ->values();
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function placeholder()
  {
    return view('placeholder');
  }

  public function render()
  {
    $appointments = $this->buildAppointments();

    $summary = [
      'total' => $appointments->count(),
      'upcoming' => $appointments->where('status', 'Upcoming')->count(),
      'missed' => $appointments->where('status', 'Missed')->count(),
      'fulfilled' => $appointments->where('status', 'Fulfilled')->count(),
    ];

    return view('livewire.workspaces.modules.appointments.index', [
      'appointments' => $appointments,
      'summary' => $summary,
    ]);
  }
}
