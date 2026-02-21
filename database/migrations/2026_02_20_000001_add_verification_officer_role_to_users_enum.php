<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    DB::statement("
      ALTER TABLE users
      MODIFY COLUMN role ENUM(
        'Central Admin',
        'State Data Administrator',
        'State Administrator',
        'LGA Officer',
        'LGA Data Administrator',
        'LGA Administrator',
        'Facility Administrator',
        'Data Officer',
        'Verification Officer',
        'Patient'
      ) NOT NULL
    ");
  }

  public function down(): void
  {
    DB::statement("
      ALTER TABLE users
      MODIFY COLUMN role ENUM(
        'Central Admin',
        'State Data Administrator',
        'State Administrator',
        'LGA Officer',
        'LGA Data Administrator',
        'LGA Administrator',
        'Facility Administrator',
        'Data Officer',
        'Patient'
      ) NOT NULL
    ");
  }
};
