<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session;
use App\Helpers\Helpers;

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
    $this->ensureIsNotRateLimited();

    $validated = $this->validate([
      'username' => 'required|string|max:200',
      'password' => 'required'
    ]);

    if (Auth::attempt($validated)) {
      Session::regenerate();

      $user = Auth::user();

      // Check account status - prevent disabled accounts from logging in
      if ($user->account_status === 'disabled') {

        throw ValidationException::withMessages([
          'message' => 'Your account has been disabled. Please contact your facility administrator.',
        ]);
      }
      $role = $user->role;

      // Define role-based redirect paths
      $roleRoutes = [
        'Central Administrator' => 'central-officer-dashboard',
        'State Data Administrator' => 'state-officer-dashboard',
        'LGA Officer' => 'lga-officer-dashboard',
        'Facility Administrator' => 'facility-admin-dashboard',
        'Data Officer' => 'data-officer-dashboard',
        'Verification Officer' => 'din-activations',
        'Patient' => 'patient-dashboard'
      ];

      if ($role && isset($roleRoutes[$role])) {
        toastr()->info('You are successfully logged in as ' . $role);
        return redirect()->intended(route($roleRoutes[$role]));
      }
    }

    if (! Auth::attempt($this->only(['username', 'password']))) {
      RateLimiter::hit($this->throttleKey());

      throw ValidationException::withMessages([
        'message' => 'The Username or Password provided does not match our records.',
      ]);
    }

    RateLimiter::clear($this->throttleKey());
  }

  protected function ensureIsNotRateLimited(): void
  {
    if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
      return;
    }

    event(new Lockout(request()));

    $seconds = RateLimiter::availableIn($this->throttleKey());

    throw ValidationException::withMessages([
      'username' => trans('auth.throttle', [
        'seconds' => $seconds,
        'minutes' => ceil($seconds / 60),
      ]),
    ]);
  }

  protected function throttleKey(): string
  {
    return Str::transliterate(Str::lower($this->username) . '|' . request()->ip());
  }
}
