<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Administrator extends Authenticatable
{
  use HasFactory;

  protected $table = 'administrators';

  protected $fillable = [
    'user_id',
    'first_name',
    'last_name',
    'email',
    'password',
    'role',
    'designation',
    'facility_id',
    'state_id',
    'lga_id',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  public function facility()
  {
    return $this->belongsTo(Facility::class);
  }

  public function state()
  {
    return $this->belongsTo(State::class);
  }

  public function lga()
  {
    return $this->belongsTo(Lga::class);
  }
}
