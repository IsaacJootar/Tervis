<?php

namespace App\Livewire\Central;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CentralAdminDashboard extends Component
{

  public function render()
  {
    return view('livewire.central.central-admin-dashboard')->layout('layouts.centralAdminLayout');
  }
}
