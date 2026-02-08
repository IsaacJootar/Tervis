<?php

namespace App\Livewire\Workspaces\Modules\ANC;

use Exception;
use Carbon\Carbon;
use App\Models\Lga;
use App\Models\Patient;
use App\Models\Ward;
use App\Models\State;
use Livewire\Component;
use App\Models\Delivery;
use App\Models\Facility;
use App\Models\Antenatal;
use App\Models\LinkedChild;
use App\Models\Registrations\DinActivation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class Deliveries extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;
  public $month_year, $cl_date, $cl_sex, $toc, $seeking_care, $transportation, $parity, $dodel, $cl_phone, $mod, $partograph, $oxytocin, $misoprostol;
  public $alive, $admitted, $discharged, $referred_out, $pac, $mother_transportation, $dead, $MDA_conducted, $MDA_not_conducted;
  public $abortion, $time_of_delivery, $pre_term, $breathing, $weight, $still_birth, $baby_dead, $live_births, $baby_sex, $took_delivery, $doctor, $newborn_care, $clamped, $CKX_gel, $breast, $temperature, $breastfeeding, $postpartum, $took_del;
  public $lmp, $edd, $pregnancy_number;
  public $delivery_id, $modal_flag = false;
  public $states, $lgas, $wards;
  public $babies = [];

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'state_id' => 'required|exists:states,id',
    'lga_id' => 'nullable|exists:lgas,id',
    'ward_id' => 'nullable|exists:wards,id',
    'month_year' => 'required|date',
    'cl_date' => 'nullable|date',
    'cl_sex' => 'required|in:11 - 14 years,15 - 19 years,20 - 24 years,25 - 29 years,30 - 34 years,35 - 49 years,50 + years',
    'toc' => 'required|in:Booked,Unbooked',
    'seeking_care' => 'required|in:less24,more24',
    'transportation' => 'required|in:vehicle,ambulance,others',
    'parity' => 'nullable|string|max:255',
    'dodel' => 'required|date',
    'cl_phone' => 'nullable|string|max:20',
    'mod' => 'required|in:SVD,CS,AD',
    'partograph' => 'required|in:yes,no',
    'oxytocin' => 'required|in:yes,no',
    'misoprostol' => 'required|in:yes,no',
    'alive' => 'nullable|string|max:255',
    'admitted' => 'nullable|string|max:255',
    'discharged' => 'nullable|string|max:255',
    'referred_out' => 'nullable|string|max:255',
    'pac' => 'nullable|string|max:255',
    'mother_transportation' => 'required|in:vehicle,ambulance,others',
    'dead' => 'nullable|string|max:255',
    'MDA_conducted' => 'nullable|string|max:255',
    'MDA_not_conducted' => 'nullable|string|max:255',
    'abortion' => 'nullable|string|max:255',
    'time_of_delivery' => 'required',
    'pre_term' => 'nullable|string|max:255',
    'breathing' => 'nullable|string|max:255',
    'weight' => 'required|numeric|min:0.5|max:6',
    'still_birth' => 'nullable|in:fresh,macerated',
    'baby_dead' => 'nullable|string|max:255',
    'live_births' => 'nullable|string|max:255',
    'baby_sex' => 'required|in:Male,Female',
    'took_delivery' => 'nullable|string|max:255',
    'doctor' => 'nullable|string|max:255',
    'newborn_care' => 'nullable|string|max:255',
    'clamped' => 'nullable|string|max:255',
    'CKX_gel' => 'nullable|string|max:255',
    'breast' => 'nullable|string|max:255',
    'temperature' => 'nullable|numeric|min:30|max:40',
    'breastfeeding' => 'required|in:counseled,accepted',
    'postpartum' => 'required|in:counseled,accepted',
    'took_del' => 'nullable|string|max:255',
    'officer_name' => 'required|string|max:255',
    'officer_role' => 'required|string|max:255',
    'officer_designation' => 'required|string|max:255',
    'babies' => 'required|array|min:1',
    'babies.*.first_name' => 'nullable|string|max:255',
    'babies.*.last_name' => 'nullable|string|max:255',
    'babies.*.gender' => 'required|in:Male,Female',
    'babies.*.birth_weight' => 'nullable|numeric|min:0.5|max:6',
    'babies.*.birth_order' => 'nullable|integer|min:1|max:10',
  ];

  public function mount($patientId)
  {
    $this->patientId = $patientId;
    $user = Auth::user();
    if (!$user || $user->role !== 'Data Officer') {
      abort(403, 'Unauthorized: Only Data Officers can access this page.');
    }

    $facility = Facility::find($user->facility_id);
    if (!$facility) {
      abort(403, 'Invalid facility assignment.');
    }

    $this->facility_id = $facility->id;
    $this->facility_name = $facility->name;
    $this->state_id = State::where('name', $facility->state)->first()->id ?? null;
    $this->state_name = $facility->state;
    $this->lga_id = Lga::where('name', $facility->lga)->where('state_id', $this->state_id)->first()->id ?? null;
    $this->lga_name = $facility->lga;
    $this->ward_id = Ward::where('name', $facility->ward)->where('lga_id', $this->lga_id)->first()->id ?? null;
    $this->ward_name = $facility->ward;
    $this->month_year = Carbon::now()->format('Y-m-d');
    $this->officer_name = $user->first_name . ' ' . $user->last_name;
    $this->officer_role = $user->role;
    $this->officer_designation = $user->designation;

    try {
      $this->states = Cache::remember('states', 60 * 60 * 24, function () {
        return State::orderBy('name')->get();
      });
      $this->lgas = Cache::remember('lgas_state_' . $this->state_id, 60 * 60 * 24, function () {
        return Lga::where('state_id', $this->state_id)->orderBy('name')->get();
      });
      $this->wards = Cache::remember('wards_lga_' . $this->lga_id, 60 * 60 * 24, function () {
        return Ward::where('lga_id', $this->lga_id)->orderBy('name')->get();
      });
    } catch (Exception $e) {
      $this->states = collect();
      $this->lgas = collect();
      $this->wards = collect();
    }

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
    }
  }

  public function updatedStateId($value)
  {
    $this->lgas = Cache::remember('lgas_state_' . $value, 60 * 60 * 24, function () use ($value) {
      return Lga::where('state_id', $value)->orderBy('name')->get();
    });
    $this->lga_id = null;
    $this->ward_id = null;
    $this->wards = collect();
  }

  public function updatedLgaId($value)
  {
    $this->wards = Cache::remember('wards_lga_' . $value, 60 * 60 * 24, function () use ($value) {
      return Ward::where('lga_id', $value)->orderBy('name')->get();
    });
    $this->ward_id = null;
  }

  public function updatedDodel($value)
  {
    $this->autoFillDeliveryMonth();
  }

  public function addBaby(): void
  {
    $this->babies[] = [
      'first_name' => '',
      'last_name' => $this->last_name,
      'gender' => null,
      'birth_weight' => null,
      'birth_order' => count($this->babies) + 1,
    ];
  }

  public function removeBaby(int $index): void
  {
    if (count($this->babies) <= 1) {
      return;
    }

    unset($this->babies[$index]);
    $this->babies = array_values($this->babies);

    foreach ($this->babies as $i => $baby) {
      $this->babies[$i]['birth_order'] = $i + 1;
    }
  }

  private function validatePatientAccess()
  {
    $patient = Patient::with(['facility', 'activeAntenatalRegistration'])->find($this->patientId);

    if (!$patient) {
      $this->hasAccess = false;
      $this->accessError = 'Patient not found in the system.';
      return;
    }

    $todaysActivation = DinActivation::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->first();

    if (!$todaysActivation) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has not been checked in today. Please activate DIN first.';
      return;
    }

    if (!$patient->activeAntenatalRegistration) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has no active pregnancy. Please register for ANC first.';
      return;
    }

    $this->hasAccess = true;
    $this->patient = $patient;
    $this->activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
  }

  private function loadPatientData()
  {
    if (!$this->patient) return;

    $this->patient_din = $this->patient->din;
    $this->first_name = $this->patient->first_name;
    $this->last_name = $this->patient->last_name;
    $this->middle_name = $this->patient->middle_name;
    $this->patient_phone = $this->patient->phone;
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;
    $this->cl_sex = $this->getAgeRangeFromAge($this->patient_age) ?? $this->patient->age_group;
    $this->cl_phone = $this->patient->phone ?? null;
    $activeAnc = $this->patient->activeAntenatalRegistration;
      if ($activeAnc) {
        $this->lmp = $activeAnc->lmp?->format('Y-m-d');
        $this->edd = $activeAnc->edd?->format('Y-m-d');
        $this->pregnancy_number = $activeAnc->pregnancy_number;
      }

    if (empty($this->babies)) {
      $this->babies = [[
        'first_name' => '',
        'last_name' => $this->last_name,
        'gender' => $this->baby_sex,
        'birth_weight' => $this->weight,
        'birth_order' => 1,
      ]];
    }
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      $this->autoFillDeliveryMonth();
      $this->hydrateDeliveryBabyFields();
      $this->validate();

      $existingDelivery = Delivery::where('patient_id', $this->patientId)
        ->whereDate('dodel', Carbon::parse($this->dodel)->format('Y-m-d'))
        ->first();

      if ($existingDelivery) {
        DB::rollBack();
        toastr()->error('Delivery record for this patient on this date already exists.');
        return;
      }

        $data = array_diff_key($this->all(), [
          'patientId' => '',
          'patient' => '',
        'patient_din' => '',
        'middle_name' => '',
        'patient_phone' => '',
        'patient_dob' => '',
        'patient_age' => '',
        'patient_gender' => '',
        'state_name' => '',
        'lga_name' => '',
        'ward_name' => '',
        'facility_name' => '',
        'states' => '',
        'lgas' => '',
        'wards' => '',
        'hasAccess' => '',
          'accessError' => '',
          'activation_time' => '',
          'babies' => ''
        ]);

        $data['patient_id'] = $this->patientId;
        $delivery = Delivery::create($data);
        $this->syncLinkedChildFromDelivery($delivery);

      DB::commit();
      toastr()->success('Delivery record created successfully.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error occurred while creating the delivery record.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the delivery record.');
      throw $e;
    }
  }

  public function edit($id)
  {
    DB::beginTransaction();
    try {
      $delivery = Delivery::findOrFail($id);
      $this->delivery_id = $id;
      $this->first_name = $this->patient->first_name ?? 'N/A';
      $this->last_name = $this->patient->last_name ?? 'N/A';
      $this->facility_id = $delivery->facility_id;
      $this->facility_name = Facility::find($delivery->facility_id)->name ?? 'N/A';
      $this->state_id = $delivery->state_id;
      $this->state_name = State::find($delivery->state_id)->name ?? 'N/A';
      $this->lga_id = $delivery->lga_id;
      $this->lga_name = Lga::find($delivery->lga_id)->name ?? 'N/A';
      $this->ward_id = $delivery->ward_id;
      $this->ward_name = Ward::find($delivery->ward_id)->name ?? 'N/A';
      $this->month_year = $delivery->month_year ? Carbon::parse($delivery->month_year)->format('Y-m-d') : null;
      $this->cl_date = $delivery->cl_date;
      $this->cl_sex = $delivery->cl_sex;
      $this->toc = $delivery->toc;
      $this->seeking_care = $delivery->seeking_care;
      $this->transportation = $delivery->transportation;
      $this->parity = $delivery->parity;
      $this->dodel = $delivery->dodel;
      $this->cl_phone = $delivery->cl_phone;
      $this->mod = $delivery->mod;
      $this->partograph = $delivery->partograph;
      $this->oxytocin = $delivery->oxytocin;
      $this->misoprostol = $delivery->misoprostol;
      $this->alive = $delivery->alive;
      $this->admitted = $delivery->admitted;
      $this->discharged = $delivery->discharged;
      $this->referred_out = $delivery->referred_out;
      $this->pac = $delivery->pac;
      $this->mother_transportation = $delivery->mother_transportation;
      $this->dead = $delivery->dead;
      $this->MDA_conducted = $delivery->MDA_conducted;
      $this->MDA_not_conducted = $delivery->MDA_not_conducted;
      $this->abortion = $delivery->abortion;
      $this->time_of_delivery = $delivery->time_of_delivery;
      $this->pre_term = $delivery->pre_term;
      $this->breathing = $delivery->breathing;
      $this->weight = $delivery->weight;
      $this->still_birth = $delivery->still_birth;
      $this->baby_dead = $delivery->baby_dead;
      $this->live_births = $delivery->live_births;
      $this->baby_sex = $delivery->baby_sex;
      $this->took_delivery = $delivery->took_delivery;
      $this->doctor = $delivery->doctor;
      $this->newborn_care = $delivery->newborn_care;
      $this->clamped = $delivery->clamped;
      $this->CKX_gel = $delivery->CKX_gel;
      $this->breast = $delivery->breast;
      $this->temperature = $delivery->temperature;
      $this->breastfeeding = $delivery->breastfeeding;
      $this->postpartum = $delivery->postpartum;
      $this->took_del = $delivery->took_del;
        $this->officer_name = $delivery->officer_name;
        $this->officer_role = $delivery->officer_role;
        $this->officer_designation = $delivery->officer_designation;

        $linkedBabies = LinkedChild::where('parent_patient_id', $this->patientId)
          ->whereDate('date_of_birth', $delivery->dodel)
          ->orderBy('birth_order')
          ->get();

        if ($linkedBabies->isNotEmpty()) {
          $this->babies = $linkedBabies->map(function ($child, $index) {
            return [
              'first_name' => $child->first_name,
              'last_name' => $child->last_name,
              'gender' => $child->gender,
              'birth_weight' => $child->birth_weight,
              'birth_order' => $child->birth_order ?: ($index + 1),
            ];
          })->values()->toArray();
        } else {
          $this->babies = [[
            'first_name' => '',
            'last_name' => $this->last_name,
            'gender' => $delivery->baby_sex,
            'birth_weight' => $delivery->weight,
            'birth_order' => 1,
          ]];
        }
        $this->modal_flag = true;
        $this->dispatch('open-main-modal');

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while loading the delivery record.');
      throw $e;
    }
  }

  public function update()
  {
    DB::beginTransaction();
    try {
      $rules = array_diff_key($this->rules, [
        'patientId' => '',
      ]);
      $this->autoFillDeliveryMonth();
      $this->hydrateDeliveryBabyFields();
      $this->validate($rules);

      $delivery = Delivery::findOrFail($this->delivery_id);
        $delivery->update(array_diff_key($this->all(), [
          'patientId' => '',
          'patient' => '',
        'patient_din' => '',
        'middle_name' => '',
        'patient_phone' => '',
        'patient_dob' => '',
        'patient_age' => '',
        'patient_gender' => '',
        'state_name' => '',
        'lga_name' => '',
        'ward_name' => '',
        'facility_name' => '',
        'states' => '',
        'lgas' => '',
        'wards' => '',
        'hasAccess' => '',
          'accessError' => '',
          'activation_time' => '',
          'babies' => ''
        ]));
        $this->syncLinkedChildFromDelivery($delivery);

      DB::commit();
      toastr()->success('Delivery record updated successfully.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error while updating the delivery record.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the delivery record.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $delivery = Delivery::findOrFail($id);
      $delivery->delete();

      DB::commit();
      toastr()->success('Delivery record deleted successfully.');
      $this->resetForm();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the delivery record.');
      throw $e;
    }
  }

  public function exit()
  {
    $this->resetForm();
    $this->closeModalAndRefresh();
  }

  public function closeModalAndRefresh()
  {
    $this->dispatch('close-modals');
    $this->js('window.location.reload()');
  }

  private function resetForm()
  {
    $this->reset([
      'first_name',
      'last_name',
      'cl_sex',
      'cl_phone',
      'month_year',
      'cl_date',
      'toc',
      'seeking_care',
      'transportation',
      'parity',
      'dodel',
      'mod',
      'partograph',
      'oxytocin',
      'misoprostol',
      'alive',
      'admitted',
      'discharged',
      'referred_out',
      'pac',
      'mother_transportation',
      'dead',
      'MDA_conducted',
      'MDA_not_conducted',
      'abortion',
      'time_of_delivery',
      'pre_term',
      'breathing',
      'weight',
      'still_birth',
      'baby_dead',
      'live_births',
      'baby_sex',
      'took_delivery',
      'doctor',
      'newborn_care',
      'clamped',
      'CKX_gel',
      'breast',
      'temperature',
      'breastfeeding',
      'postpartum',
      'took_del',
      'babies',
      'delivery_id',
      'modal_flag',
    ]);
    $this->mount($this->patientId);
  }

  private function autoFillDeliveryMonth(): void
  {
    if ($this->dodel) {
      try {
        $this->month_year = Carbon::parse($this->dodel)->startOfMonth()->format('Y-m-d');
      } catch (Exception $e) {
        // ignore invalid date
      }
    }
  }

  private function hydrateDeliveryBabyFields(): void
  {
    if (empty($this->babies)) {
      return;
    }

    $firstBaby = $this->babies[0] ?? [];
    if (!empty($firstBaby['gender'])) {
      $this->baby_sex = $firstBaby['gender'];
    }
    if (array_key_exists('birth_weight', $firstBaby)) {
      $this->weight = $firstBaby['birth_weight'];
    }
  }

  private function getAgeRangeFromAge(?int $age): ?string
  {
    if ($age === null) return null;
    if ($age < 11) return '11 - 14 years';
    if ($age >= 11 && $age <= 14) return '11 - 14 years';
    if ($age >= 15 && $age <= 19) return '15 - 19 years';
    if ($age >= 20 && $age <= 24) return '20 - 24 years';
    if ($age >= 25 && $age <= 29) return '25 - 29 years';
    if ($age >= 30 && $age <= 34) return '30 - 34 years';
    if ($age >= 35 && $age <= 49) return '35 - 49 years';
    if ($age >= 50) return '50 + years';
    return null;
  }

  private function syncLinkedChildFromDelivery(Delivery $delivery): void
  {
    if (!$delivery->dodel) {
      return;
    }

    $stillBirth = strtolower((string) $delivery->still_birth) === 'yes';
    $babyDead = strtolower((string) $delivery->baby_dead) === 'yes';
    if ($stillBirth || $babyDead) {
      return;
    }

    $motherLastName = $this->last_name ?: $delivery->patient?->last_name;
    $userId = Auth::id();

    foreach ($this->babies as $index => $baby) {
      $gender = $baby['gender'] ?? null;
      if (!$gender) {
        continue;
      }

      $birthOrder = $baby['birth_order'] ?? ($index + 1);
      $existingChild = LinkedChild::where('parent_patient_id', $delivery->patient_id)
        ->whereDate('date_of_birth', $delivery->dodel)
        ->where('gender', $gender)
        ->where('birth_order', $birthOrder)
        ->first();

      $updatePayload = [
        'gender' => $gender,
        'date_of_birth' => $delivery->dodel,
        'birth_weight' => $baby['birth_weight'] ?? null,
        'birth_order' => $birthOrder,
        'facility_id' => $delivery->facility_id,
        'updated_by' => $userId,
        'notes' => 'Auto linked from delivery #' . $delivery->id,
      ];

      if ($existingChild) {
        $existingChild->update($updatePayload);
        continue;
      }

      $firstName = $baby['first_name'] ?: ('Baby ' . ($index + 1));
      $lastName = $baby['last_name'] ?: $motherLastName;

      LinkedChild::create(array_merge($updatePayload, [
        'linked_child_id' => LinkedChild::generateLinkedChildID(),
        'parent_patient_id' => $delivery->patient_id,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'relationship' => 'Mother',
        'is_active' => true,
        'created_by' => $userId,
      ]));
    }
  }

  public function render()
  {
    $deliveries = Delivery::with('patient', 'facility')
      ->where('patient_id', $this->patientId)
      ->latest()
      ->get();
    return view('livewire.workspaces.modules.anc.deliveries', [
      'deliveries' => $deliveries,
      'states' => $this->states,
      'lgas' => $this->lgas,
      'wards' => $this->wards,
      'facility_name' => $this->facility_name,
      'state_name' => $this->state_name,
      'lga_name' => $this->lga_name,
      'ward_name' => $this->ward_name
    ])->layout('layouts.dataOfficerLayout');
  }

  public function backToDashboard()
  {
    return redirect()->route('workspaces-antenatal', ['patientId' => $this->patientId]);
  }

  public function placeholder()
  {
    return view('placeholder');
  }
}
