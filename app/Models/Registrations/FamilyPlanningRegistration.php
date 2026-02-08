<?php

namespace App\Models\Registrations;

use App\Models\Patient;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FamilyPlanningRegistration extends Model
{
  use HasFactory;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'registration_date',
    'client_reg_number',
    'referral_source',

    // Obstetric History
    'children_born_alive',
    'children_still_living',
    'miscarriages_stillbirths_abortions',
    'last_pregnancy_ended',
    'last_pregnancy_result',
    'breastfeeding',
    'want_more_children',

    // Menstrual History
    'last_menstrual_period',
    'menstrual_cycle',
    'cycle_duration',

    // Medical History
    'medical_conditions',
    'other_illness_specify',
    'smoke',
    'last_pregnancy_complication',
    'complication_specify',

    // Contraceptive History
    'prior_contraceptive',
    'prior_method',

    // Contraceptive Method Selected
    'contraceptive_selected',
    'brand_size_model',
    'source',
    'quality',

    // Physical Examination
    'weight',
    'blood_pressure',
    'breasts',
    'uterus_position',
    'uterus_size',
    'cervix_tears',
    'cervix_erosion',
    'vaginal_discharge',
    'discharge_colour',
    'discharge_odor',
    'cervix_discharge',
    'liver_enlarged',
    'laboratory_results',
    'other_observations',

    // Follow-up
    'next_appointment',

    // Pregnancy Tracking
    'pregnancy1_date_ended',
    'pregnancy1_outcome',
    'pregnancy1_complication',
    'pregnancy2_date_ended',
    'pregnancy2_outcome',
    'pregnancy2_complication',

    // Officer Information
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'registration_date' => 'date',
    'last_pregnancy_ended' => 'date',
    'last_menstrual_period' => 'date',
    'next_appointment' => 'date',
    'pregnancy1_date_ended' => 'date',
    'pregnancy2_date_ended' => 'date',
    'breastfeeding' => 'boolean',
    'smoke' => 'boolean',
    'prior_contraceptive' => 'boolean',
    'cervix_tears' => 'boolean',
    'cervix_erosion' => 'boolean',
    'vaginal_discharge' => 'boolean',
    'cervix_discharge' => 'boolean',
    'liver_enlarged' => 'boolean',
    'medical_conditions' => 'array',
    'weight' => 'decimal:2',
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
