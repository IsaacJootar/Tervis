<?php

namespace App\Livewire\Core;

use App\Models\Patient;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacilityPatients extends Component
{
  public $facility_id;

  public function mount()
  {
    $admin = Auth::user();
    /*
    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }
  */
    $this->facility_id = $admin->facility_id;
  }


  public function render()
  {
    if (empty($this->facility_id)) {
      return view('livewire.core.facility-patients', [
        'patients' => collect(),
      ])->layout('layouts.facilityAdminLayout');
    }

    // Build patient list from facility registrations + facility activations
    // so cross-facility recognized patients still appear when they visit this facility.
    $registeredPatientIds = Patient::query()
      ->where('facility_id', $this->facility_id)
      ->pluck('id');

    $activatedPatientIds = DB::table('din_activations')
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('patient_id')
      ->distinct()
      ->pluck('patient_id');

    $patientIds = $registeredPatientIds
      ->merge($activatedPatientIds)
      ->filter()
      ->unique()
      ->values();

    if ($patientIds->isEmpty()) {
      return view('livewire.core.facility-patients', [
        'patients' => collect(),
      ])->layout('layouts.facilityAdminLayout');
    }

    $patients = Patient::query()
      ->select([
        'id',
        'din',
        'first_name',
        'last_name',
        'phone',
        'email',
        'created_at',
      ])
      ->whereIn('id', $patientIds)
      ->get();

    $visitStats = DB::table('visits')
      ->select([
        'patient_id',
        DB::raw('COUNT(*) as visit_count'),
        DB::raw('MAX(visit_date) as last_visit_date'),
      ])
      ->where('facility_id', $this->facility_id)
      ->whereIn('patient_id', $patientIds)
      ->groupBy('patient_id')
      ->get()
      ->keyBy('patient_id');

    $activationStats = DB::table('din_activations')
      ->select([
        'patient_id',
        DB::raw('COUNT(*) as attendance_count'),
        DB::raw('MAX(visit_date) as last_visit_date'),
      ])
      ->where('facility_id', $this->facility_id)
      ->whereIn('patient_id', $patientIds)
      ->groupBy('patient_id')
      ->get()
      ->keyBy('patient_id');

    $antenatalCounts = DB::table('antenatal_registrations')
      ->select('patient_id', DB::raw('COUNT(*) as total'))
      ->where('facility_id', $this->facility_id)
      ->whereIn('patient_id', $patientIds)
      ->groupBy('patient_id')
      ->pluck('total', 'patient_id');

    $tetanusCounts = DB::table('tetanus_vaccinations')
      ->select('patient_id', DB::raw('COUNT(*) as total'))
      ->where('facility_id', $this->facility_id)
      ->whereIn('patient_id', $patientIds)
      ->groupBy('patient_id')
      ->pluck('total', 'patient_id');

    $postnatalCounts = DB::table('postnatal_records')
      ->select('patient_id', DB::raw('COUNT(*) as total'))
      ->where('facility_id', $this->facility_id)
      ->whereIn('patient_id', $patientIds)
      ->groupBy('patient_id')
      ->pluck('total', 'patient_id');

    $deliveryCounts = DB::table('deliveries')
      ->select('patient_id', DB::raw('COUNT(*) as total'))
      ->where('facility_id', $this->facility_id)
      ->whereIn('patient_id', $patientIds)
      ->groupBy('patient_id')
      ->pluck('total', 'patient_id');

    // Clinical notes are now primarily captured in doctor_assessments for patient-scoped workflows.
    $clinicalCounts = DB::table('doctor_assessments')
      ->select('patient_id', DB::raw('COUNT(*) as total'))
      ->where('facility_id', $this->facility_id)
      ->whereIn('patient_id', $patientIds)
      ->groupBy('patient_id')
      ->pluck('total', 'patient_id');

    $patients = $patients
      ->map(function ($patient) use (
        $visitStats,
        $activationStats,
        $antenatalCounts,
        $tetanusCounts,
        $postnatalCounts,
        $deliveryCounts,
        $clinicalCounts
      ) {
        $visitSnapshot = $visitStats->get($patient->id);
        $activationSnapshot = $activationStats->get($patient->id);

        $attendanceCount = (int) ($visitSnapshot->visit_count ?? $activationSnapshot->attendance_count ?? 0);
        $lastVisitDate = $visitSnapshot->last_visit_date ?? $activationSnapshot->last_visit_date ?? null;

        $patient->attendance_count = $attendanceCount;
        $patient->antenatal_count = (int) ($antenatalCounts[$patient->id] ?? 0);
        $patient->tetanus_count = (int) ($tetanusCounts[$patient->id] ?? 0);
        $patient->postnatal_count = (int) ($postnatalCounts[$patient->id] ?? 0);
        $patient->delivery_count = (int) ($deliveryCounts[$patient->id] ?? 0);
        $patient->clinical_notes_count = (int) ($clinicalCounts[$patient->id] ?? 0);
        $patient->last_visit_date = $lastVisitDate;

        $patient->total_visits = $patient->attendance_count +
          $patient->antenatal_count +
          $patient->tetanus_count +
          $patient->postnatal_count +
          $patient->delivery_count +
          $patient->clinical_notes_count;

        return $patient;
      })
      ->sortByDesc(function ($patient) {
        return $patient->last_visit_date ?? '0000-00-00';
      })
      ->values();

    return view('livewire.core.facility-patients', [
      'patients' => $patients,
    ])->layout('layouts.facilityAdminLayout');
  }
}
