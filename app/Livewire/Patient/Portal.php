<?php

namespace App\Livewire\Patient;

use App\Models\Activity;
use App\Models\AntenatalFollowUpAssessment;
use App\Models\Delivery;
use App\Models\DoctorAssessment;
use App\Models\FamilyPlanningFollowUp;
use App\Models\Invoice;
use App\Models\LabTestOrder;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\Reminder;
use App\Models\Registrations\AntenatalRegistration;
use App\Models\Registrations\DinActivation;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\TetanusVaccination;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.patientLayout')]
class Portal extends Component
{
  use WithPagination;

  private const PER_PAGE = 10;

  protected string $paginationTheme = 'bootstrap';

  public string $section = 'dashboard';
  public string $heading = 'Patient Dashboard';
  public string $description = 'Your patient portal section is available.';
  public Patient $patient;
  public $user;
  public string $registration_facility_name = 'N/A';
  public bool $edit_mode = false;
  public ?object $selected_record = null;

  public string $first_name = '';
  public string $last_name = '';
  public ?string $email = null;
  public ?string $phone = null;
  public ?string $current_password = null;
  public ?string $new_password = null;
  public ?string $new_password_confirmation = null;

  public function mount(string $section = 'dashboard'): void
  {
    $normalized = trim(strtolower($section));
    $definition = $this->sectionDefinitions()[$normalized] ?? $this->sectionDefinitions()['dashboard'];

    $this->section = $definition['key'];
    $this->heading = $definition['heading'];
    $this->description = $definition['description'];
    $this->bootPatientContext();
  }

  public function updatingSection(): void
  {
    $this->resetPage();
    $this->selected_record = null;
  }

  public function toggleEditMode(): void
  {
    $this->edit_mode = !$this->edit_mode;

    if (!$this->edit_mode) {
      $this->hydrateProfileFields();
    }
  }

  public function updateProfile(): void
  {
    $validated = $this->validate([
      'first_name' => 'required|string|max:255',
      'last_name' => 'required|string|max:255',
      'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
      'phone' => 'nullable|string|max:20',
    ]);

    DB::transaction(function () use ($validated) {
      $this->user->update([
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'] ?: null,
        'phone' => $validated['phone'] ?: null,
      ]);

      $this->patient->update([
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'] ?: null,
        'phone' => $validated['phone'] ?: null,
      ]);
    });

    $this->user = $this->user->fresh(['patient.facility']);
    $this->patient = $this->user->patient ?? $this->patient->fresh(['facility']);
    $this->hydrateProfileFields();
    $this->edit_mode = false;

    toastr()->info('Profile updated successfully.');
  }

  public function viewRecord(int $recordId): void
  {
    $patientId = (int) $this->patient->id;

    $this->selected_record = match ($this->section) {
      'antenatal' => AntenatalRegistration::query()->with(['user', 'facility'])->where('patient_id', $patientId)->findOrFail($recordId),
      'deliveries' => Delivery::query()->with(['user', 'facility'])->where('patient_id', $patientId)->findOrFail($recordId),
      'postnatal' => PostnatalRecord::query()->with(['user', 'facility'])->where('patient_id', $patientId)->findOrFail($recordId),
      'tetanus' => TetanusVaccination::query()->with(['user', 'facility'])->where('patient_id', $patientId)->findOrFail($recordId),
      default => null,
    };
  }

  public function closeModal(): void
  {
    $this->selected_record = null;
  }

  public function render()
  {
    $sectionData = $this->sectionViewData();

    return view('livewire.patient.portal', array_merge(
      [
        'contentView' => $this->contentViewForSection(),
        'user' => $this->user,
        'registration_facility_name' => $this->registration_facility_name,
        'portalSections' => $this->portalSections(),
        'sectionMetrics' => $this->sectionMetrics($sectionData),
        'currentDateTime' => now('Africa/Lagos')->format('l, F j, Y, h:i A'),
      ],
      $sectionData
    ));
  }

  private function bootPatientContext(): void
  {
    $this->user = Auth::user();
    abort_unless($this->user, 403);

    $this->user->loadMissing('patient.facility');
    $patient = $this->user->patient;

    if (!$patient && preg_match('/^\d{8}$/', (string) $this->user->username)) {
      $patient = Patient::query()->with('facility')->where('din', (string) $this->user->username)->first();

      if ($patient && !$this->user->patient_id) {
        $this->user->forceFill(['patient_id' => $patient->id])->save();
        $this->user = $this->user->fresh(['patient.facility']);
      }
    }

    abort_unless($patient, 404, 'Linked patient record not found for this account.');

    $this->patient = $patient;
    $this->registration_facility_name = $patient->facility?->name ?? 'N/A';
    $this->hydrateProfileFields();
  }

  private function hydrateProfileFields(): void
  {
    $this->first_name = (string) ($this->user->first_name ?? $this->patient->first_name ?? '');
    $this->last_name = (string) ($this->user->last_name ?? $this->patient->last_name ?? '');
    $this->email = $this->user->email ?: $this->patient->email;
    $this->phone = $this->user->phone ?: $this->patient->phone;
    $this->current_password = null;
    $this->new_password = null;
    $this->new_password_confirmation = null;
  }

  private function sectionViewData(): array
  {
    return match ($this->section) {
      'dashboard' => $this->dashboardViewData(),
      'profile' => $this->profileViewData(),
      'antenatal' => $this->antenatalViewData(),
      'deliveries' => $this->deliveriesViewData(),
      'postnatal' => $this->postnatalViewData(),
      'tetanus' => $this->tetanusViewData(),
      'attendance' => $this->attendanceViewData(),
      'activities' => $this->activitiesViewData(),
      'appointments' => $this->appointmentsViewData(),
      'visits' => $this->visitsViewData(),
      'assessments' => $this->assessmentsViewData(),
      'reminders' => $this->remindersViewData(),
      'laboratory' => $this->laboratoryViewData(),
      'prescriptions' => $this->prescriptionsViewData(),
      'invoices' => $this->invoicesViewData(),
      'referrals' => $this->referralsViewData(),
      'family-planning' => $this->familyPlanningViewData(),
      'health-insurance' => $this->healthInsuranceViewData(),
      default => [],
    };
  }

