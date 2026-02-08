<?php

namespace App\Livewire\Core;

use App\Models\User;
use Livewire\Component;
use App\Models\Facility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\Log;

class FacilityReports extends Component
{
  public $selected_report = '';
  public $date_from = '';
  public $date_to = '';
  public $report_data = [];
  public $report_title = '';
  public $show_results = false;

  // Scope-related properties
  public $scopeInfo = [];
  public $selectedFacilityId = null;
  public $availableFacilities = [];

  protected $scopeService;

  // Available reports
  public $available_reports = [
    'patient_summary' => 'Patient Summary Report',
    'antenatal_bookings' => 'Antenatal Bookings Report',
    'delivery_summary' => 'Delivery Summary Report',
    'tetanus_vaccination' => 'Tetanus Vaccination Report',
    'clinical_notes' => 'Clinical Notes Report',
    'attendance_summary' => 'Daily Attendance Report',
    'appointment_tracking' => 'Appointment Tracking Report',
    'maternal_outcomes' => 'Maternal Health Outcomes',
    'department_utilization' => 'Department Utilization Report',
    'staff_productivity' => 'Staff Productivity Report'
  ];

  public function boot(DataScopeService $scopeService)
  {
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    // Get user scope information
    $this->scopeInfo = $this->scopeService->getUserScope();

    // Load available facilities for dropdown (if multi-facility scope)
    if (count($this->scopeInfo['facility_ids']) > 1) {
      $this->availableFacilities = Facility::whereIn('id', $this->scopeInfo['facility_ids'])
        ->orderBy('name')
        ->get()
        ->map(function ($facility) {
          return [
            'id' => $facility->id,
            'name' => $facility->name,
            'lga' => $facility->lga,
            'ward' => $facility->ward,
            'state' => $facility->state
          ];
        })->toArray();
    }

    // Set default date range (last 30 days)
    $this->date_to = Carbon::now()->format('Y-m-d');
    $this->date_from = Carbon::now()->subDays(30)->format('Y-m-d');

    Log::info('Facility Reports Initialized', [
      'user_id' => Auth::id(),
      'scope_type' => $this->scopeInfo['scope_type'],
      'facility_count' => count($this->scopeInfo['facility_ids'])
    ]);
  }

  public function updatedSelectedReport()
  {
    $this->report_data = [];
    $this->report_title = '';
    $this->show_results = false;
  }

  public function selectFacility($facilityId)
  {
    $this->selectedFacilityId = $facilityId;
    $this->report_data = [];
    $this->show_results = false;

    $facilityName = Facility::find($facilityId)->name ?? 'Unknown';
    toastr()->info("Viewing data for {$facilityName}");
  }

  public function resetToScope()
  {
    $this->selectedFacilityId = null;
    $this->report_data = [];
    $this->show_results = false;

    $scopeLabel = $this->scopeInfo['scope_type'] === 'lga' ? 'LGA' : ($this->scopeInfo['scope_type'] === 'state' ? 'State' : 'Facility');
    toastr()->info("Viewing data for entire {$scopeLabel}");
  }

  public function generateReport()
  {
    if (!$this->selected_report) {
      toastr()->error('Please select a report type');
      return;
    }

    // Clear previous results
    $this->report_data = [];
    $this->report_title = '';
    $this->show_results = false;

    try {
      switch ($this->selected_report) {
        case 'patient_summary':
          $this->generatePatientSummaryReport();
          break;
        case 'antenatal_bookings':
          $this->generateAntenatalBookingsReport();
          break;
        case 'delivery_summary':
          $this->generateDeliverySummaryReport();
          break;
        case 'tetanus_vaccination':
          $this->generateTetanusVaccinationReport();
          break;
        case 'clinical_notes':
          $this->generateClinicalNotesReport();
          break;
        case 'attendance_summary':
          $this->generateAttendanceSummaryReport();
          break;
        case 'appointment_tracking':
          $this->generateAppointmentTrackingReport();
          break;
        case 'maternal_outcomes':
          $this->generateMaternalOutcomesReport();
          break;
        case 'department_utilization':
          $this->generateDepartmentUtilizationReport();
          break;
        case 'staff_productivity':
          $this->generateStaffProductivityReport();
          break;
        default:
          toastr()->error('Invalid report type selected');
          return;
      }

      $this->report_title = $this->available_reports[$this->selected_report];
      $this->show_results = true;
      toastr()->info('Report generated successfully');
    } catch (\Exception $e) {
      Log::error('Report generation failed: ' . $e->getMessage());
      toastr()->error('Error generating report: ' . $e->getMessage());
    }
  }

