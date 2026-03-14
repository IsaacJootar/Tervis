<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('doctor_assessments', function (Blueprint $table) {
      if (!Schema::hasColumn('doctor_assessments', 'next_appointment_date')) {
        $table->date('next_appointment_date')->nullable()->after('visit_date');
      }
    });
  }

  public function down(): void
  {
    Schema::table('doctor_assessments', function (Blueprint $table) {
      if (Schema::hasColumn('doctor_assessments', 'next_appointment_date')) {
        $table->dropColumn('next_appointment_date');
      }
    });
  }
};
