<?php

namespace App\Livewire;

use App\Helpers\Helpers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('User Login')]
class Login extends Component
{
  public $username;
  public $password;
  public $remember = false;

  public function mount()
  {
    // Explicitly set config to ensure myLayout is blank, am using this blank layout for login only, for now
    Helpers::updatePageConfig(['myLayout' => 'access-auth']);
  }

  public function render()
  {
    return view('livewire.login')
      ->layout('layouts.access-auth')
      ->layoutData(['myLayout' => 'access-auth']);
  }

  public function login()
  {
    if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
      $seconds = RateLimiter::availableIn($this->throttleKey());
      $message = trans('auth.throttle', [
        'seconds' => $seconds,
        'minutes' => ceil($seconds / 60),
      ]);
      toastr()->error($message);
      $this->addError('message', $message);
      return;
    }

    $validated = $this->validate([
      'username' => 'required|string|max:200',
      'password' => 'required',
      'remember' => 'nullable|boolean',
    ]);

    $attempted = Auth::attempt([
      'username' => (string) $validated['username'],
      'password' => (string) $validated['password'],
    ], (bool) $this->remember);

    if (!$attempted) {
      RateLimiter::hit($this->throttleKey());
      $message = 'The Username or Password provided does not match our records.';
      toastr()->error($message);
      $this->addError('message', $message);
      return;
    }

    RateLimiter::clear($this->throttleKey());
    Session::regenerate();

    $user = Auth::user();
    if (!$user || $user->account_status === 'disabled' || !$user->is_active) {
      Auth::logout();
      Session::invalidate();
      Session::regenerateToken();
      $message = 'Your account has been disabled. Please contact your facility administrator.';
      toastr()->error($message);
      $this->addError('message', $message);
      return;
    }

    $roleRoutes = [
      'Central Admin' => 'central-admin-dashboard',
      'Central Administrator' => 'central-admin-dashboard',
      'State Data Administrator' => 'state-officer-dashboard',
      'State Administrator' => 'state-officer-dashboard',
      'LGA Officer' => 'lga-officer-dashboard',
      'LGA Data Administrator' => 'lga-officer-dashboard',
      'LGA Administrator' => 'lga-officer-dashboard',
      'Facility Administrator' => 'facility-admin-dashboard',
      'Data Officer' => 'patient-workspace',
      'Verification Officer' => 'din-activations',
      'Patient' => 'patient-dashboard',
    ];

    $role = (string) $user->role;
    if ($role !== '' && isset($roleRoutes[$role])) {
      toastr()->info('You are successfully logged in as ' . $role);
      return redirect()->intended(route($roleRoutes[$role]));
    }

    Auth::logout();
    Session::invalidate();
    Session::regenerateToken();
    $message = 'Role route is not configured for this account.';
    toastr()->error($message);
    $this->addError('message', $message);
  }

  protected function throttleKey(): string
  {
    return Str::transliterate(Str::lower((string) $this->username) . '|' . request()->ip());
  }
}

