<?php

namespace App\Livewire\Core;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientAppointments extends Component
{
  public $facility_id;
  public $modal_flag = false;
  public $due_soon_patients = [];

  public function mount()
  {
    $admin = Auth::user();

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
    $now = Carbon::now();

    foreach ($patients as $patient) {
      foreach ($patient->appointments as $appointment) {
        if ($appointment['status'] === 'Upcoming') {
          $daysUntil = abs($appointment['days_until']);

          if ($daysUntil <= 3) { // 3 days is due enough
            $dueSoonPatients[] = [
              'id' => $patient->id,
              'name' => $patient->first_name . ' ' . $patient->last_name,
              'phone' => $patient->phone,
              'din' => $patient->DIN,
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
    // Reuse the same logic from render() method
    return User::select([
      'users.id',
      'users.DIN',
      'users.first_name',
      'users.last_name',
      'users.phone',
      'users.email',
      'users.created_at'
    ])
      ->where('users.role', 'Patient')
      ->whereExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('daily_attendances')
          ->whereColumn('daily_attendances.user_id', 'users.id')
          ->where('daily_attendances.facility_id', $this->facility_id);
      })
      ->with([
        'antenatal',
        'tetanusVaccinations',
        'dailyAttendances'
      ])
      ->get()
      ->map(function ($patient) {
        $appointments = [];
        $now = Carbon::now();

        // Same appointment logic as render() method
        if ($patient->antenatal && $patient->antenatal->follow_up_next_visit) {
          $appointmentDate = Carbon::parse($patient->antenatal->follow_up_next_visit);
          $fulfilled = $this->checkAppointmentFulfilled($patient->id, $appointmentDate, ['antenatal', 'daily_attendance']);

          $appointments[] = [
            'type' => 'Antenatal Follow-up',
            'date' => $appointmentDate,
            'status' => $this->getAppointmentStatus($appointmentDate, $fulfilled),
            'days_until' => round($appointmentDate->diffInDays($now, false)),
            'fulfilled' => $fulfilled,
            'color_class' => $this->getStatusColorClass($appointmentDate, $fulfilled)
          ];
        }

        $latestTetanus = $patient->tetanusVaccinations->where('next_appointment_date', '!=', null)->sortByDesc('next_appointment_date')->first();
        if ($latestTetanus && $latestTetanus->next_appointment_date) {
          $appointmentDate = Carbon::parse($latestTetanus->next_appointment_date);
          $fulfilled = $this->checkAppointmentFulfilled($patient->id, $appointmentDate, ['tetanus', 'daily_attendance']);

          $appointments[] = [
            'type' => 'TT Vaccination',
            'date' => $appointmentDate,
            'status' => $this->getAppointmentStatus($appointmentDate, $fulfilled),
            'days_until' => round($appointmentDate->diffInDays($now, false)),
            'fulfilled' => $fulfilled,
            'color_class' => $this->getStatusColorClass($appointmentDate, $fulfilled)
          ];
        }

        $patient->appointments = collect($appointments);
        return $patient;
      })
      ->filter(function ($patient) {
        return $patient->appointments->count() > 0;
      });
  }

  public function render()
  {
    // Get all patients who have visited this facility
    $patients = User::select([
      'users.id',
      'users.DIN',
      'users.first_name',
      'users.last_name',
      'users.phone',
      'users.email',
      'users.created_at'
    ])
      ->where('users.role', 'Patient')
      ->whereExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('daily_attendances')
          ->whereColumn('daily_attendances.user_id', 'users.id')
          ->where('daily_attendances.facility_id', $this->facility_id);
      })
      ->with([
        'antenatal',
        'tetanusVaccinations',
        'dailyAttendances'
      ])
      ->get()
      ->map(function ($patient) {
        $appointments = [];
        $now = Carbon::now();

        // Check antenatal follow-up appointments
        if ($patient->antenatal && $patient->antenatal->follow_up_next_visit) {
          $appointmentDate = Carbon::parse($patient->antenatal->follow_up_next_visit);
          $fulfilled = $this->checkAppointmentFulfilled($patient->id, $appointmentDate, ['antenatal', 'daily_attendance']);

          $appointments[] = [
            'type' => 'Antenatal Follow-up',
            'date' => $appointmentDate,
            'status' => $this->getAppointmentStatus($appointmentDate, $fulfilled),
            'days_until' => round($appointmentDate->diffInDays($now, false)), // rounded to whole days
            'fulfilled' => $fulfilled,
            'color_class' => $this->getStatusColorClass($appointmentDate, $fulfilled)
          ];
        }

        // Check tetanus vaccination next appointments
        $latestTetanus = $patient->tetanusVaccinations->where('next_appointment_date', '!=', null)->sortByDesc('next_appointment_date')->first();
        if ($latestTetanus && $latestTetanus->next_appointment_date) {
          $appointmentDate = Carbon::parse($latestTetanus->next_appointment_date);
          $fulfilled = $this->checkAppointmentFulfilled($patient->id, $appointmentDate, ['tetanus', 'daily_attendance']);

          $appointments[] = [
            'type' => 'TT Vaccination',
            'date' => $appointmentDate,
            'status' => $this->getAppointmentStatus($appointmentDate, $fulfilled),
            'days_until' => round($appointmentDate->diffInDays($now, false)), // rounded to whole days
            'fulfilled' => $fulfilled,
            'color_class' => $this->getStatusColorClass($appointmentDate, $fulfilled)
          ];
        }

        // Check postnatal follow-up (removed for now), maybe will add later if need be - Postnatal appointments will be added later

        // Sort appointments by date (soonest first)
        usort($appointments, function ($a, $b) {
          return $a['date']->timestamp <=> $b['date']->timestamp;
        });

        $patient->appointments = collect($appointments);
        $patient->next_appointment = $appointments ? $appointments[0] : null;
        $patient->total_appointments = count($appointments);
        $patient->missed_appointments = collect($appointments)->where('status', 'Missed')->count();
        $patient->upcoming_appointments = collect($appointments)->where('status', 'Upcoming')->count();

        return $patient;
      })
      ->filter(function ($patient) {
        // Only show patients who have appointments
        return $patient->total_appointments > 0;
      })
      ->sortBy(function ($patient) {
        // Sort by next appointment date (soonest first)
        return $patient->next_appointment ? $patient->next_appointment['date']->timestamp : PHP_INT_MAX;
      });

    return view('livewire.core.patient-appointments', [
      'patients' => $patients,
    ])->layout('layouts.facilityAdminLayout');
  }

  private function checkAppointmentFulfilled($patientId, $appointmentDate, $recordTypes)
  {
    // Check if patient attended after the appointment date
    $attendanceAfter = DB::table('daily_attendances')
      ->where('user_id', $patientId)
      ->where('visit_date', '>=', $appointmentDate->toDateString())
      ->exists();

    if ($attendanceAfter) {
      return true;
    }

    // Check specific record types for fulfillment
    foreach ($recordTypes as $type) {
      switch ($type) {
        case 'antenatal':
          $fulfilled = DB::table('antenatals')
            ->where('user_id', $patientId)
            ->where('created_at', '>=', $appointmentDate)
            ->exists();
          break;

        case 'tetanus':
          $fulfilled = DB::table('tetanus_vaccinations')
            ->where('user_id', $patientId)
            ->where('dose_date', '>=', $appointmentDate->toDateString())
            ->exists();
          break;

        case 'postnatal': //visit date would do for now
          $fulfilled = DB::table('postnatal_records')
            ->where('user_id', $patientId)
            ->where('visit_date', '>=', $appointmentDate->toDateString())
            ->exists();
          break;

        case 'delivery': // dodel is not very aligned to how i want the apointments dates.Will be back maybe
          $fulfilled = DB::table('deliveries')
            ->where('user_id', $patientId)
            ->where('dodel', '>=', $appointmentDate->toDateString())
            ->exists();
          break;
      }

      if (isset($fulfilled) && $fulfilled) {
        return true;
      }
    }

    return false;
  }

  private function getAppointmentStatus($appointmentDate, $fulfilled)
  {
    $now = Carbon::now();

    if ($fulfilled) {
      return 'Fulfilled';
    }

    if ($appointmentDate->isFuture()) {
      return 'Upcoming';
    } else {
      return 'Missed';
    }
  }

  private function getStatusColorClass($appointmentDate, $fulfilled)
  {
    $now = Carbon::now();

    if ($fulfilled) {
      return 'bg-label-success';
    }

    if ($appointmentDate->isFuture()) {
      // Upcoming - different colors based on urgency
      $daysUntil = $now->diffInDays($appointmentDate);
      if ($daysUntil <= 3) {
        return 'bg-label-warning'; // Due soon
      } else {
        return 'bg-label-info'; // Upcoming
      }
    } else {
      return 'bg-label-danger'; // Missed
    }
  }
}
