<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalNote extends Model
{
  use HasFactory;



  protected $fillable = [
    'user_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'date_of_visit',
    'section',
    'note',
    'phone',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'date_of_visit' => 'date',
  ];

  /**
   * Get the user that owns the clinical note.
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the facility that owns the clinical note.
   */
  public function facility()
  {
    return $this->belongsTo(Facility::class);
  }

  /**
   * Get the state that owns the clinical note.
   */
  public function state()
  {
    return $this->belongsTo(State::class);
  }

  /**
   * Get the LGA that owns the clinical note.
   */
  public function lga()
  {
    return $this->belongsTo(Lga::class);
  }

  /**
   * Get the ward that owns the clinical note.
   */
  public function ward()
  {
    return $this->belongsTo(Ward::class);
  }
}
