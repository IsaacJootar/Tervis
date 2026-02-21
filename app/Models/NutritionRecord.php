<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NutritionRecord extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'linked_child_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'visit_date',
    'age_group',
    'infant_feeding',
    'complementary_feeding',
    'counselling_topics',
    'support_group_referred',
    'height_cm',
    'weight_kg',
    'oedema',
    'muac_value_mm',
    'muac_class',
    'growth_status',
    'supplementary_feeding_groups',
    'mnp_given',
    'otp_provider',
    'admission_status',
    'outcome_status',
    'remarks',
    'summary_map',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'counselling_topics' => 'array',
    'supplementary_feeding_groups' => 'array',
    'summary_map' => 'array',
    'support_group_referred' => 'boolean',
    'mnp_given' => 'boolean',
    'height_cm' => 'float',
    'weight_kg' => 'float',
    'muac_value_mm' => 'integer',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function linkedChild(): BelongsTo
  {
    return $this->belongsTo(LinkedChild::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}