  /**
   * Get facility IDs based on current selection
   */
  private function getFacilityIds()
  {
    return $this->selectedFacilityId
      ? [$this->selectedFacilityId]
      : $this->scopeInfo['facility_ids'];
  }

  private function generatePatientSummaryReport()
  {
    $facilityIds = $this->getFacilityIds();

    $this->report_data = DB::table('users')
      ->leftJoin('daily_attendances', function ($join) use ($facilityIds) {
        $join->on('users.id', '=', 'daily_attendances.user_id')
          ->whereIn('daily_attendances.facility_id', $facilityIds)
          ->whereBetween('daily_attendances.visit_date', [$this->date_from, $this->date_to]);
      })
      ->leftJoin('antenatals', 'users.id', '=', 'antenatals.user_id')
      ->leftJoin('deliveries', function ($join) use ($facilityIds) {
        $join->on('users.id', '=', 'deliveries.patient_id')
          ->whereIn('deliveries.facility_id', $facilityIds);
      })
      ->leftJoin('facilities', 'daily_attendances.facility_id', '=', 'facilities.id')
      ->where('users.role', 'Patient')
      ->whereNotNull('daily_attendances.id')
      ->select([
        'users.id',
        'users.first_name',
        'users.last_name',
        'users.DIN',
        'users.phone',
        'facilities.name as facility_name',
        DB::raw('COUNT(DISTINCT daily_attendances.id) as total_visits'),
        DB::raw('COUNT(DISTINCT antenatals.id) as antenatal_records'),
        DB::raw('COUNT(DISTINCT deliveries.id) as delivery_records'),
        DB::raw('MAX(daily_attendances.visit_date) as last_visit')
      ])
      ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.DIN', 'users.phone', 'facilities.name')
      ->orderBy('last_visit', 'desc')
      ->get();
  }

  private function generateAntenatalBookingsReport()
  {
    $facilityIds = $this->getFacilityIds();

    $this->report_data = DB::table('antenatals')
      ->join('users', 'antenatals.user_id', '=', 'users.id')
      ->join('facilities', 'antenatals.registration_facility_id', '=', 'facilities.id')
      ->whereIn('antenatals.registration_facility_id', $facilityIds)
      ->whereBetween('antenatals.date_of_booking', [$this->date_from, $this->date_to])
      ->select([
        'users.first_name',
        'users.last_name',
        'users.DIN',
        'users.phone',
        'facilities.name as facility_name',
        'antenatals.date_of_booking',
        'antenatals.lmp',
        'antenatals.edd',
        'antenatals.age',
        'antenatals.blood_group_rhesus',
        'antenatals.genotype',
        'antenatals.hemoglobin',
        'antenatals.follow_up_next_visit'
      ])
      ->orderBy('antenatals.date_of_booking', 'desc')
      ->get();
  }

  private function generateDeliverySummaryReport()
  {
    $facilityIds = $this->getFacilityIds();

    $this->report_data = DB::table('deliveries')
      ->join('patients', 'deliveries.patient_id', '=', 'patients.id')
      ->join('facilities', 'deliveries.facility_id', '=', 'facilities.id')
      ->whereIn('deliveries.facility_id', $facilityIds)
      ->whereBetween('deliveries.dodel', [$this->date_from, $this->date_to])
      ->select([
        'patients.first_name',
        'patients.last_name',
        'patients.din',
        'facilities.name as facility_name',
        'deliveries.dodel',
        'deliveries.mod',
        'deliveries.weight',
        'deliveries.baby_sex',
        'deliveries.alive',
        'deliveries.still_birth',
        'deliveries.complications',
        'deliveries.officer_name',
        'deliveries.took_delivery'
      ])
      ->orderBy('deliveries.dodel', 'desc')
      ->get();
  }

  private function generateTetanusVaccinationReport()
  {
    $facilityIds = $this->getFacilityIds();

    $this->report_data = DB::table('tetanus_vaccinations')
      ->join('patients', 'tetanus_vaccinations.patient_id', '=', 'patients.id')
      ->join('facilities', 'tetanus_vaccinations.facility_id', '=', 'facilities.id')
      ->whereIn('tetanus_vaccinations.facility_id', $facilityIds)
      ->whereBetween('tetanus_vaccinations.dose_date', [$this->date_from, $this->date_to])
      ->select([
        'patients.first_name',
        'patients.last_name',
        'patients.din',
        'facilities.name as facility_name',
        'tetanus_vaccinations.dose_date',
        'tetanus_vaccinations.current_tt_dose',
        'tetanus_vaccinations.protection_status',
        'tetanus_vaccinations.next_appointment_date',
        'tetanus_vaccinations.vaccination_site',
        'tetanus_vaccinations.adverse_event',
        'tetanus_vaccinations.officer_name'
      ])
      ->orderBy('tetanus_vaccinations.dose_date', 'desc')
      ->get();
  }

