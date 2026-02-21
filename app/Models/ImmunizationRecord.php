<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImmunizationRecord extends Model
{
  use HasFactory, SoftDeletes;

  public const VACCINE_FIELDS = [
    'hepb0_date',
    'opv0_date',
    'bcg_date',
    'opv1_date',
    'penta1_date',
    'pcv1_date',
    'rota1_date',
    'opv2_date',
    'penta2_date',
    'pcv2_date',
    'rota2_date',
    'ipv1_date',
    'opv3_date',
    'penta3_date',
    'pcv3_date',
    'mr1_date',
    'yf_date',
    'mr2_date',
    'mena_date',
    'yf2_date',
    'slea_date',
    'vita1_date',
    'vita2_date',
    'ipv2_date',
  ];

  public const VACCINE_LABELS = [
    'hepb0_date' => 'HepB0',
    'opv0_date' => 'OPV0',
    'bcg_date' => 'BCG',
    'opv1_date' => 'OPV1',
    'penta1_date' => 'PENTA1',
    'pcv1_date' => 'PCV1',
    'rota1_date' => 'ROTA1',
    'opv2_date' => 'OPV2',
    'penta2_date' => 'PENTA2',
    'pcv2_date' => 'PCV2',
    'rota2_date' => 'ROTA2',
    'ipv1_date' => 'IPV1',
    'opv3_date' => 'OPV3',
    'penta3_date' => 'PENTA3',
    'pcv3_date' => 'PCV3',
    'mr1_date' => 'MR1',
    'yf_date' => 'YF',
    'mr2_date' => 'MR2',
    'mena_date' => 'MenA',
    'yf2_date' => 'YF2',
    'slea_date' => 'SLEA',
    'vita1_date' => 'VitA1',
    'vita2_date' => 'VitA2',
    'ipv2_date' => 'IPV2',
  ];

  protected $fillable = [
    'patient_id',
    'linked_child_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'visit_date',
    'immunization_card_no',
    'follow_up_address',
    'follow_up_phone',
    'hepb0_date',
    'opv0_date',
    'bcg_date',
    'opv1_date',
    'penta1_date',
    'pcv1_date',
    'rota1_date',
    'opv2_date',
    'penta2_date',
    'pcv2_date',
    'rota2_date',
    'ipv1_date',
    'opv3_date',
    'penta3_date',
    'pcv3_date',
    'mr1_date',
    'yf_date',
    'mr2_date',
    'mena_date',
    'yf2_date',
    'slea_date',
    'vita1_date',
    'vita2_date',
    'ipv2_date',
    'comments',
    'summary_map',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'hepb0_date' => 'date',
    'opv0_date' => 'date',
    'bcg_date' => 'date',
    'opv1_date' => 'date',
    'penta1_date' => 'date',
    'pcv1_date' => 'date',
    'rota1_date' => 'date',
    'opv2_date' => 'date',
    'penta2_date' => 'date',
    'pcv2_date' => 'date',
    'rota2_date' => 'date',
    'ipv1_date' => 'date',
    'opv3_date' => 'date',
    'penta3_date' => 'date',
    'pcv3_date' => 'date',
    'mr1_date' => 'date',
    'yf_date' => 'date',
    'mr2_date' => 'date',
    'mena_date' => 'date',
    'yf2_date' => 'date',
    'slea_date' => 'date',
    'vita1_date' => 'date',
    'vita2_date' => 'date',
    'ipv2_date' => 'date',
    'summary_map' => 'array',
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

  public function getGivenVaccinesCountAttribute(): int
  {
    return collect(self::VACCINE_FIELDS)
      ->filter(fn($field) => !empty($this->{$field}))
      ->count();
  }

  public function getLastAntigenAttribute(): ?string
  {
    $lastField = null;
    $lastDate = null;

    foreach (self::VACCINE_FIELDS as $field) {
      if (empty($this->{$field})) {
        continue;
      }
      if (!$lastDate || $this->{$field}->greaterThan($lastDate)) {
        $lastDate = $this->{$field};
        $lastField = $field;
      }
    }

    return $lastField ? self::VACCINE_LABELS[$lastField] : null;
  }
}