  private function portalSections(): array
  {
    return collect($this->sectionDefinitions())
      ->map(fn(array $section) => [
        'key' => $section['key'],
        'label' => $section['label'],
        'route' => $section['route'],
        'icon' => $section['icon'],
      ])
      ->values()
      ->all();
  }

  private function sectionMetrics(array $sectionData): array
  {
    return match ($this->section) {
      'dashboard' => [
        ['label' => 'Antenatal Records', 'value' => (string) ($sectionData['antenatal_count'] ?? 0), 'tone' => 'sky', 'icon' => 'antenatal'],
        ['label' => 'Deliveries', 'value' => (string) ($sectionData['delivery_count'] ?? 0), 'tone' => 'emerald', 'icon' => 'delivery'],
        ['label' => 'Postnatal Visits', 'value' => (string) ($sectionData['postnatal_count'] ?? 0), 'tone' => 'rose', 'icon' => 'postnatal'],
        ['label' => 'Tetanus Progress', 'value' => (string) (($sectionData['tetanus_count'] ?? 0) . '/5'), 'tone' => 'amber', 'icon' => 'shield'],
        ['label' => 'Total Visits', 'value' => (string) ($sectionData['attendance_count'] ?? 0), 'tone' => 'slate', 'icon' => 'visit'],
        ['label' => 'Protection Status', 'value' => (string) (($sectionData['protection_status']['status'] ?? 'N/A')), 'tone' => 'mint', 'icon' => 'security'],
      ],
      default => $this->secondarySectionMetrics($sectionData),
    };
  }

