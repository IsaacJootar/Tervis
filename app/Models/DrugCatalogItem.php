<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DrugCatalogItem extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'drug_name',
    'formulation',
    'strength',
    'route',
    'notes',
    'is_active',
  ];

  protected $casts = [
    'is_active' => 'boolean',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function dispenseLines(): HasMany
  {
    return $this->hasMany(DrugDispenseLine::class);
  }
}

