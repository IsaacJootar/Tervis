<?php

namespace App\Livewire\Patient;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.patientLayout')]
class Portal extends Component
{
  public string $section = 'dashboard';
  public string $heading = 'Patient Dashboard';
  public string $description = 'Your patient portal section is available.';

  public function mount(string $section = 'dashboard'): void
  {
    $normalized = trim(strtolower($section));
    $labels = [
      'dashboard' => ['Patient Dashboard', 'Overview of your records and care activities.'],
      'profile' => ['My Profile', 'Your account and profile details.'],
      'antenatal' => ['Antenatal Records', 'Your antenatal records and follow-up details.'],
      'deliveries' => ['My Deliveries', 'Your delivery history and outcomes.'],
      'postnatal' => ['Postnatal Care', 'Your postnatal follow-up records.'],
      'tetanus' => ['Tetanus Records', 'Your tetanus vaccination records.'],
    ];

    if (!array_key_exists($normalized, $labels)) {
      $normalized = 'dashboard';
    }

    $this->section = $normalized;
    $this->heading = $labels[$normalized][0];
    $this->description = $labels[$normalized][1];
  }

  public function render()
  {
    return view('livewire.patient.portal');
  }
}
