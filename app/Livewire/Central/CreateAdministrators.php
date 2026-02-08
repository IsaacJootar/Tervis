<?php

namespace App\Livewire\Central;

use App\Models\Facility;
use App\Models\User;
use App\Models\State;
use App\Models\Lga;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAdministrators extends Component
{
  public $first_name, $last_name, $email, $password, $password_confirmation, $role, $designation, $facility_id, $state_id, $lga_id;
  public $modal_flag = false;
  public $admin_id;

  public $facilities = [];
  public $states = [];
  public $lgas = [];
  public $roles = ['Facility Administrator', 'LGA Officer', 'State Data Administrator'];
  public $designations = ['Facility Data Administrator', 'LGA Data Administrator', 'State Data Administrator'];

  protected $rules = [
    'first_name' => 'required|string|max:255',
    'last_name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|string|min:8|confirmed',
    'role' => 'required|in:Facility Administrator,LGA Officer,State Data Administrator',
    'designation' => 'required|in:Facility Data Administrator,LGA Data Administrator,State Data Administrator',
    'facility_id' => 'required_if:role,Facility Administrator|nullable|exists:facilities,id|unique:users,facility_id',
    'state_id' => 'required_if:role,LGA Officer|required_if:role,State Data Administrator|nullable|exists:states,id',
    'lga_id' => 'required_if:role,LGA Officer|nullable|exists:lgas,id',
  ];

  protected $messages = [
    'facility_id.unique' => 'This facility is already assigned to another administrator.',
    'role.in' => 'Please select either Facility Administrator, LGA Officer, or State Data Administrator. Only one role is allowed.',
    'designation.in' => 'Please select either Facility Data Administrator, LGA Data Administrator, or State Data Administrator. Only one designation is allowed.',
    'state_id.required_if' => 'Please select a state for LGA Officer or State Data Administrator.',
    'lga_id.required_if' => 'Please select an LGA for LGA Officer.',
  ];

  public function mount()
  {
    // Cache facilities for 1 hour
    $this->facilities = Cache::remember('facilities_ordered', 3600, function () {
      return Facility::select('id', 'name')->orderBy('name')->get();
    });

    // Cache states for 1 hour
    $this->states = Cache::remember('states_ordered', 3600, function () {
      return State::select('id', 'name')->orderBy('name')->get();
    });

    $this->lgas = collect();
  }

  public function updatedStateId($value)
  {
    if ($value && $this->role === 'LGA Officer') {
      // Cache LGAs by state for 30 minutes
      $this->lgas = Cache::remember("lgas_state_{$value}", 1800, function () use ($value) {
        return Lga::select('id', 'name', 'state_id')
          ->where('state_id', $value)
          ->orderBy('name')
          ->get();
      });
    } else {
      $this->lgas = collect();
    }
    $this->lga_id = null;
  }

  public function updatedRole($value)
  {
    $this->facility_id = null;
    $this->state_id = null;
    $this->lga_id = null;
    $this->lgas = collect();
    toastr()->info("Role changed to: {$value}");
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      // Batch validation queries for better performance
      $validationQueries = collect();

      // Custom validation for State-LGA uniqueness for LGA Officer
      if ($this->role === 'LGA Officer') {
        $validationQueries->push(
          User::where('state_id', $this->state_id)
            ->where('lga_id', $this->lga_id)
            ->where('role', 'LGA Officer')
            ->exists()
        );
      }

      // Custom validation for State uniqueness for State Data Administrator
      if ($this->role === 'State Data Administrator') {
        $validationQueries->push(
          User::where('state_id', $this->state_id)
            ->where('role', 'State Data Administrator')
            ->exists()
        );
      }

      // Validate unique first_name and last_name combination
      $validationQueries->push(
        User::where('first_name', $this->first_name)
          ->where('last_name', $this->last_name)
          ->exists()
      );

      // Execute validation queries
      if ($this->role === 'LGA Officer' && $validationQueries[0]) {
        DB::rollBack();
        toastr()->error('This State and LGA combination is already assigned to another LGA Officer.');
        return;
      }

      if ($this->role === 'State Data Administrator' && $validationQueries[1]) {
        DB::rollBack();
        toastr()->error('This state is already assigned to another State Data Administrator.');
        return;
      }

      if ($validationQueries->last()) {
        DB::rollBack();
        toastr()->error('This combination of first name and last name is already in use.');
        return;
      }

      $this->validate();

      // Generate unique username efficiently
      $username = $this->email;
      $code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
      while (User::where('username', $username)->exists()) {
        $username = $this->email . $code;
        $code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
      }

      // Create user with optimized data
      $userData = [
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'username' => $username,
        'email' => $this->email,
        'password' => Hash::make($this->password),
        'role' => $this->role,
        'designation' => $this->designation,
        'facility_id' => $this->role === 'Facility Administrator' ? $this->facility_id : null,
        'state_id' => in_array($this->role, ['LGA Officer', 'State Data Administrator']) ? $this->state_id : null,
        'lga_id' => $this->role === 'LGA Officer' ? $this->lga_id : null,
        'created_at' => now(),
        'updated_at' => now(),
      ];

      User::create($userData);

      // Clear relevant caches
      $this->clearAdminCaches();

      DB::commit();
      toastr()->info('Administrator created successfully.');
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      if (isset($e->errors()['facility_id'])) {
        toastr()->error($e->errors()['facility_id'][0]);
      } elseif (isset($e->errors()['role'])) {
        toastr()->error($e->errors()['role'][0]);
      } elseif (isset($e->errors()['designation'])) {
        toastr()->error($e->errors()['designation'][0]);
      } elseif (isset($e->errors()['state_id'])) {
        toastr()->error($e->errors()['state_id'][0]);
      } elseif (isset($e->errors()['lga_id'])) {
        toastr()->error($e->errors()['lga_id'][0]);
      } else {
        throw $e;
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the administrator.');
      throw $e;
    }
  }

  public function edit($id)
  {
    // Use select to limit fields retrieved
    $admin = User::select('id', 'first_name', 'last_name', 'email', 'role', 'designation', 'facility_id', 'state_id', 'lga_id')
      ->findOrFail($id);

    $this->admin_id = $id;
    $this->first_name = $admin->first_name;
    $this->last_name = $admin->last_name;
    $this->email = $admin->email;
    $this->role = $admin->role;
    $this->designation = $admin->designation;
    $this->facility_id = $admin->facility_id;
    $this->state_id = $admin->state_id;
    $this->lga_id = $admin->lga_id;
    $this->password = null;
    $this->password_confirmation = null;
    $this->modal_flag = true;

    // Load LGAs if LGA Officer (with caching)
    if ($this->role === 'LGA Officer' && $this->state_id) {
      $this->lgas = Cache::remember("lgas_state_{$this->state_id}", 1800, function () {
        return Lga::select('id', 'name', 'state_id')
          ->where('state_id', $this->state_id)
          ->orderBy('name')
          ->get();
      });
    } else {
      $this->lgas = collect();
    }
  }

  public function update()
  {
    DB::beginTransaction();
    try {
      $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $this->admin_id,
        'role' => 'required|in:Facility Administrator,LGA Officer,State Data Administrator',
        'designation' => 'required|in:Facility Data Administrator,LGA Data Administrator,State Data Administrator',
        'facility_id' => 'required_if:role,Facility Administrator|nullable|exists:facilities,id|unique:users,facility_id,' . $this->admin_id,
        'state_id' => 'required_if:role,LGA Officer|required_if:role,State Data Administrator|nullable|exists:states,id',
        'lga_id' => 'required_if:role,LGA Officer|nullable|exists:lgas,id',
      ];

      if ($this->password) {
        $rules['password'] = 'required|string|min:8|confirmed';
      }

      // Validate unique first_name and last_name combination
      $userExists = User::where('first_name', $this->first_name)
        ->where('last_name', $this->last_name)
        ->where('id', '!=', $this->admin_id)
        ->exists();
      if ($userExists) {
        DB::rollBack();
        toastr()->error('This combination of first name and last name is already in use.');
        return;
      }

      // Custom validation for State-LGA uniqueness for LGA Officer
      if ($this->role === 'LGA Officer') {
        $exists = User::where('state_id', $this->state_id)
          ->where('lga_id', $this->lga_id)
          ->where('role', 'LGA Officer')
          ->where('id', '!=', $this->admin_id)
          ->exists();
        if ($exists) {
          DB::rollBack();
          toastr()->error('This State and LGA combination is already assigned to another LGA Officer.');
          return;
        }
      }

      // Custom validation for State uniqueness for State Data Administrator-for now i want to just limit the state officer to 1
      if ($this->role === 'State Data Administrator') {
        $exists = User::where('state_id', $this->state_id)
          ->where('role', 'State Data Administrator')
          ->where('id', '!=', $this->admin_id)
          ->exists();
        if ($exists) {
          DB::rollBack();
          toastr()->error('This state is already assigned to another State Data Administrator.');
          return;
        }
      }

      $this->validate($rules);

      // Update user with optimized data
      $updateData = [
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'email' => $this->email,
        'role' => $this->role,
        'designation' => $this->designation,
        'facility_id' => $this->role === 'Facility Administrator' ? $this->facility_id : null,
        'state_id' => in_array($this->role, ['LGA Officer', 'State Data Administrator']) ? $this->state_id : null,
        'lga_id' => $this->role === 'LGA Officer' ? $this->lga_id : null,
        'updated_at' => now(),
      ];

      if ($this->password) {
        $updateData['password'] = Hash::make($this->password);
      }

      User::where('id', $this->admin_id)->update($updateData);

      // Clear relevant caches
      $this->clearAdminCaches();

      DB::commit();
      toastr()->info('Administrator updated successfully.');
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      if (isset($e->errors()['facility_id'])) {
        toastr()->error($e->errors()['facility_id'][0]);
      } elseif (isset($e->errors()['role'])) {
        toastr()->error($e->errors()['role'][0]);
      } elseif (isset($e->errors()['designation'])) {
        toastr()->error($e->errors()['designation'][0]);
      } elseif (isset($e->errors()['state_id'])) {
        toastr()->error($e->errors()['state_id'][0]);
      } elseif (isset($e->errors()['lga_id'])) {
        toastr()->error($e->errors()['lga_id'][0]);
      } else {
        throw $e;
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the administrator.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $admin = User::findOrFail($id);
      $admin->delete();

      // Clear relevant caches
      $this->clearAdminCaches();

      DB::commit();
      toastr()->info('Administrator deleted successfully.');
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the administrator.');
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

  private function clearAdminCaches()
  {
    // Clear admin-related caches
    Cache::forget('administrators_list');
    Cache::forget('facility_administrators_count');
    Cache::forget('lga_officers_count');
    Cache::forget('state_administrators_count');

    // Clear facilities cache if needed (in case facility assignments changed)
    Cache::forget('facilities_ordered');

    // Clear LGA caches for all states (if we want to be thorough)
    $stateIds = Cache::remember('all_state_ids', 3600, function () {
      return State::pluck('id')->toArray();
    });

    foreach ($stateIds as $stateId) {
      Cache::forget("lgas_state_{$stateId}");
    }
  }

  private function resetForm()
  {
    $this->reset([
      'first_name',
      'last_name',
      'email',
      'password',
      'password_confirmation',
      'role',
      'designation',
      'facility_id',
      'state_id',
      'lga_id',
      'admin_id',
      'modal_flag',
    ]);

    // Reload cached data
    $this->facilities = Cache::remember('facilities_ordered', 3600, function () {
      return Facility::select('id', 'name')->orderBy('name')->get();
    });

    $this->states = Cache::remember('states_ordered', 3600, function () {
      return State::select('id', 'name')->orderBy('name')->get();
    });

    $this->lgas = collect();
  }

  public function render()
  {
    // Cache administrators list for 5 minutes
    $admins = Cache::remember('administrators_list', 300, function () {
      return User::select('id', 'first_name', 'last_name', 'email', 'role', 'designation', 'facility_id', 'state_id', 'lga_id')
        ->with([
          'facility:id,name',
          'state:id,name',
          'lga:id,name'
        ])
        ->whereIn('role', ['Facility Administrator', 'LGA Officer', 'State Data Administrator'])
        ->latest()
        ->take(50)
        ->get();
    });

    return view('livewire.central.create-administrators', [
      'admins' => $admins,
      'facilities' => $this->facilities,
      'states' => $this->states,
      'lgas' => $this->lgas,
      'roles' => $this->roles,
      'designations' => $this->designations,
    ])->layout('layouts.centralAdminLayout');
  }
}
