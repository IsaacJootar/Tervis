<?php

namespace App\Livewire\Account;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Settings extends Component
{
  public $first_name;
  public $last_name;
  public $email;
  public $phone;
  public $username;
  public $role;

  public $current_password;
  public $new_password;
  public $new_password_confirmation;

  public function mount(): void
  {
    $user = Auth::user();
    if (!$user) {
      abort(403, 'Unauthorized');
    }

    $this->first_name = (string) $user->first_name;
    $this->last_name = (string) $user->last_name;
    $this->email = (string) ($user->email ?? '');
    $this->phone = (string) ($user->phone ?? '');
    $this->username = (string) ($user->username ?? '');
    $this->role = (string) ($user->role ?? '');
  }

  public function updateProfile(): void
  {
    $user = Auth::user();
    if (!$user) {
      abort(403, 'Unauthorized');
    }

    $validated = $this->validate([
      'first_name' => ['required', 'string', 'max:100'],
      'last_name' => ['required', 'string', 'max:100'],
      'email' => [
        'required',
        'email',
        'max:150',
        Rule::unique('users', 'email')->ignore($user->id),
      ],
      'phone' => ['nullable', 'string', 'max:20'],
    ]);

    User::query()->where('id', $user->id)->update([
      'first_name' => $validated['first_name'],
      'last_name' => $validated['last_name'],
      'email' => $validated['email'],
      'phone' => $validated['phone'] ?: null,
    ]);

    $user->refresh();
    $this->first_name = (string) $user->first_name;
    $this->last_name = (string) $user->last_name;
    $this->email = (string) ($user->email ?? '');
    $this->phone = (string) ($user->phone ?? '');

    toastr()->success('Account profile updated successfully.');
  }

  public function updatePassword(): void
  {
    $user = Auth::user();
    if (!$user) {
      abort(403, 'Unauthorized');
    }

    $validated = $this->validate([
      'current_password' => ['required', 'string'],
      'new_password' => ['required', 'string', 'min:8', 'confirmed'],
    ], [], [
      'new_password' => 'new password',
    ]);

    if (!Hash::check((string) $validated['current_password'], (string) $user->password)) {
      $this->addError('current_password', 'Current password is incorrect.');
      return;
    }

    User::query()->where('id', $user->id)->update([
      'password' => Hash::make((string) $validated['new_password']),
    ]);

    $this->reset([
      'current_password',
      'new_password',
      'new_password_confirmation',
    ]);

    toastr()->success('Password changed successfully.');
  }

  public function render()
  {
    return view('livewire.account.settings')->layout($this->resolveLayout());
  }

  private function resolveLayout(): string
  {
    $role = (string) (Auth::user()->role ?? '');

    return match ($role) {
      'Central Admin', 'Central Administrator' => 'layouts.centralAdminLayout',
      'State Data Administrator', 'State Administrator' => 'layouts.stateOfficerLayout',
      'LGA Officer', 'LGA Data Administrator', 'LGA Administrator' => 'layouts.lgaOfficerLayout',
      'Facility Administrator' => 'layouts.facilityAdminLayout',
      'Data Officer' => 'layouts.dataOfficerLayout',
      'Verification Officer' => 'layouts.avoOfficerLayout',
      'Patient' => 'layouts.patientLayout',
      default => 'layouts.facilityAdminLayout',
    };
  }
}