  private function generateClinicalNotesReport()
  {
    $facilityIds = $this->getFacilityIds();

    $this->report_data = DB::table('clinical_notes')
      ->join('users', 'clinical_notes.user_id', '=', 'users.id')
      ->join('facilities', 'clinical_notes.facility_id', '=', 'facilities.id')
      ->whereIn('clinical_notes.facility_id', $facilityIds)
      ->whereBetween('clinical_notes.date_of_visit', [$this->date_from, $this->date_to])
      ->select([
        'users.first_name',
        'users.last_name',
        'users.DIN',
        'facilities.name as facility_name',
        'clinical_notes.date_of_visit',
        'clinical_notes.section',
        'clinical_notes.note',
        'clinical_notes.officer_name',
        'clinical_notes.officer_designation'
      ])
      ->orderBy('clinical_notes.date_of_visit', 'desc')
      ->get();
  }

  private function generateAttendanceSummaryReport()
  {
    $facilityIds = $this->getFacilityIds();

    $this->report_data = DB::table('daily_attendances')
      ->join('users', 'daily_attendances.user_id', '=', 'users.id')
      ->join('facilities', 'daily_attendances.facility_id', '=', 'facilities.id')
      ->whereIn('daily_attendances.facility_id', $facilityIds)
      ->whereBetween('daily_attendances.visit_date', [$this->date_from, $this->date_to])
      ->select([
        'users.first_name',
        'users.last_name',
        'users.DIN',
        'facilities.name as facility_name',
        'daily_attendances.visit_date',
        'daily_attendances.gender',
        'daily_attendances.age_group',
        'daily_attendances.first_contact',
        'daily_attendances.address',
        'daily_attendances.phone'
      ])
      ->orderBy('daily_attendances.visit_date', 'desc')
      ->get();
  }

  private function generateAppointmentTrackingReport()
  {
    $facilityIds = $this->getFacilityIds();
    $appointments = collect();

    // Antenatal appointments
    $antenatalAppts = DB::table('antenatals')
      ->join('users', 'antenatals.user_id', '=', 'users.id')
      ->join('facilities', 'antenatals.registration_facility_id', '=', 'facilities.id')
      ->whereIn('antenatals.registration_facility_id', $facilityIds)
      ->whereNotNull('antenatals.follow_up_next_visit')
      ->whereBetween('antenatals.follow_up_next_visit', [$this->date_from, $this->date_to])
      ->select([
        'users.first_name',
        'users.last_name',
        'users.DIN',
        'facilities.name as facility_name',
        'antenatals.follow_up_next_visit as appointment_date',
        DB::raw("'Antenatal Follow-up' as appointment_type"),
        'users.id as user_id',
        'antenatals.registration_facility_id as facility_id'
      ])
      ->get();

    foreach ($antenatalAppts as $appt) {
      $fulfilled = DB::table('daily_attendances')
        ->where('user_id', $appt->user_id)
        ->where('facility_id', $appt->facility_id)
        ->where('visit_date', '>=', $appt->appointment_date)
        ->exists();

      $appt->status = $fulfilled ? 'Fulfilled' : 'Missed';
      $appointments->push($appt);
    }

    $this->report_data = $appointments->sortBy('appointment_date');
  }

  private function generateMaternalOutcomesReport()
  {
    $facilityIds = $this->getFacilityIds();

    $this->report_data = DB::table('deliveries')
      ->join('patients', 'deliveries.patient_id', '=', 'patients.id')
      ->join('facilities', 'deliveries.facility_id', '=', 'facilities.id')
      ->leftJoin('antenatals', 'patients.id', '=', 'antenatals.user_id')
      ->whereIn('deliveries.facility_id', $facilityIds)
      ->whereBetween('deliveries.dodel', [$this->date_from, $this->date_to])
      ->select([
        'users.first_name',
        'users.last_name',
        'users.DIN',
        'facilities.name as facility_name',
        'deliveries.dodel',
        'deliveries.mod',
        'deliveries.weight',
        'deliveries.alive',
        'deliveries.still_birth',
        'deliveries.complications',
        'antenatals.age',
        'antenatals.previous_pregnancies',
        'antenatals.hemoglobin',
        'antenatals.blood_pressure'
      ])
      ->orderBy('deliveries.dodel', 'desc')
      ->get();
  }

