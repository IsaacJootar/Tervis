<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('tetanus_vaccinations', function (Blueprint $table) {
      $table->id();

      // ============================================
      // RELATIONSHIPS
      // ============================================
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('antenatal_registration_id')->constrained('antenatal_registrations')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      // ============================================
      // VISIT INFORMATION
      // ============================================
      $table->date('visit_date');

      // ============================================
      // TETANUS VACCINATION INFORMATION
      // ============================================
      $table->enum('current_tt_dose', ['TT1', 'TT2', 'TT3', 'TT4', 'TT5']);
      $table->date('dose_date');
      $table->tinyInteger('dose_number'); // 1, 2, 3, 4, or 5

      // ============================================
      // PROTECTION AND SCHEDULING
      // ============================================
      $table->enum('protection_status', ['Not Protected', 'Partially Protected', 'Protected', 'Fully Protected']);
      $table->integer('dose_interval')->nullable(); // Days between doses
      $table->date('next_appointment_date')->nullable();

      // ============================================
      // VACCINE INFORMATION
      // ============================================
      $table->enum('vaccination_site', ['Left Upper Arm', 'Right Upper Arm', 'Left Thigh', 'Right Thigh'])->nullable();
      $table->string('batch_number', 50)->nullable();
      $table->date('expiry_date')->nullable();

      // ============================================
      // SAFETY MONITORING
      // ============================================
      $table->enum('adverse_event', ['None', 'Mild Pain', 'Swelling', 'Fever', 'Other'])->default('None');
      $table->text('adverse_event_details')->nullable();
      $table->text('notes')->nullable();

      // ============================================
      // PATIENT SNAPSHOT (Copied at record creation)
      // ============================================
      $table->string('patient_din', 8);
      $table->string('patient_first_name');
      $table->string('patient_middle_name')->nullable();
      $table->string('patient_last_name');
      $table->string('patient_phone')->nullable();
      $table->integer('patient_age')->nullable();
      $table->enum('patient_gender', ['Male', 'Female'])->nullable();

      // ============================================
      // OFFICER TRACKING
      // ============================================
      $table->string('officer_name');
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      // ============================================
      // TIMESTAMPS
      // ============================================
      $table->timestamps();
      $table->softDeletes();

      // ============================================
      // INDEXES FOR PERFORMANCE
      // ============================================
      $table->index(['facility_id', 'visit_date']);
      $table->index(['patient_id', 'visit_date']);
      $table->index(['antenatal_registration_id', 'dose_number']);
      $table->index('visit_date');
      $table->index('current_tt_dose');
      $table->index('protection_status');
      $table->index('next_appointment_date');
      $table->index('patient_din');

      // Unique constraint: One dose type per pregnancy
      $table->unique(['antenatal_registration_id', 'current_tt_dose'], 'unique_pregnancy_dose');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tetanus_vaccinations');
  }
};
