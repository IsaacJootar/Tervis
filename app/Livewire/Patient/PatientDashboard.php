<?php

namespace App\Livewire\Patient;

use Livewire\Component;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Antenatal;
use App\Models\Delivery;
use App\Models\PostnatalRecord;
use App\Models\TetanusVaccination;
use App\Models\DailyAttendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PatientDashboard extends Component
{
  public $user, $antenatal_count, $delivery_count, $postnatal_count, $tetanus_count, $attendance_count;
  public $recent_activities, $next_appointments, $protection_status;
  public $facility_name, $registration_facility_name;

  public function mount()
  {
    $this->user = Auth::user();

    if (!$this->user || $this->user->role !== 'Patient') {
      abort(403, 'Unauthorized: Only Patients can access this page.');
    }

    $this->loadPatientData();
    $this->loadRecentActivities();
    $this->calculateTetanusProtection();
  }

  private function loadPatientData()
  {
    $userId = $this->user->id;

    // Cache counts for better performance
    $this->antenatal_count = Cache::remember("patient_{$userId}_antenatal_count", 300, function () use ($userId) {
      return Antenatal::where('user_id', $userId)->count();
    });

    $this->delivery_count = Cache::remember("patient_{$userId}_delivery_count", 300, function () use ($userId) {
      return Delivery::where('patient_id', $userId)->count();
    });

    $this->postnatal_count = Cache::remember("patient_{$userId}_postnatal_count", 300, function () use ($userId) {
      return PostnatalRecord::where('patient_id', $userId)->count();
    });

    $this->tetanus_count = Cache::remember("patient_{$userId}_tetanus_count", 300, function () use ($userId) {
      return TetanusVaccination::where('patient_id', $userId)->count();
    });

    $this->attendance_count = Cache::remember("patient_{$userId}_attendance_count", 300, function () use ($userId) {
      return DailyAttendance::where('user_id', $userId)->count();
    });

    // Get facility information
    $antenatal = Antenatal::with('registrationFacility')->where('user_id', $userId)->first();
    $this->registration_facility_name = $antenatal->registrationFacility->name ?? 'N/A';
    $this->facility_name = $antenatal->registrationFacility->name ?? 'N/A';
  }

  private function loadRecentActivities()
  {
    $userId = $this->user->id;

    $activities = collect();

    // Recent deliveries
    $deliveries = Delivery::where('patient_id', $userId)
      ->latest('dodel')
      ->take(3)
      ->get();

    foreach ($deliveries as $delivery) {
      $activities->push([
        'type' => 'delivery',
        'title' => 'Delivery Record',
        'date' => $delivery->dodel,
        'description' => "Mode: {$delivery->mod}, Baby: {$delivery->baby_sex}",
        'icon' => 'bx-baby-carriage',
        'color' => 'success'
      ]);
    }

    // Recent postnatal visits
    $postnatal = PostnatalRecord::where('patient_id', $userId)
      ->latest('visit_date')
      ->take(3)
      ->get();

    foreach ($postnatal as $post) {
      $activities->push([
        'type' => 'postnatal',
        'title' => 'Postnatal Visit',
        'date' => $post->visit_date,
        'description' => "Attendance: {$post->attendance}, Outcome: {$post->visit_outcome}",
        'icon' => 'bx-heart',
        'color' => 'info'
      ]);
    }

    // Recent tetanus vaccinations
    $tetanus = TetanusVaccination::where('patient_id', $userId)
      ->latest('visit_date')
      ->take(3)
      ->get();

    foreach ($tetanus as $vaccination) {
      $activities->push([
        'type' => 'tetanus',
        'title' => 'Tetanus Vaccination',
        'date' => $vaccination->visit_date,
        'description' => "{$vaccination->current_tt_dose} - {$vaccination->protection_status}",
        'icon' => 'bx-shield-plus',
        'color' => 'warning'
      ]);
    }

    $this->recent_activities = $activities->sortByDesc('date')->take(5);
  }

  private function calculateTetanusProtection()
  {
    $tetanusCount = TetanusVaccination::where('patient_id', $this->user->id)->count();

    switch ($tetanusCount) {
      case 0:
        $this->protection_status = ['status' => 'Not Protected', 'percentage' => 0, 'color' => 'danger'];
        break;
      case 1:
        $this->protection_status = ['status' => 'Not Protected', 'percentage' => 20, 'color' => 'danger'];
        break;
      case 2:
        $this->protection_status = ['status' => 'Partially Protected', 'percentage' => 40, 'color' => 'warning'];
        break;
      case 3:
      case 4:
        $this->protection_status = ['status' => 'Protected', 'percentage' => 80, 'color' => 'info'];
        break;
      case 5:
      default:
        $this->protection_status = ['status' => 'Fully Protected', 'percentage' => 100, 'color' => 'success'];
        break;
    }
  }

  public function render()
  {
    return view('livewire.patient.patient-dashboard')
      ->layout('layouts.patientLayout');
  }
}