  private function generateDepartmentUtilizationReport()
  {
    $facilityIds = $this->getFacilityIds();

    $this->report_data = DB::table('facility_departments')
      ->leftJoin('clinical_notes', function ($join) {
        $join->on('facility_departments.facility_id', '=', 'clinical_notes.facility_id')
          ->whereBetween('clinical_notes.date_of_visit', [$this->date_from, $this->date_to]);
      })
      ->join('facilities', 'facility_departments.facility_id', '=', 'facilities.id')
      ->whereIn('facility_departments.facility_id', $facilityIds)
      ->where('facility_departments.is_active', true)
      ->select([
        'facility_departments.name as department_name',
        'facilities.name as facility_name',
        'facility_departments.details',
        DB::raw('COUNT(clinical_notes.id) as total_notes'),
        DB::raw('COUNT(DISTINCT clinical_notes.user_id) as unique_patients')
      ])
      ->groupBy('facility_departments.id', 'facility_departments.name', 'facilities.name', 'facility_departments.details')
      ->orderBy('total_notes', 'desc')
      ->get();
  }

  private function generateStaffProductivityReport()
  {
    $facilityIds = $this->getFacilityIds();
    $activities = collect();

    // Clinical notes
    $clinicalNotes = DB::table('clinical_notes')
      ->join('facilities', 'clinical_notes.facility_id', '=', 'facilities.id')
      ->whereIn('clinical_notes.facility_id', $facilityIds)
      ->whereBetween('date_of_visit', [$this->date_from, $this->date_to])
      ->selectRaw('officer_name, officer_designation, facilities.name as facility_name, COUNT(*) as activity_count, "Clinical Notes" as activity_type')
      ->groupBy('officer_name', 'officer_designation', 'facilities.name')
      ->get();

    // Deliveries
    $deliveries = DB::table('deliveries')
      ->join('facilities', 'deliveries.facility_id', '=', 'facilities.id')
      ->whereIn('deliveries.facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->date_from, $this->date_to])
      ->selectRaw('officer_name, officer_designation, facilities.name as facility_name, COUNT(*) as activity_count, "Deliveries" as activity_type')
      ->groupBy('officer_name', 'officer_designation', 'facilities.name')
      ->get();

    // Tetanus vaccinations
    $vaccinations = DB::table('tetanus_vaccinations')
      ->join('facilities', 'tetanus_vaccinations.facility_id', '=', 'facilities.id')
      ->whereIn('tetanus_vaccinations.facility_id', $facilityIds)
      ->whereBetween('dose_date', [$this->date_from, $this->date_to])
      ->selectRaw('officer_name, officer_designation, facilities.name as facility_name, COUNT(*) as activity_count, "Vaccinations" as activity_type')
      ->groupBy('officer_name', 'officer_designation', 'facilities.name')
      ->get();

    // Combine and aggregate by officer
    $allActivities = $clinicalNotes->concat($deliveries)->concat($vaccinations);

    $staffSummary = $allActivities->groupBy('officer_name')->map(function ($activities, $officerName) {
      $officer = $activities->first();
      return (object) [
        'officer_name' => $officerName,
        'officer_designation' => $officer->officer_designation,
        'facility_name' => $officer->facility_name,
        'total_activities' => $activities->sum('activity_count'),
        'activity_breakdown' => $activities->pluck('activity_count', 'activity_type')->toArray()
      ];
    })->values();

    $this->report_data = $staffSummary->sortByDesc('total_activities');
  }

  public function getReportDescription($reportType)
  {
    $descriptions = [
      'patient_summary' => 'Overview of all patients with visit statistics and medical record counts',
      'antenatal_bookings' => 'Complete antenatal care registrations with pregnancy details and follow-up schedules',
      'delivery_summary' => 'Delivery records including outcomes, complications, and newborn details',
      'tetanus_vaccination' => 'Tetanus immunization tracking with dose schedules and protection status',
      'clinical_notes' => 'Medical notes, lab results, and clinical observations by healthcare providers',
      'attendance_summary' => 'Daily patient visits with demographic information and contact details',
      'appointment_tracking' => 'Appointment compliance analysis showing fulfilled vs missed appointments',
      'maternal_outcomes' => 'Maternal and newborn health outcomes analysis for quality improvement',
      'department_utilization' => 'Department-wise service utilization and patient flow analysis',
      'staff_productivity' => 'Healthcare worker activity tracking and productivity metrics'
    ];

    return $descriptions[$reportType] ?? 'Detailed facility report for administrative and clinical analysis';
  }

  public function render()
  {
    $user = Auth::user();
    $layout = match (true) {
      in_array($user->role, ['State Data Administrator']) => 'layouts.stateOfficerLayout',
      in_array($user->role, ['LGA Officer']) => 'layouts.lgaOfficerLayout',
      in_array($user->role, ['Facility Administrator']) => 'layouts.facilityAdminLayout',
      default => 'facilityAdminLayout'
    };

    return view('livewire.core.facility-reports')
      ->layout($layout);
  }
}