  private function secondarySectionMetrics(array $sectionData): array
  {
    return match ($this->section) {
      'profile' => [
        ['label' => 'DIN', 'value' => (string) ($this->user->DIN ?? 'N/A'), 'tone' => 'sky', 'icon' => 'id-card'],
        ['label' => 'Username', 'value' => (string) ($this->user->username ?? 'N/A'), 'tone' => 'emerald', 'icon' => 'user'],
        ['label' => 'Member Since', 'value' => (string) $this->user->created_at?->format('M Y'), 'tone' => 'slate', 'icon' => 'calendar'],
      ],
      'antenatal' => [
        ['label' => 'Total Records', 'value' => (string) $sectionData['antenatal_records']->total(), 'tone' => 'sky', 'icon' => 'records'],
        ['label' => 'Latest Booking', 'value' => $sectionData['antenatal_latest_booking'] ?? 'N/A', 'tone' => 'emerald', 'icon' => 'calendar'],
        ['label' => 'Current Pregnancy', 'value' => $sectionData['antenatal_pregnancy_label'] ?? 'N/A', 'tone' => 'amber', 'icon' => 'pregnancy'],
      ],
      'deliveries' => [
        ['label' => 'Total Deliveries', 'value' => (string) $sectionData['deliveries']->total(), 'tone' => 'emerald', 'icon' => 'delivery'],
        ['label' => 'Latest Delivery', 'value' => $sectionData['deliveries_latest_date'] ?? 'N/A', 'tone' => 'sky', 'icon' => 'calendar'],
        ['label' => 'Latest Mode', 'value' => (string) ($sectionData['deliveries_latest_mode'] ?? 'N/A'), 'tone' => 'slate', 'icon' => 'records'],
      ],
      'postnatal' => [
        ['label' => 'Total Visits', 'value' => (string) $sectionData['postnatal_records']->total(), 'tone' => 'rose', 'icon' => 'postnatal'],
        ['label' => 'Latest Visit', 'value' => $sectionData['postnatal_latest_visit'] ?? 'N/A', 'tone' => 'sky', 'icon' => 'calendar'],
        ['label' => 'Stable Outcomes', 'value' => (string) ($sectionData['stable_postnatal_outcomes'] ?? 0), 'tone' => 'emerald', 'icon' => 'security'],
      ],
      'tetanus' => [
        ['label' => 'Completed Doses', 'value' => (string) (($sectionData['doses_completed'] ?? 0) . '/5'), 'tone' => 'amber', 'icon' => 'shield'],
        ['label' => 'Next Due', 'value' => (string) ($sectionData['next_due_dose'] ?? 'N/A'), 'tone' => 'sky', 'icon' => 'calendar'],
        ['label' => 'Protection Status', 'value' => (string) ($sectionData['protection_status']['status'] ?? 'N/A'), 'tone' => 'mint', 'icon' => 'security'],
      ],
      'attendance' => [
        ['label' => 'Check-Ins', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'calendar'],
        ['label' => 'Latest Visit', 'value' => $sectionData['attendance_latest_visit'] ?? 'N/A', 'tone' => 'emerald', 'icon' => 'visit'],
        ['label' => 'Facilities Visited', 'value' => (string) ($sectionData['attendance_facility_count'] ?? 0), 'tone' => 'slate', 'icon' => 'facility'],
      ],
      'activities' => [
        ['label' => 'Total Activities', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'activity'],
        ['label' => 'Today', 'value' => (string) ($sectionData['activities_today'] ?? 0), 'tone' => 'emerald', 'icon' => 'calendar'],
        ['label' => 'Modules', 'value' => (string) ($sectionData['activity_module_count'] ?? 0), 'tone' => 'slate', 'icon' => 'records'],
      ],
      'appointments' => [
        ['label' => 'Total Appointments', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'appointment'],
        ['label' => 'Upcoming', 'value' => (string) ($sectionData['appointments_upcoming'] ?? 0), 'tone' => 'emerald', 'icon' => 'calendar'],
        ['label' => 'Missed', 'value' => (string) ($sectionData['appointments_missed'] ?? 0), 'tone' => 'rose', 'icon' => 'alert'],
      ],
      'visits' => [
        ['label' => 'Visits', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'visit'],
        ['label' => 'Open Visits', 'value' => (string) ($sectionData['open_visit_count'] ?? 0), 'tone' => 'emerald', 'icon' => 'activity'],
        ['label' => 'This Month', 'value' => (string) ($sectionData['visits_this_month'] ?? 0), 'tone' => 'slate', 'icon' => 'calendar'],
      ],
      'assessments' => [
        ['label' => 'Assessments', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'assessment'],
        ['label' => 'Lab Requests', 'value' => (string) ($sectionData['assessment_lab_requests'] ?? 0), 'tone' => 'amber', 'icon' => 'lab'],
        ['label' => 'Drug Orders', 'value' => (string) ($sectionData['assessment_drug_requests'] ?? 0), 'tone' => 'emerald', 'icon' => 'prescription'],
      ],
      'reminders' => [
        ['label' => 'Reminders', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'reminder'],
        ['label' => 'Pending', 'value' => (string) ($sectionData['reminders_pending'] ?? 0), 'tone' => 'amber', 'icon' => 'alert'],
        ['label' => 'Sent', 'value' => (string) ($sectionData['reminders_sent'] ?? 0), 'tone' => 'emerald', 'icon' => 'activity'],
      ],
      'laboratory' => [
        ['label' => 'Lab Orders', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'lab'],
        ['label' => 'Pending', 'value' => (string) ($sectionData['lab_pending_count'] ?? 0), 'tone' => 'amber', 'icon' => 'alert'],
        ['label' => 'Completed', 'value' => (string) ($sectionData['lab_completed_count'] ?? 0), 'tone' => 'emerald', 'icon' => 'security'],
      ],
      'prescriptions' => [
        ['label' => 'Prescriptions', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'prescription'],
        ['label' => 'Pending', 'value' => (string) ($sectionData['prescriptions_pending'] ?? 0), 'tone' => 'amber', 'icon' => 'alert'],
        ['label' => 'Dispensed', 'value' => (string) ($sectionData['prescriptions_dispensed'] ?? 0), 'tone' => 'emerald', 'icon' => 'security'],
      ],
      'invoices' => [
        ['label' => 'Invoices', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'invoice'],
        ['label' => 'Outstanding', 'value' => (string) ($sectionData['invoice_outstanding_total'] ?? '0.00'), 'tone' => 'rose', 'icon' => 'alert'],
        ['label' => 'Paid', 'value' => (string) ($sectionData['invoice_paid_total'] ?? '0.00'), 'tone' => 'emerald', 'icon' => 'security'],
      ],
      'referrals' => [
        ['label' => 'Referrals', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'referral'],
        ['label' => 'Completed', 'value' => (string) ($sectionData['referrals_completed'] ?? 0), 'tone' => 'emerald', 'icon' => 'security'],
        ['label' => 'Follow-Up Needed', 'value' => (string) ($sectionData['referrals_follow_up_needed'] ?? 0), 'tone' => 'amber', 'icon' => 'alert'],
      ],
      'family-planning' => [
        ['label' => 'Records', 'value' => (string) $sectionData['tableRows']->total(), 'tone' => 'sky', 'icon' => 'records'],
        ['label' => 'Registrations', 'value' => (string) ($sectionData['family_planning_registrations_count'] ?? 0), 'tone' => 'emerald', 'icon' => 'activity'],
        ['label' => 'Follow-Ups', 'value' => (string) ($sectionData['family_planning_follow_ups_count'] ?? 0), 'tone' => 'amber', 'icon' => 'calendar'],
      ],
      'health-insurance' => [
        ['label' => 'Coverage', 'value' => (string) ($sectionData['insurance_status'] ?? 'Not Enrolled'), 'tone' => 'sky', 'icon' => 'insurance'],
        ['label' => 'Provider', 'value' => (string) ($sectionData['insurance_provider'] ?? 'N/A'), 'tone' => 'emerald', 'icon' => 'facility'],
        ['label' => 'History Entries', 'value' => (string) $sectionData['insurance_history']->total(), 'tone' => 'slate', 'icon' => 'activity'],
      ],
      default => [],
    };
  }

  private function dashboardViewData(): array
  {
    $patientId = (int) $this->patient->id;
    $tetanusRecords = TetanusVaccination::query()->where('patient_id', $patientId)->get(['current_tt_dose']);
    $attendanceCount = (int) DinActivation::query()->where('patient_id', $patientId)->count();

    return [
      'antenatal_count' => (int) AntenatalRegistration::query()->where('patient_id', $patientId)->count(),
      'delivery_count' => (int) Delivery::query()->where('patient_id', $patientId)->count(),
      'postnatal_count' => (int) PostnatalRecord::query()->where('patient_id', $patientId)->count(),
      'tetanus_count' => $this->completedTetanusDoses($tetanusRecords),
      'attendance_count' => $attendanceCount,
      'next_appointments' => $attendanceCount,
      'recent_activities' => $this->recentActivities($patientId),
      'protection_status' => $this->buildTetanusProtectionStatus($tetanusRecords),
    ];
  }

  private function profileViewData(): array
  {
    return [
      'antenatal_record' => $this->patient->activeAntenatalRegistration()->first(),
    ];
  }

  private function antenatalViewData(): array
  {
    $records = AntenatalRegistration::query()
      ->with(['user', 'facility'])
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('date_of_booking')
      ->orderByDesc('id')
      ->paginate(self::PER_PAGE, ['*'], 'antenatalPage');

    $latest = $records->first();

    return [
      'antenatal_records' => $records,
      'antenatal_latest_booking' => $latest?->date_of_booking?->format('M d, Y') ?? 'N/A',
      'antenatal_pregnancy_label' => $latest?->pregnancy_number ? 'Pregnancy #' . $latest->pregnancy_number : 'N/A',
    ];
  }

