<?php

namespace App\Models\Registrations;

use App\Models\Patient;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralPatientsRegistration extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'marital_status',
    'occupation',
    'religion',
    'place_of_origin',
    'tribe',
    'home_address',
    'town',
    'landmark',
    'po_box_no',
    'nok_name',
    'nok_relationship',
    'nok_phone',
    'nok_address',
    'xray_no',
    'officer_name',
    'officer_role',
    'officer_designation',
    'registered_by',
    'registration_date',
  ];

  protected $casts = [
    'registration_date' => 'datetime',
    'patient_id' => 'integer',
    'facility_id' => 'integer',
    'registered_by' => 'integer',
  ];

  /**
   * Relationships
   */
  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}
