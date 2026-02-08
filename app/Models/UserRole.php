<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserRole extends Authenticatable
{
  use HasFactory;

  protected $table = 'user_roles';

  protected $fillable = [
    'first_name',
    'last_name',
    'username',
    'password',
    'role',
    'designation',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];
}
