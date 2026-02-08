<?php

namespace App\Livewire\Central;

use App\Models\Lga;
use App\Models\Ward;
use App\Models\State;
use Livewire\Component;
use App\Models\Facility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CreateFacility extends Component
{
  public $name, $state, $lga, $ward, $address, $phone, $email;
  public $modal_flag = false;
  public $facility_id;

  public $states = [];
  public $lgas = [];
  public $wards = [];

  protected $rules = [
    'name' => 'required|string|max:255',
    'state' => 'required|exists:states,id',
    'lga' => 'required|exists:lgas,id',
    'ward' => 'required|exists:wards,id',
    'address' => 'required|string|max:255',
    'phone' => 'required|string|max:15',
    'email' => 'nullable|email|unique:facilities,email|max:255',
  ];

  public function mount()
  {
    // Cache states for 1 hour
    $this->states = Cache::remember('states_ordered', 3600, function () {
      return State::select('id', 'name')->orderBy('name')->get();
    });
  }

  public function updatedState($value)
  {
    if ($value) {
      // Cache LGAs by state for 30 minutes
      $this->lgas = Cache::remember("lgas_state_{$value}", 1800, function () use ($value) {
        return Lga::select('id', 'name', 'state_id')
          ->where('state_id', $value)
          ->orderBy('name')
          ->get();
      });
    } else {
      $this->lgas = [];
    }
    $this->lga = '';
    $this->wards = [];
    $this->ward = '';
  }

  public function updatedLga($value)
  {
    if ($value) {
      // Cache wards by LGA for 30 minutes
      $this->wards = Cache::remember("wards_lga_{$value}", 1800, function () use ($value) {
        return Ward::select('id', 'name', 'lga_id')
          ->where('lga_id', $value)
          ->orderBy('name')
          ->get();
      });
    } else {
      $this->wards = [];
    }
    $this->ward = '';
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      // Check for duplicate facility name in same state-lga-ward
      $exists = Facility::where('name', $this->name)
        ->where('state_id', $this->state)
        ->where('lga_id', $this->lga)
        ->exists();

      if ($exists) {
        DB::rollBack();
        toastr()->error('A facility with this name already exists in the selected location.');
        return;
      }

      $this->validate();

      // Get names efficiently
      $stateName = State::find($this->state)->name;
      $lgaName = Lga::find($this->lga)->name;
      $wardName = Ward::find($this->ward)->name;

      Facility::create([
        'name' => $this->name,
        'state_id' => $this->state,
        'lga_id' => $this->lga,
        'state' => $stateName,
        'lga' => $lgaName,
        'ward' => $wardName,
        'address' => $this->address,
        'phone' => $this->phone,
        'email' => $this->email,
        'created_at' => now(),
        'updated_at' => now(),
      ]);

      // Clear relevant caches
      $this->clearFacilityCaches();

      DB::commit();
      toastr()->info('Facility created successfully.');
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      if (isset($e->errors()['email'])) {
        toastr()->error($e->errors()['email'][0]);
      } elseif (isset($e->errors()['name'])) {
        toastr()->error($e->errors()['name'][0]);
      } else {
        throw $e;
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the facility.');
      throw $e;
    }
  }

  public function edit($id)
  {
    $facility = Facility::findOrFail($id);
    $this->facility_id = $id;
    $this->name = $facility->name;
    $this->state = $facility->state_id;
    $this->updatedState($this->state); // Load LGAs
    $this->lga = $facility->lga_id;
    $this->updatedLga($this->lga); // Load Wards
    $this->ward = Ward::where('name', $facility->ward)->first()->id ?? null; // Assuming ward names are unique per LGA
    $this->address = $facility->address;
    $this->phone = $facility->phone;
    $this->email = $facility->email;

    $this->modal_flag = true;
  }

  public function update()
  {
    DB::beginTransaction();
    try {
      // Check for duplicate facility name in same state-lga-ward (excluding current)
      $exists = Facility::where('name', $this->name)
        ->where('state_id', $this->state)
        ->where('lga_id', $this->lga)
        ->where('id', '!=', $this->facility_id)
        ->exists();

      if ($exists) {
        DB::rollBack();
        toastr()->error('A facility with this name already exists in the selected location.');
        return;
      }

      $this->validate();

      // Get names efficiently
      $stateName = State::find($this->state)->name;
      $lgaName = Lga::find($this->lga)->name;
      $wardName = Ward::find($this->ward)->name;

      $updateData = [
        'name' => $this->name,
        'state_id' => $this->state,
        'lga_id' => $this->lga,
        'state' => $stateName,
        'lga' => $lgaName,
        'ward' => $wardName,
        'address' => $this->address,
        'phone' => $this->phone,
        'email' => $this->email,
        'updated_at' => now(),
      ];

      Facility::where('id', $this->facility_id)->update($updateData);

      // Clear relevant caches
      $this->clearFacilityCaches();

      DB::commit();
      toastr()->success('Facility updated successfully.');
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      if (isset($e->errors()['email'])) {
        toastr()->error($e->errors()['email'][0]);
      } elseif (isset($e->errors()['name'])) {
        toastr()->error($e->errors()['name'][0]);
      } else {
        throw $e;
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the facility.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $facility = Facility::findOrFail($id);
      $facility->delete();

      // Clear relevant caches
      $this->clearFacilityCaches();

      DB::commit();
      toastr()->success('Facility deleted successfully.');
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the facility.');
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
    $this->dispatch('close-modal');
    $this->js('window.location.reload()');
  }

  private function clearFacilityCaches()
  {
    // Clear facility-related caches
    Cache::forget('facilities_list');
    Cache::forget('facilities_ordered');

    // Clear location-based caches if needed
    $stateIds = Cache::remember('all_state_ids', 3600, function () {
      return State::pluck('id')->toArray();
    });

    foreach ($stateIds as $stateId) {
      Cache::forget("lgas_state_{$stateId}");

      // Clear LGA-specific ward caches
      $lgaIds = Cache::remember("lga_ids_state_{$stateId}", 3600, function () use ($stateId) {
        return Lga::where('state_id', $stateId)->pluck('id')->toArray();
      });

      foreach ($lgaIds as $lgaId) {
        Cache::forget("wards_lga_{$lgaId}");
      }
    }
  }

  private function resetForm()
  {
    $this->reset([
      'name',
      'state',
      'lga',
      'ward',
      'address',
      'phone',
      'email',
      'facility_id',
      'modal_flag',
      'lgas',
      'wards',
    ]);

    // Reload cached data
    $this->states = Cache::remember('states_ordered', 3600, function () {
      return State::select('id', 'name')->orderBy('name')->get();
    });
  }

  public function render()
  {
    // Cache facilities list for 5 minutes
    $facilities = Cache::remember('facilities_list', 300, function () {
      return Facility::select('id', 'name', 'state', 'lga', 'ward', 'address', 'phone', 'email')
        ->latest()
        ->take(50)
        ->get();
    });


    return view('livewire.central.create-facility', [
      'facilities' => $facilities,
      'states' => $this->states,
      'lgas' => $this->lgas,
      'wards' => $this->wards,
    ])->layout('layouts.centralAdminLayout');
  }
}