  private function deliveriesViewData(): array
  {
    $records = Delivery::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->latestFirst()
      ->paginate(self::PER_PAGE, ['*'], 'deliveriesPage');

    $latest = $records->first();

    return [
      'deliveries' => $records,
      'deliveries_latest_date' => $latest?->dodel?->format('M d, Y') ?? 'N/A',
      'deliveries_latest_mode' => $latest?->mod ?? 'N/A',
    ];
  }

  private function postnatalViewData(): array
  {
    $records = PostnatalRecord::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->latestFirst()
      ->paginate(self::PER_PAGE, ['*'], 'postnatalPage');

    return [
      'postnatal_records' => $records,
      'postnatal_latest_visit' => $records->first()?->visit_date?->format('M d, Y') ?? 'N/A',
      'stable_postnatal_outcomes' => PostnatalRecord::query()
        ->where('patient_id', $this->patient->id)
        ->where('visit_outcome', 'Stable')
        ->count(),
    ];
  }

  private function tetanusViewData(): array
  {
    $records = TetanusVaccination::query()
      ->with(['user', 'facility'])
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('visit_date')
      ->orderByDesc('id')
      ->paginate(self::PER_PAGE, ['*'], 'tetanusPage');

    $allRecords = TetanusVaccination::query()
      ->where('patient_id', $this->patient->id)
      ->get(['current_tt_dose']);

    $completed = $this->completedTetanusDoses($allRecords);

    return [
      'tetanus_records' => $records,
      'doses_completed' => $completed,
      'next_due_dose' => $completed >= 5 ? 'Complete' : 'TT' . ($completed + 1),
      'protection_status' => $this->buildTetanusProtectionStatus($allRecords),
    ];
  }

  private function attendanceViewData(): array
  {
    $records = DinActivation::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->latestFirst()
      ->paginate(self::PER_PAGE, ['*'], 'attendancePage')
      ->through(fn(DinActivation $record) => [
        'cells' => [
          $this->formatDate($record->visit_date),
          $this->formatDateTime($record->check_in_time, 'h:i A'),
          $record->facility?->name ?? 'N/A',
          $record->officer_name ?? 'N/A',
        ],
      ]);

    return [
      'tableTitle' => 'Attendance Timeline',
      'tableDescription' => 'Every DIN activation recorded for this patient, with facility and officer context.',
      'tableIcon' => 'bx-time-five',
      'tableColumns' => ['Visit Date', 'Check-In', 'Facility', 'Officer'],
      'tableRows' => $records,
      'emptyMessage' => 'No attendance records have been captured yet.',
      'attendance_latest_visit' => data_get($records->first(), 'cells.0', 'N/A'),
      'attendance_facility_count' => DinActivation::query()
        ->where('patient_id', $this->patient->id)
        ->distinct('facility_id')
        ->count('facility_id'),
    ];
  }

  private function activitiesViewData(): array
  {
    $baseQuery = Activity::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->latest('created_at')
      ->latest('id');

    return [
      'tableTitle' => 'Activity Log',
      'tableDescription' => 'A unified feed of what has happened across your patient-facing records and workflows.',
      'tableIcon' => 'bx-pulse',
      'tableColumns' => ['Date', 'Module', 'Action', 'Facility', 'Description'],
      'tableRows' => (clone $baseQuery)
        ->paginate(self::PER_PAGE, ['*'], 'activitiesPage')
        ->through(fn(Activity $activity) => [
          'cells' => [
            $activity->created_at?->format('M d, Y h:i A') ?? 'N/A',
            str((string) $activity->module)->replace(['_', '-'], ' ')->title()->value(),
            str((string) $activity->action)->replace(['_', '-'], ' ')->title()->value(),
            $activity->facility?->name ?? 'N/A',
            $activity->description ?: 'Patient activity recorded.',
          ],
        ]),
      'emptyMessage' => 'No activity has been logged for this patient yet.',
      'activities_today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
      'activity_module_count' => (clone $baseQuery)->distinct('module')->count('module'),
    ];
  }

  private function appointmentsViewData(): array
  {
    $appointments = $this->buildAppointmentRows();

    return [
      'tableTitle' => 'Appointment Planner',
      'tableDescription' => 'Upcoming, fulfilled, and missed appointments compiled from the patient record modules.',
      'tableIcon' => 'bx-calendar-event',
      'tableColumns' => ['Appointment Date', 'Type', 'Source', 'Status', 'Details'],
      'tableRows' => $this->paginateCollection($appointments, 'appointmentsPage'),
      'emptyMessage' => 'No appointments are available for this patient yet.',
      'appointments_upcoming' => $appointments->where('status', 'Upcoming')->count(),
      'appointments_missed' => $appointments->where('status', 'Missed')->count(),
    ];
  }

