<?php

namespace App\Livewire\Core;

use App\Models\Activity;
use App\Models\Bed;
use App\Models\BedSection;
use App\Models\Facility;
use App\Models\InpatientAdmission;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AdmittedPatients extends Component
{
  public $facility_id;
  public $facility_name;
  public $officer_name;

  public $patient_search = '';
  public $admission_patient_id;
  public $selected_patient_display;
  public $bed_section_id;
  public $bed_id;
  public $admitted_at;
  public $admission_reason;

  public $close_admission_id;
  public $close_action;
  public $close_at;
  public $close_note;
  public $close_referral_destination;

  protected function admissionRules(): array
  {
    return [
      'admission_patient_id' => 'required|integer|exists:patients,id',
      'bed_section_id' => [
        'required',
        Rule::exists('bed_sections', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'bed_id' => [
        'required',
        Rule::exists('beds', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'admitted_at' => 'required|date',
      'admission_reason' => 'nullable|string|max:2000',
    ];
  }

  protected function closeRules(): array
  {
    return [
      'close_admission_id' => 'required|integer|exists:inpatient_admissions,id',
      'close_action' => ['required', Rule::in([InpatientAdmission::STATUS_DISCHARGED, InpatientAdmission::STATUS_REFERRED])],
      'close_at' => 'required|date',
      'close_note' => 'nullable|string|max:2000',
      'close_referral_destination' => $this->close_action === InpatientAdmission::STATUS_REFERRED
        ? 'required|string|max:255'
        : 'nullable|string|max:255',
    ];
  }

  protected $messages = [
    'admission_patient_id.required' => 'Select a patient to admit.',
    'bed_section_id.required' => 'Select section.',
    'bed_id.required' => 'Select bed.',
    'close_referral_destination.required' => 'Referral destination is required for referred-out patients.',
  ];

  public function mount(): void
  {
    $admin = Auth::user();
    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }

    $this->facility_id = (int) $admin->facility_id;
    $this->facility_name = Facility::find($this->facility_id)?->name ?? 'Unknown Facility';
    $this->officer_name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) ?: ($admin->full_name ?? 'Facility Admin');

    $this->admitted_at = now()->format('Y-m-d\TH:i');
    $this->close_at = now()->format('Y-m-d\TH:i');
  }

  public function updatedPatientSearch(): void
  {
    $this->admission_patient_id = null;
    $this->selected_patient_display = null;
  }

  public function updatedBedSectionId(): void
  {
    $this->bed_id = null;
  }

  public function getPatientSearchResultsProperty()
  {
    $query = trim((string) $this->patient_search);
    if (strlen($query) < 2) {
      return collect();
    }

    return Patient::query()
      ->where(function ($q) use ($query) {
        $q->where('din', 'like', "%{$query}%")
          ->orWhere('phone', 'like', "%{$query}%")
          ->orWhere('first_name', 'like', "%{$query}%")
          ->orWhere('last_name', 'like', "%{$query}%")
          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
      })
      ->where('is_active', true)
      ->limit(12)
      ->get(['id', 'din', 'first_name', 'last_name', 'phone', 'facility_id']);
  }

  public function getAvailableBedsProperty()
  {
    if (!$this->bed_section_id) {
      return collect();
    }

    return Bed::query()
      ->forFacility($this->facility_id)
      ->where('bed_section_id', (int) $this->bed_section_id)
      ->where('is_active', true)
      ->where('status', 'available')
      ->orderBy('bed_code')
      ->get(['id', 'bed_code', 'room_label']);
  }

  public function selectPatient(int $patientId): void
  {
    $patient = Patient::query()->find($patientId);
    if (!$patient) {
      toastr()->error('Patient not found.');
      return;
    }

    $this->admission_patient_id = $patient->id;
    $this->selected_patient_display = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) . ' | DIN: ' . ($patient->din ?: 'N/A');
    $this->patient_search = '';
  }

  public function clearSelectedPatient(): void
  {
    $this->admission_patient_id = null;
    $this->selected_patient_display = null;
    $this->patient_search = '';
  }

  public function admitPatient(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->admissionRules());

      $alreadyAdmitted = InpatientAdmission::query()
        ->forFacility($this->facility_id)
        ->where('patient_id', (int) $this->admission_patient_id)
        ->where('is_active', true)
        ->exists();

      if ($alreadyAdmitted) {
        toastr()->warning('This patient already has an active admission in this facility.');
        DB::rollBack();
        return;
      }

      $bed = Bed::query()
        ->forFacility($this->facility_id)
        ->where('id', (int) $this->bed_id)
        ->lockForUpdate()
        ->first();

      if (!$bed) {
        toastr()->error('Selected bed not found.');
        DB::rollBack();
        return;
      }

      if ((int) $bed->bed_section_id !== (int) $this->bed_section_id) {
        toastr()->error('Selected bed does not belong to the chosen section.');
        DB::rollBack();
        return;
      }

      if (!$bed->is_active || $bed->status !== 'available') {
        toastr()->warning('Selected bed is not available.');
        DB::rollBack();
        return;
      }

      $bedHasActiveAdmission = InpatientAdmission::query()
        ->forFacility($this->facility_id)
        ->where('bed_id', $bed->id)
        ->where('is_active', true)
        ->exists();

      if ($bedHasActiveAdmission) {
        toastr()->warning('Selected bed already has an active admission.');
        DB::rollBack();
        return;
      }

      $section = BedSection::query()
        ->forFacility($this->facility_id)
        ->find((int) $this->bed_section_id);

      $admission = InpatientAdmission::create([
        'facility_id' => $this->facility_id,
        'patient_id' => (int) $this->admission_patient_id,
        'bed_section_id' => (int) $this->bed_section_id,
        'bed_id' => (int) $bed->id,
        'admission_code' => $this->generateAdmissionCode(),
        'admitted_at' => Carbon::parse($this->admitted_at),
        'admitted_by' => $this->officer_name,
        'admission_reason' => trim((string) $this->admission_reason) ?: null,
        'status' => InpatientAdmission::STATUS_ADMITTED,
        'is_active' => true,
      ]);

      $bed->update([
        'bed_section_id' => (int) $this->bed_section_id,
        'ward_section' => $section?->name ?? $bed->ward_section,
        'status' => 'occupied',
        'occupied_by_patient_id' => (int) $this->admission_patient_id,
        'occupied_since' => Carbon::parse($this->admitted_at),
        'last_status_changed_at' => now(),
      ]);

      Activity::create([
        'patient_id' => (int) $this->admission_patient_id,
        'facility_id' => $this->facility_id,
        'module' => 'inpatient',
        'action' => 'admit',
        'description' => "Inpatient admission created ({$admission->admission_code}) on bed {$bed->bed_code}.",
        'performed_by' => $this->officer_name,
        'meta' => [
          'admission_id' => $admission->id,
          'admission_code' => $admission->admission_code,
          'bed_id' => $bed->id,
          'bed_code' => $bed->bed_code,
          'section_id' => $section?->id,
          'section_name' => $section?->name,
        ],
      ]);

      DB::commit();
      toastr()->success("Patient admitted successfully on bed {$bed->bed_code}.");
      $this->resetAdmissionForm();
      $this->refreshPageSoon(1500);
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to admit patient.');
    }
  }

  public function startClose(int $admissionId, string $action): void
  {
    if (!in_array($action, [InpatientAdmission::STATUS_DISCHARGED, InpatientAdmission::STATUS_REFERRED], true)) {
      toastr()->error('Invalid close action.');
      return;
    }

    $exists = InpatientAdmission::query()
      ->forFacility($this->facility_id)
      ->whereKey($admissionId)
      ->where('is_active', true)
      ->exists();

    if (!$exists) {
      toastr()->warning('Active admission not found.');
      return;
    }

    $this->close_admission_id = $admissionId;
    $this->close_action = $action;
    $this->close_at = now()->format('Y-m-d\TH:i');
    $this->close_note = null;
    $this->close_referral_destination = null;
    $this->dispatch('open-close-admission-modal');
  }

  public function cancelClose(): void
  {
    $this->resetCloseForm();
  }

  public function closeCloseModal(): void
  {
    $this->resetCloseForm();
    $this->dispatch('close-close-admission-modal');
  }

  public function completeCloseAdmission(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->closeRules());

      $admission = InpatientAdmission::query()
        ->forFacility($this->facility_id)
        ->whereKey((int) $this->close_admission_id)
        ->where('is_active', true)
        ->lockForUpdate()
        ->first();

      if (!$admission) {
        toastr()->warning('Active admission not found.');
        DB::rollBack();
        return;
      }

      $closeAt = Carbon::parse($this->close_at);
      if ($closeAt->lt($admission->admitted_at)) {
        toastr()->error('Close date/time cannot be earlier than admission date/time.');
        DB::rollBack();
        return;
      }

      $bed = Bed::query()
        ->forFacility($this->facility_id)
        ->whereKey((int) $admission->bed_id)
        ->lockForUpdate()
        ->first();

      $admission->update([
        'status' => $this->close_action,
        'is_active' => false,
        'discharged_at' => $closeAt,
        'discharged_by' => $this->officer_name,
        'discharge_note' => trim((string) $this->close_note) ?: null,
        'referral_destination' => $this->close_action === InpatientAdmission::STATUS_REFERRED
          ? trim((string) $this->close_referral_destination)
          : null,
      ]);

      if ($bed) {
        $bed->update([
          'status' => 'available',
          'occupied_by_patient_id' => null,
          'occupied_since' => null,
          'last_status_changed_at' => now(),
        ]);
      }

      $action = $this->close_action === InpatientAdmission::STATUS_REFERRED ? 'refer' : 'discharge';
      $description = $this->close_action === InpatientAdmission::STATUS_REFERRED
        ? "Inpatient admission referred out ({$admission->admission_code})."
        : "Inpatient admission discharged ({$admission->admission_code}).";

      Activity::create([
        'patient_id' => (int) $admission->patient_id,
        'facility_id' => $this->facility_id,
        'module' => 'inpatient',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => [
          'admission_id' => $admission->id,
          'admission_code' => $admission->admission_code,
          'bed_id' => $admission->bed_id,
          'close_action' => $this->close_action,
          'referral_destination' => $admission->referral_destination,
        ],
      ]);

      DB::commit();
      toastr()->success($this->close_action === InpatientAdmission::STATUS_REFERRED ? 'Patient referred out successfully.' : 'Patient discharged successfully.');
      $this->resetCloseForm();
      $this->dispatch('close-close-admission-modal');
      $this->refreshPageSoon(1500);
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to update admission status.');
    }
  }

  private function generateAdmissionCode(): string
  {
    for ($attempt = 0; $attempt < 20; $attempt++) {
      $code = 'ADM-' . $this->facility_id . '-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
      $exists = InpatientAdmission::query()->where('admission_code', $code)->exists();
      if (!$exists) {
        return $code;
      }
    }

    return 'ADM-' . $this->facility_id . '-' . now()->format('YmdHis') . '-' . random_int(10000, 99999);
  }

  private function resetAdmissionForm(): void
  {
    $this->reset([
      'patient_search',
      'admission_patient_id',
      'selected_patient_display',
      'bed_section_id',
      'bed_id',
      'admission_reason',
    ]);
    $this->admitted_at = now()->format('Y-m-d\TH:i');
  }

  private function resetCloseForm(): void
  {
    $this->reset([
      'close_admission_id',
      'close_action',
      'close_note',
      'close_referral_destination',
    ]);
    $this->close_at = now()->format('Y-m-d\TH:i');
  }

  private function refreshPageSoon(int $delayMs = 1500): void
  {
    $this->js("setTimeout(() => window.location.reload(), {$delayMs})");
  }

  public function render()
  {
    $sections = BedSection::query()
      ->forFacility($this->facility_id)
      ->where('is_active', true)
      ->orderBy('name')
      ->get(['id', 'name']);

    $activeAdmissions = InpatientAdmission::query()
      ->forFacility($this->facility_id)
      ->with(['patient:id,din,first_name,last_name,phone', 'bed:id,bed_code,room_label', 'section:id,name'])
      ->where('is_active', true)
      ->orderByDesc('admitted_at')
      ->get();

    $historyAdmissions = InpatientAdmission::query()
      ->forFacility($this->facility_id)
      ->with(['patient:id,din,first_name,last_name,phone', 'bed:id,bed_code,room_label', 'section:id,name'])
      ->where('is_active', false)
      ->orderByDesc('discharged_at')
      ->limit(500)
      ->get();

    $todayStart = now()->startOfDay();
    $todayAdmissionsCount = InpatientAdmission::query()
      ->forFacility($this->facility_id)
      ->where('admitted_at', '>=', $todayStart)
      ->count();

    $todayDischargesCount = InpatientAdmission::query()
      ->forFacility($this->facility_id)
      ->whereNotNull('discharged_at')
      ->where('discharged_at', '>=', $todayStart)
      ->count();

    $bedTotals = Bed::query()
      ->forFacility($this->facility_id)
      ->where('is_active', true)
      ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='occupied' THEN 1 ELSE 0 END) as occupied")
      ->first();

    $totalBeds = (int) ($bedTotals->total ?? 0);
    $occupiedBeds = (int) ($bedTotals->occupied ?? 0);
    $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

    $summary = [
      'active' => $activeAdmissions->count(),
      'today_admissions' => $todayAdmissionsCount,
      'today_discharges' => $todayDischargesCount,
      'occupancy_rate' => $occupancyRate,
    ];

    $selectedCloseAdmission = null;
    if ($this->close_admission_id) {
      $selectedCloseAdmission = $activeAdmissions->firstWhere('id', (int) $this->close_admission_id);
    }

    return view('livewire.core.admitted-patients', [
      'sections' => $sections,
      'availableBeds' => $this->availableBeds,
      'patientSearchResults' => $this->patientSearchResults,
      'activeAdmissions' => $activeAdmissions,
      'historyAdmissions' => $historyAdmissions,
      'selectedCloseAdmission' => $selectedCloseAdmission,
      'summary' => $summary,
    ])->layout('layouts.facilityAdminLayout');
  }
}
