<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DrugDispenseLine extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'drug_catalog_item_id',
    'prescription_id',
    'month_year',
    'dispensed_date',
    'dispense_code',
    'drug_name',
    'quantity',
    'dispense_notes',
    'dispensed_by',
  ];

  protected $casts = [
    'month_year' => 'date',
    'dispensed_date' => 'date',
    'quantity' => 'decimal:2',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function catalogItem(): BelongsTo
  {
    return $this->belongsTo(DrugCatalogItem::class, 'drug_catalog_item_id');
  }

  public function prescription(): BelongsTo
  {
    return $this->belongsTo(Prescription::class);
  }
}