  private function visitsViewData(): array
  {
    $baseQuery = Visit::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('visit_date')
      ->orderByDesc('id');

    return [
      'tableTitle' => 'Visit History',
      'tableDescription' => 'Every collated visit against this patient, including status, events, and facility context.',
      'tableIcon' => 'bx-walk',
      'tableColumns' => ['Visit Date', 'Check-In', 'Status', 'Events', 'Facility'],
      'tableRows' => (clone $baseQuery)
        ->paginate(self::PER_PAGE, ['*'], 'visitsPage')
        ->through(fn(Visit $visit) => [
          'cells' => [
            $this->formatDate($visit->visit_date),
            $visit->check_in_display ?: 'N/A',
            ucfirst((string) $visit->status),
            (string) ($visit->total_events ?? 0),
            $visit->facility?->name ?? 'N/A',
          ],
        ]),
      'emptyMessage' => 'No visit records have been collated yet.',
      'open_visit_count' => (clone $baseQuery)->where('status', 'open')->count(),
      'visits_this_month' => (clone $baseQuery)
        ->whereBetween('visit_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
        ->count(),
    ];
  }

  private function assessmentsViewData(): array
  {
    $baseQuery = DoctorAssessment::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('visit_date')
      ->orderByDesc('id');

    return [
      'tableTitle' => 'Clinical Assessments',
      'tableDescription' => 'Doctor and clinical assessments recorded for this patient across the care journey.',
      'tableIcon' => 'bx-user-check',
      'tableColumns' => ['Visit Date', 'Diagnosis', 'Next Appointment', 'Facility', 'Officer'],
      'tableRows' => (clone $baseQuery)
        ->paginate(self::PER_PAGE, ['*'], 'assessmentsPage')
        ->through(fn(DoctorAssessment $assessment) => [
          'cells' => [
            $this->formatDate($assessment->visit_date),
            $assessment->final_diagnosis ?: 'N/A',
            $this->formatDate($assessment->next_appointment_date),
            $assessment->facility?->name ?? 'N/A',
            $assessment->officer_name ?? 'N/A',
          ],
        ]),
      'emptyMessage' => 'No clinical assessments are available yet.',
      'assessment_lab_requests' => (clone $baseQuery)->where('requires_lab_tests', true)->count(),
      'assessment_drug_requests' => (clone $baseQuery)->where('requires_drugs', true)->count(),
    ];
  }

  private function remindersViewData(): array
  {
    $baseQuery = Reminder::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('reminder_date')
      ->orderByDesc('id');

    return [
      'tableTitle' => 'Reminder Schedule',
      'tableDescription' => 'All reminders generated for this patient, including status and delivery channel.',
      'tableIcon' => 'bx-bell',
      'tableColumns' => ['Reminder Date', 'Title', 'Status', 'Channels', 'Facility'],
      'tableRows' => (clone $baseQuery)
        ->paginate(self::PER_PAGE, ['*'], 'remindersPage')
        ->through(fn(Reminder $reminder) => [
          'cells' => [
            $this->formatDate($reminder->reminder_date),
            $reminder->title ?: 'Untitled Reminder',
            str((string) $reminder->status)->replace('_', ' ')->title()->value(),
            collect((array) $reminder->channels)->filter()->implode(', ') ?: 'N/A',
            $reminder->facility?->name ?? 'N/A',
          ],
        ]),
      'emptyMessage' => 'No reminders have been scheduled yet.',
      'reminders_pending' => (clone $baseQuery)->whereIn('status', ['pending', 'queued'])->count(),
      'reminders_sent' => (clone $baseQuery)->where('status', 'sent')->count(),
    ];
  }

  private function laboratoryViewData(): array
  {
    $baseQuery = LabTestOrder::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('requested_at')
      ->orderByDesc('visit_date')
      ->orderByDesc('id');

    return [
      'tableTitle' => 'Laboratory Requests',
      'tableDescription' => 'Your lab test requests and completion progress, shown in a patient-friendly summary table.',
      'tableIcon' => 'bx-test-tube',
      'tableColumns' => ['Visit Date', 'Test', 'Priority', 'Status', 'Facility'],
      'tableRows' => (clone $baseQuery)
        ->paginate(self::PER_PAGE, ['*'], 'laboratoryPage')
        ->through(fn(LabTestOrder $order) => [
          'cells' => [
            $this->formatDate($order->visit_date),
            $order->test_name ?: 'N/A',
            str((string) $order->priority)->title()->value() ?: 'N/A',
            str((string) $order->status)->replace('_', ' ')->title()->value(),
            $order->facility?->name ?? 'N/A',
          ],
        ]),
      'emptyMessage' => 'No laboratory requests are available yet.',
      'lab_pending_count' => (clone $baseQuery)->where('status', 'pending')->count(),
      'lab_completed_count' => (clone $baseQuery)->where('status', 'completed')->count(),
    ];
  }

  private function prescriptionsViewData(): array
  {
    $baseQuery = Prescription::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('prescribed_date')
      ->orderByDesc('id');

    return [
      'tableTitle' => 'Prescription History',
      'tableDescription' => 'Prescriptions raised for this patient, including dosage, status, and facility source.',
      'tableIcon' => 'bx-capsule',
      'tableColumns' => ['Prescribed Date', 'Drug', 'Dosage', 'Status', 'Facility'],
      'tableRows' => (clone $baseQuery)
        ->paginate(self::PER_PAGE, ['*'], 'prescriptionsPage')
        ->through(fn(Prescription $prescription) => [
          'cells' => [
            $this->formatDate($prescription->prescribed_date),
            $prescription->drug_name ?: 'N/A',
            trim(implode(' ', array_filter([$prescription->dosage, $prescription->frequency, $prescription->duration]))),
            str((string) $prescription->status)->replace('_', ' ')->title()->value(),
            $prescription->facility?->name ?? 'N/A',
          ],
        ]),
      'emptyMessage' => 'No prescriptions are available yet.',
      'prescriptions_pending' => (clone $baseQuery)->where('status', 'pending')->count(),
      'prescriptions_dispensed' => (clone $baseQuery)->where('status', 'dispensed')->count(),
    ];
  }

  private function invoicesViewData(): array
  {
    $baseQuery = Invoice::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('invoice_date')
      ->orderByDesc('id');

    return [
      'tableTitle' => 'Invoice Summary',
      'tableDescription' => 'Bills, payment progress, and outstanding balances attached to this patient.',
      'tableIcon' => 'bx-receipt',
      'tableColumns' => ['Invoice Date', 'Invoice Code', 'Status', 'Outstanding', 'Facility'],
      'tableRows' => (clone $baseQuery)
        ->paginate(self::PER_PAGE, ['*'], 'invoicesPage')
        ->through(fn(Invoice $invoice) => [
          'cells' => [
            $this->formatDate($invoice->invoice_date),
            $invoice->invoice_code ?: 'N/A',
            str((string) $invoice->status)->replace('_', ' ')->title()->value(),
            number_format((float) $invoice->outstanding_amount, 2),
            $invoice->facility?->name ?? 'N/A',
          ],
        ]),
      'emptyMessage' => 'No invoices have been generated yet.',
      'invoice_outstanding_total' => number_format((float) (clone $baseQuery)->sum('outstanding_amount'), 2),
      'invoice_paid_total' => number_format((float) (clone $baseQuery)->sum('amount_paid'), 2),
    ];
  }

  private function referralsViewData(): array
  {
    $baseQuery = Referral::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->orderByDesc('referral_date')
      ->orderByDesc('id');

    return [
      'tableTitle' => 'Referral Trail',
      'tableDescription' => 'Every referral raised for this patient, including destination and completion details.',
      'tableIcon' => 'bx-transfer-alt',
      'tableColumns' => ['Referral Date', 'From', 'To', 'Follow-Up', 'Facility'],
      'tableRows' => (clone $baseQuery)
        ->paginate(self::PER_PAGE, ['*'], 'referralsPage')
        ->through(fn(Referral $referral) => [
          'cells' => [
            $this->formatDate($referral->referral_date),
            $referral->referred_from ?: 'N/A',
            $referral->referred_to ?: 'N/A',
            $referral->follow_up_needed ? 'Required' : 'Not Required',
            $referral->facility?->name ?? 'N/A',
          ],
        ]),
      'emptyMessage' => 'No referrals are available yet.',
      'referrals_completed' => (clone $baseQuery)->whereNotNull('date_completed')->count(),
      'referrals_follow_up_needed' => (clone $baseQuery)->where('follow_up_needed', true)->count(),
    ];
  }

  private function familyPlanningViewData(): array
  {
    $registrations = FamilyPlanningRegistration::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->get()
      ->map(fn(FamilyPlanningRegistration $record) => [
        'sort_date' => $record->registration_date,
        'cells' => [
          $this->formatDate($record->registration_date),
          'Registration',
          $record->contraceptive_selected ?: 'N/A',
          $this->formatDate($record->next_appointment),
          $record->facility?->name ?? 'N/A',
        ],
      ]);

    $followUps = FamilyPlanningFollowUp::query()
      ->with('facility')
      ->where('patient_id', $this->patient->id)
      ->get()
      ->map(fn(FamilyPlanningFollowUp $record) => [
        'sort_date' => $record->visit_date,
        'cells' => [
          $this->formatDate($record->visit_date),
          'Follow-Up',
          $record->method_supplied ?: 'N/A',
          $this->formatDate($record->next_appointment_date),
          $record->facility?->name ?? 'N/A',
        ],
      ]);

    $rows = $registrations
      ->merge($followUps)
      ->sortByDesc(fn(array $row) => $row['sort_date']?->timestamp ?? 0)
      ->values();

    return [
      'tableTitle' => 'Family Planning Records',
      'tableDescription' => 'Family planning registrations and follow-up visits tied to this patient.',
      'tableIcon' => 'bx-group',
      'tableColumns' => ['Visit Date', 'Record Type', 'Method', 'Next Appointment', 'Facility'],
      'tableRows' => $this->paginateCollection($rows, 'familyPlanningPage'),
      'emptyMessage' => 'No family planning records are available yet.',
      'family_planning_registrations_count' => $registrations->count(),
      'family_planning_follow_ups_count' => $followUps->count(),
    ];
  }

  private function healthInsuranceViewData(): array
  {
    return [
      'insurance_status' => $this->patient->is_nhis_subscriber ? 'Active Coverage' : 'Not Enrolled',
      'insurance_provider' => $this->patient->nhis_provider ?: 'N/A',
      'insurance_number' => $this->patient->nhis_number ?: 'N/A',
      'insurance_plan' => $this->patient->nhis_plan_type ?: 'N/A',
      'insurance_expiry' => $this->formatDate($this->patient->nhis_expiry_date),
      'insurance_principal_name' => $this->patient->nhis_principal_name ?: 'N/A',
      'insurance_principal_number' => $this->patient->nhis_principal_number ?: 'N/A',
      'insurance_history' => Activity::query()
        ->with('facility')
        ->where('patient_id', $this->patient->id)
        ->where('module', 'health_insurance')
        ->latest('created_at')
        ->latest('id')
        ->paginate(self::PER_PAGE, ['*'], 'insuranceHistoryPage'),
    ];
  }

  private function recentActivities(int $patientId): Collection
  {
    return Activity::query()
      ->where('patient_id', $patientId)
      ->latest('created_at')
      ->limit(6)
      ->get()
      ->map(function (Activity $activity): array {
        [$icon, $color] = match ($activity->module) {
          'anc', 'antenatal' => ['bx-plus-medical', 'primary'],
          'deliveries' => ['bx-baby-carriage', 'success'],
          'postnatal' => ['bx-heart', 'info'],
          'tetanus', 'anc_tetanus' => ['bx-shield-plus', 'warning'],
          'appointments' => ['bx-calendar-event', 'info'],
          'visits' => ['bx-walk', 'primary'],
          'doctor-assessment', 'assessments' => ['bx-user-check', 'success'],
          'laboratory' => ['bx-test-tube', 'warning'],
          'prescriptions' => ['bx-capsule', 'info'],
          'invoices' => ['bx-receipt', 'danger'],
          'referrals' => ['bx-transfer-alt', 'dark'],
          'reminders' => ['bx-bell', 'warning'],
          'family_planning' => ['bx-group', 'primary'],
          'health_insurance' => ['bx-shield-quarter', 'success'],
          default => ['bx-notepad', 'secondary'],
        };

        return [
          'title' => ucwords(str_replace(['_', '-'], ' ', (string) $activity->module)),
          'description' => $activity->description ?: 'Patient activity recorded.',
          'date' => $activity->created_at ?? now(),
          'icon' => $icon,
          'color' => $color,
        ];
      });
  }

  private function buildAppointmentRows(): Collection
  {
    $activationDates = DinActivation::query()
      ->where('patient_id', $this->patient->id)
      ->pluck('visit_date')
      ->filter()
      ->map(fn($date) => Carbon::parse($date)->startOfDay())
      ->sort()
      ->values();

    $rows = collect();

    DoctorAssessment::query()
      ->where('patient_id', $this->patient->id)
      ->whereNotNull('next_appointment_date')
      ->get(['next_appointment_date', 'final_diagnosis'])
      ->each(function (DoctorAssessment $record) use ($rows, $activationDates): void {
        $appointmentDate = Carbon::parse($record->next_appointment_date)->startOfDay();
        $status = $this->resolveAppointmentStatus($activationDates, $appointmentDate);
        $rows->push([
          'sort_date' => $appointmentDate,
          'status' => $status,
          'cells' => [$appointmentDate->format('M d, Y'), 'Doctor Follow-Up', 'Doctor Assessment', $status, $record->final_diagnosis ?: 'N/A'],
        ]);
      });

    TetanusVaccination::query()
      ->where('patient_id', $this->patient->id)
      ->whereNotNull('next_appointment_date')
      ->get(['next_appointment_date', 'current_tt_dose'])
      ->each(function (TetanusVaccination $record) use ($rows, $activationDates): void {
        $appointmentDate = Carbon::parse($record->next_appointment_date)->startOfDay();
        $status = $this->resolveAppointmentStatus($activationDates, $appointmentDate);
        $rows->push([
          'sort_date' => $appointmentDate,
          'status' => $status,
          'cells' => [$appointmentDate->format('M d, Y'), 'TT Vaccination', 'ANC Tetanus', $status, $record->current_tt_dose ?: 'N/A'],
        ]);
      });

    AntenatalFollowUpAssessment::query()
      ->where('patient_id', $this->patient->id)
      ->whereNotNull('next_return_date')
      ->get(['next_return_date', 'clinical_remarks'])
      ->each(function (AntenatalFollowUpAssessment $record) use ($rows, $activationDates): void {
        $appointmentDate = Carbon::parse($record->next_return_date)->startOfDay();
        $status = $this->resolveAppointmentStatus($activationDates, $appointmentDate);
        $rows->push([
          'sort_date' => $appointmentDate,
          'status' => $status,
          'cells' => [$appointmentDate->format('M d, Y'), 'ANC Follow-Up', 'ANC Follow-Up Assessment', $status, $record->clinical_remarks ?: 'N/A'],
        ]);
      });

    FamilyPlanningFollowUp::query()
      ->where('patient_id', $this->patient->id)
      ->whereNotNull('next_appointment_date')
      ->get(['next_appointment_date', 'method_supplied'])
      ->each(function (FamilyPlanningFollowUp $record) use ($rows, $activationDates): void {
        $appointmentDate = Carbon::parse($record->next_appointment_date)->startOfDay();
        $status = $this->resolveAppointmentStatus($activationDates, $appointmentDate);
        $rows->push([
          'sort_date' => $appointmentDate,
          'status' => $status,
          'cells' => [$appointmentDate->format('M d, Y'), 'Family Planning Follow-Up', 'Family Planning Follow-Up', $status, $record->method_supplied ?: 'N/A'],
        ]);
      });

    FamilyPlanningRegistration::query()
      ->where('patient_id', $this->patient->id)
      ->whereNotNull('next_appointment')
      ->get(['next_appointment', 'contraceptive_selected'])
      ->each(function (FamilyPlanningRegistration $record) use ($rows, $activationDates): void {
        $appointmentDate = Carbon::parse($record->next_appointment)->startOfDay();
        $status = $this->resolveAppointmentStatus($activationDates, $appointmentDate);
        $rows->push([
          'sort_date' => $appointmentDate,
          'status' => $status,
          'cells' => [$appointmentDate->format('M d, Y'), 'Family Planning Registration', 'Family Planning Registration', $status, $record->contraceptive_selected ?: 'N/A'],
        ]);
      });

    return $rows->sortByDesc(fn(array $row) => $row['sort_date']?->timestamp ?? 0)->values();
  }

  private function resolveAppointmentStatus(Collection $activationDates, Carbon $appointmentDate): string
  {
    $fulfilled = $activationDates->contains(
      fn(Carbon $visitDate) => $visitDate->greaterThanOrEqualTo($appointmentDate)
    );

    if ($fulfilled) {
      return 'Fulfilled';
    }

    return $appointmentDate->isFuture() || $appointmentDate->isToday() ? 'Upcoming' : 'Missed';
  }

  private function completedTetanusDoses(iterable $records): int
  {
    $doses = collect($records)
      ->pluck('current_tt_dose')
      ->filter()
      ->map(fn($dose) => strtoupper((string) $dose))
      ->unique();

    return min(5, $doses->count());
  }

  private function buildTetanusProtectionStatus(iterable $records): array
  {
    $dosesCompleted = $this->completedTetanusDoses($records);

    return match (true) {
      $dosesCompleted >= 5 => ['status' => 'Fully Protected', 'description' => 'You have completed all tetanus doses.', 'percentage' => 100, 'color' => 'success'],
      $dosesCompleted >= 3 => ['status' => 'Protected', 'description' => 'You have substantial tetanus protection.', 'percentage' => $dosesCompleted * 20, 'color' => 'info'],
      $dosesCompleted >= 1 => ['status' => 'Partially Protected', 'description' => 'More tetanus doses are still needed.', 'percentage' => $dosesCompleted * 20, 'color' => 'warning'],
      default => ['status' => 'Not Protected', 'description' => 'No tetanus vaccination has been recorded yet.', 'percentage' => 0, 'color' => 'danger'],
    };
  }

  private function sectionDefinitions(): array
  {
    // Keep patient portal routing and labels in one place so new sections stay consistent.
    return [
      'dashboard' => ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'patient-dashboard', 'icon' => 'bx-grid-alt', 'heading' => 'Patient Dashboard', 'description' => 'Overview of your records and care activities.'],
      'profile' => ['key' => 'profile', 'label' => 'Profile', 'route' => 'patient-profile', 'icon' => 'bx-user', 'heading' => 'My Profile', 'description' => 'Your account and profile details.'],
      'attendance' => ['key' => 'attendance', 'label' => 'Attendance', 'route' => 'patient-attendance', 'icon' => 'bx-time-five', 'heading' => 'Attendance History', 'description' => 'Every patient check-in linked to your DIN.'],
      'activities' => ['key' => 'activities', 'label' => 'Activities', 'route' => 'patient-activities', 'icon' => 'bx-pulse', 'heading' => 'Activity Log', 'description' => 'A unified timeline of your care activities.'],
      'appointments' => ['key' => 'appointments', 'label' => 'Appointments', 'route' => 'patient-appointments', 'icon' => 'bx-calendar-event', 'heading' => 'Appointments', 'description' => 'Upcoming, fulfilled, and missed appointments.'],
      'visits' => ['key' => 'visits', 'label' => 'Visits', 'route' => 'patient-visits', 'icon' => 'bx-walk', 'heading' => 'Visit History', 'description' => 'Collated visits across your patient workspaces.'],
      'antenatal' => ['key' => 'antenatal', 'label' => 'Antenatal', 'route' => 'patient-antenatal', 'icon' => 'bx-plus-medical', 'heading' => 'Antenatal Records', 'description' => 'Your antenatal records and follow-up details.'],
      'deliveries' => ['key' => 'deliveries', 'label' => 'Deliveries', 'route' => 'patient-deliveries', 'icon' => 'bx-baby-carriage', 'heading' => 'My Deliveries', 'description' => 'Your delivery history and outcomes.'],
      'postnatal' => ['key' => 'postnatal', 'label' => 'Postnatal', 'route' => 'patient-postnatal', 'icon' => 'bx-heart', 'heading' => 'Postnatal Care', 'description' => 'Your postnatal follow-up records.'],
      'tetanus' => ['key' => 'tetanus', 'label' => 'Tetanus', 'route' => 'patient-tetanus', 'icon' => 'bx-shield-plus', 'heading' => 'Tetanus Records', 'description' => 'Your tetanus vaccination records.'],
      'assessments' => ['key' => 'assessments', 'label' => 'Assessments', 'route' => 'patient-assessments', 'icon' => 'bx-user-check', 'heading' => 'Clinical Assessments', 'description' => 'Doctor assessments and clinical notes tied to your record.'],
      'reminders' => ['key' => 'reminders', 'label' => 'Reminders', 'route' => 'patient-reminders', 'icon' => 'bx-bell', 'heading' => 'Reminders', 'description' => 'Your reminders and scheduled follow-up notices.'],
      'laboratory' => ['key' => 'laboratory', 'label' => 'Laboratory', 'route' => 'patient-laboratory', 'icon' => 'bx-test-tube', 'heading' => 'Laboratory Requests', 'description' => 'Lab requests and completion progress linked to your visits.'],
      'prescriptions' => ['key' => 'prescriptions', 'label' => 'Prescriptions', 'route' => 'patient-prescriptions', 'icon' => 'bx-capsule', 'heading' => 'Prescriptions', 'description' => 'Your prescriptions and dispense status.'],
      'invoices' => ['key' => 'invoices', 'label' => 'Invoices', 'route' => 'patient-invoices', 'icon' => 'bx-receipt', 'heading' => 'Invoices', 'description' => 'Billing history and outstanding balances.'],
      'referrals' => ['key' => 'referrals', 'label' => 'Referrals', 'route' => 'patient-referrals', 'icon' => 'bx-transfer-alt', 'heading' => 'Referrals', 'description' => 'Referrals raised for your care journey.'],
      'family-planning' => ['key' => 'family-planning', 'label' => 'Family Planning', 'route' => 'patient-family-planning', 'icon' => 'bx-group', 'heading' => 'Family Planning Records', 'description' => 'Registrations and follow-up visits for family planning.'],
      'health-insurance' => ['key' => 'health-insurance', 'label' => 'Insurance', 'route' => 'patient-health-insurance', 'icon' => 'bx-shield-quarter', 'heading' => 'Health Insurance', 'description' => 'Your current insurance profile and change history.'],
    ];
  }

