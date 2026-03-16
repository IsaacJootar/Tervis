<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('family_planning_follow_ups', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id');
      $table->foreignId('facility_id');
      $table->foreignId('family_planning_registration_id')->nullable();
      $table->foreignId('state_id')->nullable();
      $table->foreignId('lga_id')->nullable();
      $table->foreignId('ward_id')->nullable();

      $table->foreign('patient_id', 'fpfu_patient_fk')
        ->references('id')->on('patients')
        ->onDelete('cascade');
      $table->foreign('facility_id', 'fpfu_facility_fk')
        ->references('id')->on('facilities')
        ->onDelete('cascade');
      $table->foreign('family_planning_registration_id', 'fpfu_reg_fk')
        ->references('id')->on('family_planning_registrations')
        ->nullOnDelete();
      $table->foreign('state_id', 'fpfu_state_fk')
        ->references('id')->on('states')
        ->nullOnDelete();
      $table->foreign('lga_id', 'fpfu_lga_fk')
        ->references('id')->on('lgas')
        ->nullOnDelete();
      $table->foreign('ward_id', 'fpfu_ward_fk')
        ->references('id')->on('wards')
        ->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('visit_date');
      $table->date('next_appointment_date')->nullable();

      $table->enum('method_change', ['Y', 'N'])->nullable();
      $table->string('method_supplied')->nullable();
      $table->string('brand_size_quality')->nullable();

      $table->string('blood_pressure', 20)->nullable();
      $table->decimal('weight', 5, 2)->nullable();
      $table->enum('pelvic_exam_performed', ['Y', 'N'])->nullable();

      $table->text('observation_notes')->nullable();
      $table->json('summary_map')->nullable();

      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'month_year']);
      $table->index(['facility_id', 'visit_date']);
      $table->index(['patient_id', 'facility_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('family_planning_follow_ups');
  }
};
