<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataOfficer extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'role',
        'designation',
        'facility_id',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with Facility
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
