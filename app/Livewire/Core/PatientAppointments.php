<?php

namespace App\Livewire\Core;

use App\Models\AntenatalFollowUpAssessment;
use App\Models\DoctorAssessment;
use App\Models\FamilyPlanningFollowUp;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\TetanusVaccination;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PatientAppointments extends Component
{
  public $facility_id;
  public $modal_flag = false;
  public $due_soon_patients = [];

  public function mount()
  {
    $admin = Auth::user();
    if (!$admin) {
      abort(403, 'Unauthorized');
    }

    $this->facility_id = $admin->facility_id;
  }

  public function openDueSoonModal()
  {
    $this->due_soon_patients = $this->getDueSoonPatients();
    $this->modal_flag = true;
    $this->dispatch('open-due-soon-modal');
  }

  public function sendNotifications()
  {
    // Future SMS API integration will go here
    $count = count($this->due_soon_patients);

    // For now, just show success message
    toastr()->info("Ready to send {$count} SMS notifications.");

    // Future implementation: will comeback later
    // foreach ($this->due_soon_patients as $patient) {
    //     // Call SMS API with $patient['phone'] and appointment details
    //     // $this->sendSMS($patient['phone'], $patient['message']);
    // }

    $this->closeModal();
  }

  public function closeModal()
  {
    $this->modal_flag = false;
    $this->due_soon_patients = [];
    $this->dispatch('close-due-soon-modal');
  }

  private function getDueSoonPatients()
  {
    $patients = $this->getAllPatientsWithAppointments();
    $dueSoonPatients = [];

    foreach ($patients as $patient) {
      foreach ($patient->appointments as $appointment) {
        if ($appointment['status'] === 'Upcoming') {
          $daysUntil = abs($appointment['days_until']);

          if ($daysUntil <= 3) { // 3 days is due enough
            $dueSoonPatients[] = [
              'id' => $patient->id,
              'name' => $patient->first_name . ' ' . $patient->last_name,
              'phone' => $patient->phone,
              'din' => $patient->din,
              'appointment_type' => $appointment['type'],
              'appointment_date' => $appointment['date']->format('M d, Y'),
              'days_until' => $daysUntil,
              'urgency_level' => $daysUntil === 0 ? 'today' : ($daysUntil === 1 ? 'tomorrow' : "{$daysUntil} days"),
              'message' => $this->generateSMSMessage($patient, $appointment)
            ];
          }
        }
      }
    }

    // Sort by urgency (soonest first)
    usort($dueSoonPatients, function ($a, $b) {
      return $a['days_until'] <=> $b['days_until'];
    });

    return $dueSoonPatients;
  }

  private function generateSMSMessage($patient, $appointment)
  {
    $facilityName = "Your Healthcare Facility"; // You can get this from the facility relationship
    $appointmentDate = $appointment['date']->format('M d, Y');
    $appointmentType = $appointment['type'];

    return "Hello {$patient->first_name}, this is a reminder about your {$appointmentType} appointment on {$appointmentDate} at {$facilityName}. Please attend on time. Thank you.";
  }

  private function getAllPatientsWithAppointments()
  {
    return $this->buildPatientsWithAppointments();
  }

  public function render()
  {
    $patients = $this->buildPatientsWithAppointments();

    return view('livewire.core.patient-appointments', [
      'patients' => $patients,
    ])->layout('layouts.facilityAdminLayout');
  }

  private function buildPatientsWithAppointments(): Collection
  {
    if (empty($this->facility_id)) {
      return collect();
    }

    $appointmentRows = $this->collectAppointmentRows();
    if ($appointmentRows->isEmpty()) {
      return collect();
    }

    $patientIds = $appointmentRows->pluck('patient_id')->unique()->values();
    $patients = Patient::query()
      ->whereIn('id', $patientIds)
      ->select([
        'id',
        'din',
        'first_name',
        'last_name',
        'phone',
        'email',
        'created_at',
      ])
      ->get()
      ->keyBy('id');

    $activationDatesByPatient = $this->getActivationDatesByPatient($patientIds);
    $today = Carbon::today();

    return $appointmentRows
      ->groupBy('patient_id')
      ->map(function (Collection $rows, $patientId) use ($patients, $activationDatesByPatient, $today) {
        $patient = $patients->get((int) $patientId);
        if (!$patient) {
          return null;
        }

        $activationDates = $activationDatesByPatient->get((int) $patientId, collect());

        $appointments = $rows
          ->map(function (array $row) use ($activationDates, $today) {
            $appointmentDate = $row['date']->copy()->startOfDay();
            $fulfilled = $this->isAppointmentFulfilled($activationDates, $appointmentDate);
            $status = $this->getAppointmentStatus($appointmentDate, $fulfilled);
            $daysUntil = (int) round($appointmentDate->diffInDays($today, false));

            return [
              'type' => $row['type'],
              'date' => $appointmentDate,
              'status' => $status,
              'days_until' => $daysUntil,
              'fulfilled' => $fulfilled,
              'color_class' => $this->getStatusColorClass($appointmentDate, $fulfilled),
            ];
          })
          ->sortBy(fn(array $appointment) => $appointment['date']->timestamp)
          ->values();

        $patient->appointments = $appointments;
        $patient->next_appointment = $appointments->first();
        $patient->total_appointments = $appointments->count();
        $patient->missed_appointments = $appointments->where('status', 'Missed')->count();
        $patient->upcoming_appointments = $appointments->where('status', 'Upcoming')->count();

        return $patient;
      })
      ->filter(fn($patient) => $patient && $patient->total_appointments > 0)
      ->sortBy(function ($patient) {
        return $patient->next_appointment ? $patient->next_appointment['date']->timestamp : PHP_INT_MAX;
      })
      ->values();
  }

  private function collectAppointmentRows(): Collection
  {
    $rows = collect();

    $doctorAppointments = DoctorAssessment::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->get(['patient_id', 'next_appointment_date']);

    foreach ($doctorAppointments as $record) {
      $rows->push([
        'patient_id' => (int) $record->patient_id,
        'type' => 'Doctor Follow-up',
        'date' => Carbon::parse($record->next_appointment_date)->startOfDay(),
      ]);
    }

    $ttAppointments = TetanusVaccination::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->get(['patient_id', 'next_appointment_date']);

    foreach ($ttAppointments as $record) {
      $rows->push([
        'patient_id' => (int) $record->patient_id,
        'type' => 'TT Vaccination',
        'date' => Carbon::parse($record->next_appointment_date)->startOfDay(),
      ]);
    }

    $ancFollowUpAppointments = AntenatalFollowUpAssessment::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_return_date')
      ->get(['patient_id', 'next_return_date']);

    foreach ($ancFollowUpAppointments as $record) {
      $rows->push([
        'patient_id' => (int) $record->patient_id,
        'type' => 'ANC Follow-up',
        'date' => Carbon::parse($record->next_return_date)->startOfDay(),
      ]);
    }

    $familyPlanningAppointments = FamilyPlanningFollowUp::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->get(['patient_id', 'next_appointment_date']);

    foreach ($familyPlanningAppointments as $record) {
      $rows->push([
        'patient_id' => (int) $record->patient_id,
        'type' => 'Family Planning Follow-up',
        'date' => Carbon::parse($record->next_appointment_date)->startOfDay(),
      ]);
    }

    $familyPlanningRegistrationAppointments = FamilyPlanningRegistration::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment')
      ->get(['patient_id', 'next_appointment']);

    foreach ($familyPlanningRegistrationAppointments as $record) {
      $rows->push([
        'patient_id' => (int) $record->patient_id,
        'type' => 'Family Planning Registration Follow-up',
        'date' => Carbon::parse($record->next_appointment)->startOfDay(),
      ]);
    }

    return $rows->filter(function (array $row) {
      return !empty($row['patient_id']);
    });
  }

  private function getActivationDatesByPatient(Collection $patientIds): Collection
  {
    if ($patientIds->isEmpty()) {
      return collect();
    }

    return DinActivation::query()
      ->where('facility_id', $this->facility_id)
      ->whereIn('patient_id', $patientIds)
      ->whereNotNull('visit_date')
      ->get(['patient_id', 'visit_date'])
      ->groupBy('patient_id')
      ->map(function (Collection $records) {
        return $records
          ->pluck('visit_date')
          ->filter()
          ->map(fn($date) => Carbon::parse($date)->startOfDay())
          ->sortBy(fn(Carbon $date) => $date->timestamp)
          ->values();
      });
  }

  private function isAppointmentFulfilled(Collection $activationDates, Carbon $appointmentDate): bool
  {
    return $activationDates->contains(
      fn(Carbon $visitDate) => $visitDate->greaterThanOrEqualTo($appointmentDate)
    );
  }

  private function getAppointmentStatus($appointmentDate, $fulfilled)
  {
    if ($fulfilled) {
      return 'Fulfilled';
    }

    return $appointmentDate->isFuture() || $appointmentDate->isToday() ? 'Upcoming' : 'Missed';
  }

  private function getStatusColorClass($appointmentDate, $fulfilled)
  {
    if ($fulfilled) {
      return 'bg-label-success';
    }

    if ($appointmentDate->isPast() && !$appointmentDate->isToday()) {
      return 'bg-label-danger';
    }

    $daysUntil = Carbon::today()->diffInDays($appointmentDate, false);
    if ($daysUntil <= 3) {
      return 'bg-label-warning';
    }

    return 'bg-label-info';
  }
}
