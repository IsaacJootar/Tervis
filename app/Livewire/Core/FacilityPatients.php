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

    // Get all patients who have attendance records in this facility
    $patients = Patient::query()
      ->select([
        'patients.id',
        'patients.din',
        'patients.first_name',
        'patients.last_name',
        'patients.phone',
        'patients.email',
        'patients.created_at'
      ])
      ->whereExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('daily_attendances')
          ->whereColumn('daily_attendances.user_id', 'patients.id')
          ->where('daily_attendances.facility_id', $this->facility_id);
      })
      ->with([
        'dailyAttendances',
        'antenatal',
        'tetanusVaccinations',
        'postnatalRecords',
        'deliveries',
        'clinicalNotes'
      ])
      ->get()
      ->map(function ($patient) {
        $lastVisit = DB::table('daily_attendances')
          ->where('user_id', $patient->id)
          ->where('facility_id', $this->facility_id)
          ->orderBy('visit_date', 'desc')
          ->first();

        $patient->attendance_count = $patient->dailyAttendances->count();
        $patient->antenatal_count = $patient->antenatal ? 1 : 0;
        $patient->tetanus_count = $patient->tetanusVaccinations->count();
        $patient->postnatal_count = $patient->postnatalRecords->count();
        $patient->delivery_count = $patient->deliveries->count();
        $patient->clinical_notes_count = $patient->clinicalNotes->count();

        $patient->last_visit_date = $lastVisit ? $lastVisit->visit_date : null;

        $patient->total_visits = $patient->attendance_count +
          $patient->antenatal_count +
          $patient->tetanus_count +
          $patient->postnatal_count +
          $patient->delivery_count +
          $patient->clinical_notes_count;

        return $patient;
      })
      ->sortByDesc('last_visit_date');

    return view('livewire.core.facility-patients', [
      'patients' => $patients,
    ])->layout('layouts.facilityAdminLayout');
  }
}
