<?php

namespace App\Livewire\Core;

use App\Models\Facility;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDataOfficers extends Component
{
  public $first_name, $last_name, $email, $username, $password, $password_confirmation, $designation, $facility_id;
  public $modal_flag = false;
  public $data_officer_id;

  public $facilities = [];
  private $role = 'Data Officer';
  public $designations = ['Nurse', 'Doctor', 'Midwife', 'Lab Attendant', 'Verification Officer', 'Volunteer'];

  protected $rules = [
    'first_name' => 'required|string|max:255',
    'last_name' => 'required|string|max:255',
    'email' => 'nullable|email',
    'username' => 'required|string|unique:users,username',
    'password' => 'required|string|min:8|confirmed',
    'designation' => 'required|in:Nurse,Doctor,Midwife,Lab Attendant,Verification Officer,Volunteer',
  ];

  protected $messages = [
    'email.email' => 'Please enter a valid email address.',
    'username.unique' => 'This username is already taken.',
    'password.confirmed' => 'The password confirmation does not match.',
    'designation.in' => 'Please select a valid designation (Nurse, Doctor, Midwife, Lab Attendant,Verification Officer, Volunteer).',
  ];

  public function mount()
  {
    $admin = Auth::user();

    $this->facility_id = $admin->facility_id;

    // Cache facility data for 1 hour
    $facility = Cache::remember("facility_{$this->facility_id}", 3600, function () {
      return Facility::select('id', 'name')->find($this->facility_id);
    });

    if (!$facility) {
      abort(403, 'Invalid facility assignment.');
    }
    $this->facilities = [$facility];
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      // Check for duplicate first_name and last_name combination
      $userRoleExists = User::where('first_name', $this->first_name)
        ->where('last_name', $this->last_name)
        ->exists();
      if ($userRoleExists) {
        DB::rollBack();
        toastr()->error('This combination of first name and last name is already in use.');
        return;
      }

      $this->validate();

      // Generate unique username
      $username = $this->username;
      $code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
      while (User::where('username', $username)->exists()) {
        $username = $this->username . $code;
        $code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
      }

      // Create user with optimized data
      // Determine the role based on designation
      $role = ($this->designation === 'Verification Officer') ? 'Verification Officer' : 'Data Officer';

      $userData = [
        'first_name'   => $this->first_name,
        'last_name'    => $this->last_name,
        'email'        => $this->email ?: null,
        'username'     => $username,
        'password'     => Hash::make($this->password),
        'role'         => $role, // Logic applied here
        'designation'  => $this->designation,
        'facility_id'  => $this->facility_id,
        'created_at'   => now(),
        'updated_at'   => now(),
      ];

      User::create($userData);

      // Clear relevant caches
      $this->clearDataOfficerCaches();

      DB::commit();
      toastr()->info('Data Officer created successfully.');
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      if (isset($e->errors()['email'])) {
        toastr()->error($e->errors()['email'][0]);
      } elseif (isset($e->errors()['username'])) {
        toastr()->error($e->errors()['username'][0]);
      } elseif (isset($e->errors()['password'])) {
        toastr()->error($e->errors()['password'][0]);
      } elseif (isset($e->errors()['designation'])) {
        toastr()->error($e->errors()['designation'][0]);
      } else {
        throw $e;
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the data officer.');
      throw $e;
    }
  }

  public function edit($id)
  {
    // Use select to limit fields retrieved
    $user = User::select('id', 'first_name', 'last_name', 'email', 'username', 'designation', 'facility_id', 'role')
      ->findOrFail($id);

    if ($user->role !== 'Data Officer' || $user->facility_id !== $this->facility_id) {
      abort(403, 'Unauthorized: This Data Officer does not belong to your facility.');
    }

    $this->data_officer_id = $id;
    $this->first_name = $user->first_name;
    $this->last_name = $user->last_name;
    $this->email = $user->email;
    $this->username = $user->username;
    $this->designation = $user->designation;
    $this->password = null;
    $this->password_confirmation = null;
    $this->modal_flag = true;
  }

  public function update()
  {
    DB::beginTransaction();
    try {
      $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'nullable|email',
        'username' => 'required|string|unique:users,username,' . $this->data_officer_id,
        'designation' => 'required|in:Nurse,Doctor,Midwife,Lab Attendant',
      ];

      if ($this->password) {
        $rules['password'] = 'required|string|min:8|confirmed';
      }

      // Validate unique first_name and last_name combination
      $userRoleExists = User::where('first_name', $this->first_name)
        ->where('last_name', $this->last_name)
        ->where('id', '!=', $this->data_officer_id)
        ->exists();
      if ($userRoleExists) {
        DB::rollBack();
        toastr()->error('This combination of first name and last name is already in use.');
        return;
      }

      $this->validate($rules);

      $user = User::findOrFail($this->data_officer_id);
      if ($user->role !== 'Data Officer' || $user->facility_id !== $this->facility_id) {
        DB::rollBack();
        abort(403, 'Unauthorized: This Data Officer does not belong to your facility.');
      }

      // Update user with optimized data
      $updateData = [
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'email' => $this->email ?: null,
        'username' => $this->username,
        'role' => 'Data Officer',
        'designation' => $this->designation,
        'facility_id' => $this->facility_id,
        'updated_at' => now(),
      ];

      if ($this->password) {
        $updateData['password'] = Hash::make($this->password);
      }

      User::where('id', $this->data_officer_id)->update($updateData);

      // Clear relevant caches
      $this->clearDataOfficerCaches();

      DB::commit();
      toastr()->info('Data Officer updated successfully.');
      $this->resetForm();
    } catch (ValidationException $e) {
      DB::rollBack();
      if (isset($e->errors()['email'])) {
        toastr()->error($e->errors()['email'][0]);
      } elseif (isset($e->errors()['username'])) {
        toastr()->error($e->errors()['username'][0]);
      } elseif (isset($e->errors()['password'])) {
        toastr()->error($e->errors()['password'][0]);
      } elseif (isset($e->errors()['designation'])) {
        toastr()->error($e->errors()['designation'][0]);
      } else {
        throw $e;
      }
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the data officer.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $user = User::findOrFail($id);
      if ($user->role !== 'Data Officer' || $user->facility_id !== $this->facility_id) {
        DB::rollBack();
        abort(403, 'Unauthorized: This Data Officer does not belong to your facility.');
      }

      $user->delete();

      // Clear relevant caches
      $this->clearDataOfficerCaches();

      DB::commit();
      toastr()->info('Data Officer deleted successfully.');
    } catch (\Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the data officer.');
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

  private function clearDataOfficerCaches()
  {
    // Clear data officer related caches
    Cache::forget("data_officers_facility_{$this->facility_id}");
    Cache::forget("facility_{$this->facility_id}_data_officers_count");
    Cache::forget("facility_{$this->facility_id}_doctors_count");
    Cache::forget("facility_{$this->facility_id}_nurses_count");
    Cache::forget("facility_{$this->facility_id}_lab_attendants_count");

    // Clear facility cache if needed
    Cache::forget("facility_{$this->facility_id}");
  }

  private function resetForm()
  {
    $this->reset([
      'first_name',
      'last_name',
      'email',
      'username',
      'password',
      'password_confirmation',
      'designation',
      'data_officer_id',
      'modal_flag',
    ]);

    // Reload cached facility data
    $facility = Cache::remember("facility_{$this->facility_id}", 3600, function () {
      return Facility::select('id', 'name')->find($this->facility_id);
    });

    if ($facility) {
      $this->facilities = [$facility];
    }
  }

  public function render()
  {
    // Cache data officers list for 5 minutes-this is list for only officers in your facility amount the tenants
    $dataOfficers = Cache::remember("data_officers_facility_{$this->facility_id}", 300, function () {
      return User::select('id', 'first_name', 'last_name', 'email', 'username', 'designation', 'facility_id', 'role')
        ->with(['facility:id,name'])
        ->where('role', 'Data Officer')
        ->where('facility_id', $this->facility_id)
        ->latest()
        ->take(50)
        ->get();
    });

    return view('livewire.core.create-data-officers', [
      'dataOfficers' => $dataOfficers,
      'facilities' => $this->facilities,
      'designations' => $this->designations,
    ])->layout('layouts.facilityAdminLayout');
  }
}
