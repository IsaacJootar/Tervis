<?php

namespace App\Livewire\Registers;


use App\Models\Antenatal;
use App\Models\Delivery;
use App\Models\PostnatalRecord;
use App\Models\TetanusVaccination;
use App\Models\DailyAttendance;
use App\Models\Registrations\AntenatalRegistration;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DataOfficerDashboard extends Component
{
  public $antenatal_count;
  public $delivery_count;
  public $postnatal_count;
  public $tetanus_count;
  public $attendance_count;
  public $active_patients;
  public $recent_activity_count;
  public $total_patients;
  public $recent_activities;

  public function mount()
  {
    $user = Auth::user();
    if (!$user || $user->role !== 'Data Officer') {
      abort(403, 'Unauthorized: Only Data Officers can access this page.');
    }

    // Fetch counts for statistics cards and hero card- limit to officer's tenant.
    $this->antenatal_count = Cache::remember('data_officer_antenatal_count', 300, function () use ($user) {
      return AntenatalRegistration::where('facility_id', $user->facility_id)->count();
    });

    $this->delivery_count = Cache::remember('data_officer_delivery_count', 300, function () use ($user) {
      return Delivery::where('facility_id', $user->facility_id)->count();
    });

    $this->postnatal_count = Cache::remember('data_officer_postnatal_count', 300, function () use ($user) {
      return PostnatalRecord::where('facility_id', $user->facility_id)->count();
    });

    $this->tetanus_count = Cache::remember('data_officer_tetanus_count', 300, function () use ($user) {
      return TetanusVaccination::where('facility_id', $user->facility_id)->count();
    });

    $this->attendance_count = Cache::remember('data_officer_attendance_count', 300, function () use ($user) {
      return DailyAttendance::where('facility_id', $user->facility_id)->count();
    });

    $this->active_patients = Cache::remember('data_officer_active_patients', 300, function () use ($user) {
      $antenatal_users = AntenatalRegistration::where('facility_id', $user->facility_id)->pluck('patient_id');
      $delivery_users = Delivery::where('facility_id', $user->facility_id)->pluck('user_id');
      $postnatal_users = PostnatalRecord::where('facility_id', $user->facility_id)->pluck('user_id');
      $tetanus_users = TetanusVaccination::where('facility_id', $user->facility_id)->pluck('patient_id');
      $attendance_users = DailyAttendance::where('facility_id', $user->facility_id)->pluck('user_id');
      return $antenatal_users->merge($delivery_users)->merge($postnatal_users)->merge($tetanus_users)->merge($attendance_users)->unique()->count();
    });

    $this->total_patients = Cache::remember('data_officer_total_patients', 300, function () use ($user) {
      return User::where('facility_id', $user->facility_id)->where('role', 'Patient')->count();
    });

    $this->recent_activity_count = Cache::remember('data_officer_recent_activity_count', 300, function () use ($user) {
      $antenatal_count = AntenatalRegistration::where('facility_id', $user->facility_id)
        ->where('date_of_booking', '>=', now()->subDays(30))
        ->count();
      $delivery_count = Delivery::where('facility_id', $user->facility_id)
        ->where('dodel', '>=', now()->subDays(30))
        ->count();
      $postnatal_count = PostnatalRecord::where('facility_id', $user->facility_id)
        ->where('visit_date', '>=', now()->subDays(30))
        ->count();
      $tetanus_count = TetanusVaccination::where('facility_id', $user->facility_id)
        ->where('visit_date', '>=', now()->subDays(30))
        ->count();
      $attendance_count = DailyAttendance::where('facility_id', $user->facility_id)
        ->where('visit_date', '>=', now()->subDays(30))
        ->count();
      return $antenatal_count + $delivery_count + $postnatal_count + $tetanus_count + $attendance_count;
    });

    // Fetch recent activities
    $this->recent_activities = Cache::remember('data_officer_recent_activities', 300, function () use ($user) {
      $activities = [];

      // Antenatal records
      $antenatal_records = AntenatalRegistration::with(relations: 'patient')
        ->where('facility_id', $user->facility_id)
        ->latest('date_of_booking')
        ->take(5)
        ->get()
        ->map(function ($record) {
          return [
            'title' => 'Antenatal Registration',
            // 'description' => "Registered {$record->user->first_name} {$record->user->last_name} (DIN: {$record->user->DIN}) for antenatal care.",
            'date' => $record->date_of_booking,
            'icon' => 'bx-plus-medical',
            'color' => 'primary'
          ];
        });

      // Delivery records
      $delivery_records = Delivery::with('user')
        ->where('facility_id', $user->facility_id)
        ->latest('dodel')
        ->take(5)
        ->get()
        ->map(function ($record) {
          return [
            'title' => 'Delivery Record',
            //'description' => "Recorded delivery for {$record->user->first_name} {$record->user->last_name} (DIN: {$record->user->DIN}).",
            'date' => $record->dodel,
            'icon' => 'bx-baby-carriage',
            'color' => 'success'
          ];
        });

      // Postnatal records
      $postnatal_records = PostnatalRecord::with('user')
        ->where('facility_id', $user->facility_id)
        ->latest('visit_date')
        ->take(5)
        ->get()
        ->map(function ($record) {
          return [
            'title' => 'Postnatal Visit',
            // 'description' => "Postnatal visit for {$record->user->first_name} {$record->user->last_name} (DIN: {$record->user->DIN}).",
            'date' => $record->visit_date,
            'icon' => 'bx-heart',
            'color' => 'info'
          ];
        });

      // Tetanus records
      $tetanus_records = TetanusVaccination::with('user')
        ->where('facility_id', $user->facility_id)
        ->latest('visit_date')
        ->take(5)
        ->get()
        ->map(function ($record) {
          return [
            'title' => 'Tetanus Vaccination',
            //'description' => "Administered {$record->current_tt_dose} to {$record->user->first_name} {$record->user->last_name} (DIN: {$record->user->DIN}).",
            'date' => $record->visit_date,
            'icon' => 'bx-shield-plus',
            'color' => 'warning'
          ];
        });

      // Daily attendance records
      $attendance_records = DailyAttendance::with('user')
        ->where('facility_id', $user->facility_id) // limit to tenant facility only
        ->latest('visit_date')
        ->take(5)
        ->get()
        ->map(function ($record) {
          return [
            'title' => 'Daily Attendance',
            //'description' => "Attendance recorded for {$record->user->first_name} {$record->user->last_name} (DIN: {$record->user->DIN}).",
            'date' => $record->visit_date,
            'icon' => 'bx-calendar-check',
            'color' => 'secondary'
          ];
        });

      // Merge and sort by date, limit to 10 recent activities
      $activities = $antenatal_records
        ->merge($delivery_records)
        ->merge($postnatal_records)
        ->merge($tetanus_records)
        ->merge($attendance_records)
        ->sortByDesc('date')
        ->take(10)
        ->values();

      return $activities;
    });
  }

  public function render()
  {
    return view('livewire.registers.data-officer-dashboard', [
      'antenatal_count' => $this->antenatal_count,
      'delivery_count' => $this->delivery_count,
      'postnatal_count' => $this->postnatal_count,
      'tetanus_count' => $this->tetanus_count,
      'attendance_count' => $this->attendance_count,
      'active_patients' => $this->active_patients,
      'total_patients' => $this->total_patients,
      'recent_activity_count' => $this->recent_activity_count,
      'recent_activities' => $this->recent_activities
    ])->layout('layouts.dataOfficerLayout');
  }
}
