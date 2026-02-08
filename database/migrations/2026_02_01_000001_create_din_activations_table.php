<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('din_activations', function (Blueprint $table) {
      $table->id();

      // Patient and Facility Links
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      // Attendance Details
      $table->date('visit_date');
      $table->time('check_in_time');

      // Patient Information (Copied from Patient at Activation)
      $table->string('patient_din');
      $table->string('patient_first_name');
      $table->string('patient_middle_name')->nullable();
      $table->string('patient_last_name');
      $table->string('patient_phone')->nullable();
      $table->integer('patient_age')->nullable();
      $table->enum('patient_gender', ['Male', 'Female'])->nullable();

      // Officer Tracking
      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();
      $table->softDeletes();

      // Indexes for performance
      $table->index(['facility_id', 'visit_date']);
      $table->index(['patient_id', 'visit_date']);
      $table->index('visit_date');
      $table->index('patient_din');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('din_activations');
  }
};
