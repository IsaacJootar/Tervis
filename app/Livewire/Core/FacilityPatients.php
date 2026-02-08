<?php

namespace App\Livewire\Core;

use App\Models\User;
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
    // Get all patients (users with role 'Patient') who have records in this facility
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
        $query->select(DB::raw(1)) // just 11 instace found is enough to pass the exist test. faster
          ->from('daily_attendances')
          ->whereColumn('daily_attendances.user_id', 'users.id')
          ->where('daily_attendances.facility_id', $this->facility_id);
      })
      ->with([
        // Load ALL relationships (not limited to facility) for general counting
        'dailyAttendances', // All attendances across all facilities
        'antenatal', // Single antenatal record (regardless of facility)
        'tetanusVaccinations', // All tetanus shots across all facilities
        'postnatalRecords', // All postnatal records across all facilities
        'deliveries', // All deliveries across all facilities
        'clinicalNotes' // All clinical notes across all facilities
      ])
      ->get()
      ->map(function ($patient) {
        // Get last visit date from daily_attendances for THIS FACILITY only
        $lastVisit = DB::table('daily_attendances')
          ->where('user_id', $patient->id)
          ->where('facility_id', $this->facility_id)
          ->orderBy('visit_date', 'desc')
          ->first();

        // Count ALL records across ALL facilities (general patient stats)
        $patient->attendance_count = $patient->dailyAttendances->count();

        // For antenatal - count if record exists (regardless of facility)
        $patient->antenatal_count = $patient->antenatal ? 1 : 0;

        $patient->tetanus_count = $patient->tetanusVaccinations->count();
        $patient->postnatal_count = $patient->postnatalRecords->count();
        $patient->delivery_count = $patient->deliveries->count();
        $patient->clinical_notes_count = $patient->clinicalNotes->count();

        // Last visit is still facility-specific (when they last visited THIS facility- where admin is logged in)
        $patient->last_visit_date = $lastVisit ? $lastVisit->visit_date : null;

        // Calculate total interactions across ALL facilities
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
