<?php

namespace App\Models;

use App\Models\Registrations\AntenatalRegistration;

/**
 * Legacy compatibility model for modules that still reference App\Models\Antenatal.
 */
class Antenatal extends AntenatalRegistration
{
  protected $table = 'antenatal_registrations';
}
