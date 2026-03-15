<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referral extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'referral_date',
    'serial_no',
    'referred_from',
    'referred_to',
    'requested_service_code',
    'services_selected',
    'services_other',
    'service_provided',
    'date_completed',
    'follow_up_needed',
    'transport_mode',
    'time_in',
    'time_out',
    'completed_by',
    'completed_designation',
    'completed_date',
    'focal_person',
    'focal_date',
    'summary_map',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'referral_date' => 'date',
    'date_completed' => 'date',
    'completed_date' => 'date',
    'focal_date' => 'date',
    'services_selected' => 'array',
    'summary_map' => 'array',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}
