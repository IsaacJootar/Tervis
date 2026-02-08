<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAttendance extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'visit_date',
    'date_of_birth',
    'gender',
    'age_group',
    'address',
    'state_of_origin_id',
    'phone',
    'first_contact',
    'next_of_kin_name',
    'next_of_kin_relation',
    'next_of_kin_address',
    'next_of_kin_phone',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  // Each daily record belongs to ONE user (who created/owns it)

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  //  Each daily record belongs to ONE facility (hospital/clinic)
  // Think: "This attendance happened at which facility?"
  public function facility()
  {
    return $this->belongsTo(Facility::class);
  }

  //  Each daily record belongs to ONE state (current location)
  // Think: "This person visited a facility in which state?"
  public function state()
  {
    return $this->belongsTo(State::class, 'state_id');
  }

  //  Each daily record belongs to ONE LGA (Local Government Area)

  public function lga()
  {
    return $this->belongsTo(Lga::class, 'lga_id');
  }

  //  Each daily record belongs to ONE ward (smallest administrative unit)

  public function ward()
  {
    return $this->belongsTo(Ward::class, 'ward_id');
  }

  //  Each daily record belongs to ONE state of origin (where person is from)
  public function stateOfOrigin()
  {
    return $this->belongsTo(State::class, 'state_of_origin_id');
  }
}