  private function contentViewForSection(): string
  {
    return match ($this->section) {
      'dashboard' => 'livewire.patient.patient-dashboard',
      'profile' => 'livewire.patient.patient-profile',
      'antenatal' => 'livewire.patient.patient-antenatal',
      'deliveries' => 'livewire.patient.patient-deliveries',
      'postnatal' => 'livewire.patient.patient-postnatal',
      'tetanus' => 'livewire.patient.patient-tetanus',
      'health-insurance' => 'livewire.patient.patient-health-insurance',
      default => 'livewire.patient.patient-records-table',
    };
  }

  private function paginateCollection(Collection $rows, string $pageName): LengthAwarePaginator
  {
    // Some patient portal sections are assembled from multiple modules, so we paginate the merged collection.
    $page = Paginator::resolveCurrentPage($pageName);
    $items = $rows->forPage($page, self::PER_PAGE)->values();

    return new LengthAwarePaginator($items, $rows->count(), self::PER_PAGE, $page, [
      'pageName' => $pageName,
      'path' => Paginator::resolveCurrentPath(),
    ]);
  }

  private function formatDate($value): string
  {
    if (!$value) {
      return 'N/A';
    }

    try {
      return $value instanceof Carbon ? $value->format('M d, Y') : Carbon::parse($value)->format('M d, Y');
    } catch (\Throwable $e) {
      return 'N/A';
    }
  }

  private function formatDateTime($value, string $format): string
  {
    if (!$value) {
      return 'N/A';
    }

    try {
      return $value instanceof Carbon ? $value->format($format) : Carbon::parse($value)->format($format);
    } catch (\Throwable $e) {
      return (string) $value;
    }
  }
}
